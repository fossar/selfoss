import selfoss from './selfoss-base';
import { OfflineStorageNotAvailableError } from './errors';
import Dexie, {
    PromiseExtended,
    Table,
    Transaction,
    TransactionMode,
} from 'dexie';
import { OfflineDb, Entry } from './model/OfflineDb';
import { FilterType } from './Filter';
import { FetchParams } from './selfoss-db-online';

export type ItemStatus = {
    id: number;
    unread?: boolean;
    starred?: boolean;
};

const ENTRY_STATUS_NAMES: Array<'unread' | 'starred'> = ['unread', 'starred'];

export default class DbOffline {
    /** @var Date the datetime of the newest garbage collected entry, i.e. deleted because not of interest. */
    public newestGCedEntry: Date | null = null;
    public offlineDays: number = 10;

    public lastItemId: number | null = null;
    public newerEntriesMissing: boolean = false;
    public shouldLoadEntriesOnline: boolean = false;
    public olderEntriesOnline: boolean = false;
    public needsSync: boolean;

    _tr<U>(
        mode: TransactionMode,
        tables: Table[],
        scope: (trans: Transaction) => PromiseLike<U> | U,
    ): PromiseExtended<U> {
        return selfoss.db.storage
            .transaction(mode, tables, scope)
            .catch((error) => {
                selfoss.app.showError(
                    selfoss.app._('error_offline_storage', {
                        '0': error.message,
                    }),
                );
                selfoss.db.broken = true;
                selfoss.db.enableOffline.update(false);
                selfoss.entriesPage?.reload();

                // If this is a QuotaExceededError, garbage collect more
                // entries and hope it helps.
                if (error.name === Dexie.errnames.QuotaExceeded) {
                    this.GCEntries(true);
                }

                return Promise.reject(error);
            });
    }

    init() {
        if (!selfoss.db.enableOffline.value || selfoss.db.storage) {
            return;
        }

        selfoss.db.broken = false;
        selfoss.db.storage = new OfflineDb();

        selfoss.db.storage.on('populate', () => {
            selfoss.db.storage.stats.add({ name: 'unread', value: 0 });
            selfoss.db.storage.stats.add({ name: 'starred', value: 0 });
            selfoss.db.storage.stats.add({ name: 'total', value: 0 });
        });

        // retrieve last update stats in offline db
        return selfoss.dbOffline
            ._tr(
                'r',
                [selfoss.db.storage.entries, selfoss.db.storage.stamps],
                () => {
                    this._memLastItemId();
                    selfoss.db.storage.stamps.get(
                        'lastItemsUpdate',
                        (stamp) => {
                            if (stamp) {
                                selfoss.db.lastUpdate = stamp.datetime;
                                selfoss.dbOnline.firstSync = false;
                            } else {
                                this.shouldLoadEntriesOnline = true;
                            }
                        },
                    );
                    selfoss.db.storage.stamps.get(
                        'newestGCedEntry',
                        (stamp) => {
                            if (stamp) {
                                this.newestGCedEntry = stamp.datetime;
                            }

                            const limit = new Date(
                                Date.now() - 3 * 24 * 3600 * 1000,
                            );
                            if (!stamp || this.newestGCedEntry < limit) {
                                this.newestGCedEntry = new Date(
                                    Date.now() - 24 * 3600 * 1000,
                                );
                            }
                        },
                    );
                },
            )
            .then(() => {
                const offlineDays = window.localStorage.getItem('offlineDays');
                if (offlineDays !== null) {
                    this.offlineDays = parseInt(offlineDays);
                }
                // The newest garbage collected entry is either what's already
                // in the offline db or if more recent the entry older than
                // offlineDays ago.
                this.newestGCedEntry = new Date(
                    Math.max(
                        +this.newestGCedEntry,
                        Date.now() - this.offlineDays * 86400000,
                    ),
                );

                window.addEventListener('online', () => {
                    selfoss.db.tryOnline();
                });
                window.addEventListener('offline', () => {
                    selfoss.db.setOffline().catch((error) => {
                        if (error instanceof OfflineStorageNotAvailableError) {
                            selfoss.app.showError(
                                selfoss.app._(
                                    'error_offline_storage_not_available',
                                    {
                                        '0': '<a href="https://caniuse.com/#feat=indexeddb">',
                                        '1': '</a>',
                                    },
                                ),
                            );
                        } else {
                            throw error;
                        }
                    });
                });

                selfoss.app.setOfflineState(false);
                selfoss.db.tryOnline().then(() => {
                    selfoss.reloadTags();
                });
                this.reloadOnlineStats();
                this.refreshStats();
            })
            .catch(() => {
                selfoss.db.broken = true;
                selfoss.db.enableOffline.update(false);
            });
    }

    _memLastItemId() {
        return selfoss.db.storage.entries
            .orderBy('id')
            .reverse()
            .first((entry) => {
                if (entry) {
                    this.lastItemId = entry.id;
                } else {
                    this.lastItemId = 0;
                }
            });
    }

