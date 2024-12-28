import selfoss from './selfoss-base';
import { OfflineStorageNotAvailableError } from './errors';
import Dexie from 'dexie';
import { FilterType } from './Filter';

const ENTRY_STATUS_NAMES = ['unread', 'starred'];

selfoss.dbOffline = {
    /** @var Date the datetime of the newest garbage collected entry, i.e. deleted because not of interest. */
    newestGCedEntry: null,
    offlineDays: 10,

    lastItemId: null,
    newerEntriesMissing: false,
    shouldLoadEntriesOnline: false,
    olderEntriesOnline: false,

    _tr(...args) {
        return selfoss.db.storage.transaction(...args).catch((error) => {
            selfoss.app.showError(
                selfoss.app._('error_offline_storage', [error.message]),
            );
            selfoss.db.broken = true;
            selfoss.db.enableOffline.update(false);
            selfoss.entries?.reload();

            // If this is a QuotaExceededError, garbage collect more
            // entries and hope it helps.
            if (error.name === Dexie.errnames.QuotaExceeded) {
                selfoss.dbOffline.GCEntries(true);
            }

            return Promise.reject(error);
        });
    },

    init() {
        if (!selfoss.db.enableOffline.value || selfoss.db.storage) {
            return;
        }

        selfoss.db.broken = false;
        selfoss.db.storage = new Dexie('selfoss');
        selfoss.db.storage.version(1).stores({
            entries: '&id,*datetime,[datetime+id]',
            statusq: '++id,*entryId',
            stamps: '&name,datetime',
            stats: '&name',
            tags: '&name',
            sources: '&id',
        });

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
                    selfoss.dbOffline._memLastItemId();
                    selfoss.db.storage.stamps.get(
                        'lastItemsUpdate',
                        (stamp) => {
                            if (stamp) {
                                selfoss.db.lastUpdate = stamp.datetime;
                                selfoss.dbOnline.firstSync = false;
                            } else {
                                selfoss.dbOffline.shouldLoadEntriesOnline = true;
                            }
                        },
                    );
                    selfoss.db.storage.stamps.get(
                        'newestGCedEntry',
                        (stamp) => {
                            if (stamp) {
                                selfoss.dbOffline.newestGCedEntry =
                                    stamp.datetime;
                            }

                            const limit = new Date(
                                Date.now() - 3 * 24 * 3600 * 1000,
                            );
                            if (
                                !stamp ||
                                selfoss.dbOffline.newestGCedEntry < limit
                            ) {
                                selfoss.dbOffline.newestGCedEntry = new Date(
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
                    selfoss.dbOffline.offlineDays = parseInt(offlineDays);
                }
                // The newest garbage collected entry is either what's already
                // in the offline db or if more recent the entry older than
                // offlineDays ago.
                selfoss.dbOffline.newestGCedEntry = new Date(
                    Math.max(
                        selfoss.dbOffline.newestGCedEntry,
                        Date.now() - selfoss.dbOffline.offlineDays * 86400000,
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
                                    [
                                        '<a href="https://caniuse.com/#feat=indexeddb">',
                                        '</a>',
                                    ],
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
                selfoss.dbOffline.reloadOnlineStats();
                selfoss.dbOffline.refreshStats();
            })
            .catch(() => {
                selfoss.db.broken = true;
                selfoss.db.enableOffline.update(false);
            });
    },

    _memLastItemId() {
        return selfoss.db.storage.entries
            .orderBy('id')
            .reverse()
            .first((entry) => {
                if (entry) {
                    selfoss.dbOffline.lastItemId = entry.id;
                } else {
                    selfoss.dbOffline.lastItemId = 0;
                }
            });
    },

    storeEntries(entries) {
        return selfoss.dbOffline._tr(
            'rw',
            [selfoss.db.storage.entries, selfoss.db.storage.stamps],
            () => {
                selfoss.dbOffline.GCEntries();

                // store entries offline
                selfoss.db.storage.entries.bulkPut(entries).then(() => {
                    selfoss.dbOffline._memLastItemId();
                    selfoss.dbOffline.refreshStats();
                });
            },
        );
    },

    GCEntries(more = false) {
        if (more) {
            // We need to garbage collect more, as the browser storage limit
            // seems to be exceeded: decrease the amount of days entries are
            // kept offline.
            const keptDays = Math.floor(
                (new Date() - selfoss.dbOffline.newestGCedEntry) / 86400000,
            );
            selfoss.dbOffline.offlineDays = Math.max(
                Math.min(keptDays - 1, selfoss.dbOffline.offlineDays - 1),
                0,
            );
            window.localStorage.setItem(
                'offlineDays',
                selfoss.dbOffline.offlineDays,
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
                            Date.now() - stamp.datetime > 24 * 3600 * 1000)
                    ) {
                        // Cleanup items older than offlineDays days, not of
                        // interest.
                        const limit = new Date(
                            Date.now() -
                                selfoss.dbOffline.offlineDays *
                                    24 *
                                    3600 *
                                    1000,
                        );

                        selfoss.db.storage.entries
                            .where('datetime')
                            .below(limit)
                            .filter((entry) => {
                                return !entry.unread && !entry.starred;
                            })
                            .each((entry) => {
                                selfoss.db.storage.entries.delete(entry.id);
                                if (
                                    selfoss.dbOffline.newestGCedEntry <
                                    entry.datetime
                                ) {
                                    selfoss.dbOffline.newestGCedEntry =
                                        entry.datetime;
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
                                        datetime:
                                            selfoss.dbOffline.newestGCedEntry,
                                    },
                                ]);
                            });
                    }
                });
            },
        );
    },

    storeStats(stats) {
        return selfoss.dbOffline._tr('rw', [selfoss.db.storage.stats], () => {
            for (const [name, value] of Object.entries(stats)) {
                selfoss.db.storage.stats.put({
                    name,
                    value,
                });
            }
        });
    },

    storeLastUpdate(lastUpdate) {
        return selfoss.dbOffline._tr('rw', [selfoss.db.storage.stamps], () => {
            if (lastUpdate) {
                selfoss.db.storage.stamps.put({
                    name: 'lastItemsUpdate',
                    datetime: lastUpdate,
                });
            }
        });
    },

    getEntries(fetchParams) {
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
                            entry.datetime < selfoss.dbOffline.newestGCedEntry
                        ) {
                            // the offline db is missing older entries, the next
                            // seek will have to find them online.
                            selfoss.dbOffline.olderEntriesOnline = true;
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
    },

    reloadOnlineStats() {
        return selfoss.dbOffline._tr('r', [selfoss.db.storage.stats], () => {
            selfoss.db.storage.stats.toArray((stats) => {
                const newStats = {};
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
    },

    refreshStats() {
        return selfoss.dbOffline._tr('r', [selfoss.db.storage.entries], () => {
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
    },

    enqueueStatuses(statuses) {
        if (statuses) {
            selfoss.dbOffline.needsSync = true;
        }

        const d = new Date();
        const newQueuedStatuses = statuses.map((newStatus) => ({
            entryId: parseInt(newStatus.entryId),
            name: newStatus.name,
            value: newStatus.value,
            datetime: d,
        }));

        return selfoss.dbOffline._tr('rw', [selfoss.db.storage.statusq], () => {
            selfoss.db.storage.statusq.bulkAdd(newQueuedStatuses);
        });
    },

    enqueueStatus(entryId, statusName, statusValue) {
        return selfoss.dbOffline.enqueueStatuses([
            {
                entryId,
                name: statusName,
                value: statusValue,
            },
        ]);
    },

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
                    selfoss.dbOffline.needsSync = false;
                });
            });

        return selfoss.dbOnline._syncBegin();
    },

    storeEntryStatuses(itemStatuses, dequeue = false, updateStats = true) {
        return selfoss.dbOffline
            ._tr(
                'rw',
                [
                    selfoss.db.storage.entries,
                    selfoss.db.storage.stats,
                    selfoss.db.storage.statusq,
                ],
                () => {
                    const statsDiff = {};

                    // update entries statuses
                    itemStatuses.forEach((itemStatus) => {
                        const newStatus = {};

                        ENTRY_STATUS_NAMES.forEach((statusName) => {
                            if (statusName in itemStatus) {
                                statsDiff[statusName] = 0;
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

                        const id = parseInt(itemStatus.id);
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
                                selfoss.dbOffline.needsSync = true;
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
            .then(selfoss.dbOffline.refreshStats);
    },

    entriesMark(itemIds, unread) {
        selfoss.dbOnline.statsDirty = true;
        const newStatuses = itemIds.map((itemId) => {
            return { id: itemId, unread };
        });
        return selfoss.dbOffline.storeEntryStatuses(newStatuses);
    },

    entryMark(itemId, unread) {
        return selfoss.dbOffline.entriesMark([itemId], unread);
    },

    entryStar(itemId, starred) {
        return selfoss.dbOffline.storeEntryStatuses([
            {
                id: itemId,
                starred,
            },
        ]);
    },
};