    storeEntries(entries) {
        return this._tr(
            'rw',
            [selfoss.db.storage.entries, selfoss.db.storage.stamps],
            () => {
                this.GCEntries();

                // store entries offline
                selfoss.db.storage.entries.bulkPut(entries).then(() => {
                    this._memLastItemId();
                    this.refreshStats();
                });
            },
        );
    }

    GCEntries(more = false) {
        if (more) {
            // We need to garbage collect more, as the browser storage limit
            // seems to be exceeded: decrease the amount of days entries are
            // kept offline.
            const keptDays = Math.floor(
                (Date.now() - +this.newestGCedEntry) / 86400000,
            );
            this.offlineDays = Math.max(
                Math.min(keptDays - 1, this.offlineDays - 1),
                0,
            );
            window.localStorage.setItem(
                'offlineDays',
                this.offlineDays.toString(),
            );
        }

        return selfoss.db.storage.transaction(
            'rw',
            selfoss.db.storage.entries,
            selfoss.db.storage.stamps,
            () => {
                // cleanup and remember when
                selfoss.db.storage.stamps.get('lastCleanup', (stamp) => {
                    // Cleanup once a day or once after db reset
                    if (
                        !stamp ||
                        more ||
                        (stamp &&
                            Date.now() - +stamp.datetime > 24 * 3600 * 1000)
                    ) {
                        // Cleanup items older than offlineDays days, not of
                        // interest.
                        const limit = new Date(
                            Date.now() - this.offlineDays * 24 * 3600 * 1000,
                        );

                        selfoss.db.storage.entries
                            .where('datetime')
                            .below(limit)
                            .filter((entry) => {
                                return !entry.unread && !entry.starred;
                            })
                            .each((entry) => {
                                selfoss.db.storage.entries.delete(entry.id);
                                if (this.newestGCedEntry < entry.datetime) {
                                    this.newestGCedEntry = entry.datetime;
                                }
                            })
                            .then(() => {
                                selfoss.db.storage.stamps.bulkPut([
                                    {
                                        name: 'lastCleanup',
                                        datetime: new Date(),
                                    },
                                    {
                                        name: 'newestGCedEntry',
                                        datetime: this.newestGCedEntry,
                                    },
                                ]);
                            });
                    }
                });
            },
        );
    }

    storeStats(stats: { [key: string]: number }): Promise<void> {
        return this._tr('rw', [selfoss.db.storage.stats], () => {
            for (const [name, value] of Object.entries(stats)) {
                selfoss.db.storage.stats.put({
                    name,
                    value,
                });
            }
        });
    }

    storeLastUpdate(lastUpdate: Date): Promise<void> {
        return this._tr('rw', [selfoss.db.storage.stamps], () => {
            if (lastUpdate) {
                selfoss.db.storage.stamps.put({
                    name: 'lastItemsUpdate',
                    datetime: lastUpdate,
                });
            }
        });
    }

    getEntries(
        fetchParams: FetchParams,
    ): Promise<{ entries: Entry[]; hasMore: boolean }> {
        let hasMore = false;
        return selfoss.dbOffline
            ._tr('r', [selfoss.db.storage.entries], () => {
                let howMany = 0;

                const ascOrder =
                    selfoss.config.unreadOrder === 'asc' &&
                    fetchParams.type === FilterType.UNREAD;
                let entries =
                    selfoss.db.storage.entries.orderBy('[datetime+id]');
                if (!ascOrder) {
                    entries = entries.reverse();
                }

                const fromDatetime = fetchParams.fromDatetime;
                const fromId = fetchParams.fromId;
                const seek = fromDatetime && fromId;
                const alwaysInDb =
                    fetchParams.type === FilterType.STARRED ||
                    fetchParams.type === FilterType.UNREAD;

                return entries
                    .filter((entry) => {
                        let keepEntry = false;

                        if (fetchParams.extraIds.includes(entry.id)) {
                            return true;
                        }

                        if (fetchParams.type === FilterType.STARRED) {
                            keepEntry = entry.starred;
                        } else if (fetchParams.type === FilterType.UNREAD) {
                            keepEntry = entry.unread;
                        } else {
                            keepEntry = true;
                        }

                        // seek pagination
                        if (seek) {
                            if (ascOrder) {
                                keepEntry &&=
                                    entry.datetime > fromDatetime ||
                                    (entry.datetime.getTime() ==
                                        fromDatetime.getTime() &&
                                        entry.id > fromId);
                            } else {
                                keepEntry &&=
                                    entry.datetime < fromDatetime ||
                                    (entry.datetime.getTime() ==
                                        fromDatetime.getTime() &&
                                        entry.id < fromId);
                            }
                        }

                        return keepEntry;
                    })
                    .until((entry) => {
                        howMany += 1;

                        if (
                            !ascOrder &&
                            !alwaysInDb &&
                            entry.datetime < this.newestGCedEntry
                        ) {
                            // the offline db is missing older entries, the next
                            // seek will have to find them online.
                            this.olderEntriesOnline = true;
                            hasMore = true;
                            return true; // stop iteration
                        }

                        // stop iteration if enough entries have been shown
                        // go one further to assess if has more
                        if (howMany >= selfoss.config.itemsPerPage + 1) {
                            hasMore = true;
                            return true;
                        }

                        return false;
                    });
            })
            .then((entriesCollection) => entriesCollection.toArray())
            .then((entries) => ({ entries, hasMore }));
    }

    reloadOnlineStats() {
        return this._tr('r', [selfoss.db.storage.stats], () => {
            selfoss.db.storage.stats.toArray((stats) => {
                const newStats = {
                    unread: 0,
                    starred: 0,
                    total: 0,
                };
                stats.forEach((stat) => {
                    newStats[stat.name] = stat.value;
                });
                selfoss.refreshStats(
                    newStats.total,
                    newStats.unread,
                    newStats.starred,
                );
            });
        });
    }

    refreshStats() {
        return this._tr('r', [selfoss.db.storage.entries], () => {
            const offlineCounts = { newest: 0, unread: 0, starred: 0 };

            // IDBKeyRange does not support boolean indexes, so we need to
            // iterate over all the entries.
            selfoss.db.storage.entries
                .each((entry) => {
                    offlineCounts.newest = offlineCounts.newest + 1;
                    if (entry.unread) {
                        offlineCounts.unread = offlineCounts.unread + 1;
                    }
                    if (entry.starred) {
                        offlineCounts.starred = offlineCounts.starred + 1;
                    }
                })
                .then(() => {
                    selfoss.app.refreshOfflineCounts(offlineCounts);
                });
        });
    }

    enqueueStatuses(
        statuses: { entryId: string; name: string; value: string }[],
    ): Promise<void> {
        if (statuses) {
            this.needsSync = true;
        }

        const d = new Date();
        const newQueuedStatuses = statuses.map((newStatus) => ({
            entryId: parseInt(newStatus.entryId),
            name: newStatus.name,
            value: newStatus.value,
            datetime: d,
        }));

        return this._tr('rw', [selfoss.db.storage.statusq], () => {
            selfoss.db.storage.statusq.bulkAdd(newQueuedStatuses);
        });
    }

    enqueueStatus(entryId, statusName, statusValue) {
        return this.enqueueStatuses([
            {
                entryId,
                name: statusName,
                value: statusValue,
            },
        ]);
    }

    sendNewStatuses() {
        selfoss.db.storage.statusq
            .toArray()
            .then((statuses) => {
                return statuses.map((s) => {
                    const statusUpdate = {
                        id: s.entryId,
                        datetime: s.datetime,
                    };
                    statusUpdate[s.name] = s.value;

                    return statusUpdate;
                });
            })
            .then((statuses) => {
                const s = statuses.length > 0 ? statuses : undefined;
                selfoss.dbOnline.sync(s, true).then(() => {
                    this.needsSync = false;
                });
            });

        return selfoss.dbOnline._syncBegin();
    }

    storeEntryStatuses(
        itemStatuses: ItemStatus[],
        dequeue: boolean = false,
        updateStats: boolean = true,
    ): Promise<void> {
        return selfoss.dbOffline
            ._tr(
                'rw',
                [
                    selfoss.db.storage.entries,
                    selfoss.db.storage.stats,
                    selfoss.db.storage.statusq,
                ],
                () => {
                    const statsDiff = { unread: 0, starred: 0 };

                    // update entries statuses
                    itemStatuses.forEach((itemStatus) => {
                        const newStatus = {};

                        ENTRY_STATUS_NAMES.forEach((statusName) => {
                            if (statusName in itemStatus) {
                                newStatus[statusName] = itemStatus[statusName];

                                if (updateStats) {
                                    if (itemStatus[statusName]) {
                                        statsDiff[statusName]++;
                                    } else {
                                        statsDiff[statusName]--;
                                    }
                                }
                            }
                        });

                        const id = itemStatus.id;
                        selfoss.db.storage.entries.get(id).then(
                            () => {
                                selfoss.db.storage.entries.update(
                                    id,
                                    newStatus,
                                );
                            },
                            () => {
                                // the key was not found, the status of an entry
                                // missing in db was updated, request sync.
                                this.needsSync = true;
                            },
                        );

                        if (dequeue) {
                            // status update from server, remove from status queue
                            selfoss.db.storage.statusq
                                .where('entryId')
                                .equals(id)
                                .delete();
                        }
                    });

                    if (updateStats) {
                        for (const [name, value] of Object.entries(statsDiff)) {
                            selfoss.db.storage.stats.get(name, (stat) => {
                                selfoss.db.storage.stats.put({
                                    name,
                                    value: stat.value + value,
                                });
                            });
                        }
                    }
                },
            )
            .then(this.refreshStats);
    }

    entriesMark(itemIds: number[], unread: boolean): Promise<void> {
        selfoss.dbOnline.statsDirty = true;
        const newStatuses = itemIds.map((itemId) => {
            return { id: itemId, unread };
        });
        return this.storeEntryStatuses(newStatuses);
    }

    entryMark(itemId: number, unread: boolean): Promise<void> {
        return this.entriesMark([itemId], unread);
    }

    entryStar(itemId: number, starred: boolean): Promise<void> {
        return this.storeEntryStatuses([
            {
                id: itemId,
                starred,
            },
        ]);
    }
}
