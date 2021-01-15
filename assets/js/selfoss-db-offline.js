import selfoss from './selfoss-base';
import { OfflineStorageNotAvailableError } from './errors';
import Dexie from 'dexie';
import { FilterType } from './Filter';


selfoss.dbOffline = {


    /** @var Date the datetime of the newest garbage collected entry, i.e. deleted because not of interest. */
    newestGCedEntry: null,
    offlineDays: 10,

    lastItemId: null,
    newerEntriesMissing: false,
    shouldLoadEntriesOnline: false,
    olderEntriesOnline: false,

    _tr: function() {
        return selfoss.db.storage.transaction
            .apply(selfoss.db.storage, arguments)
            .catch(function(error) {
                selfoss.ui.showError(
                    selfoss.ui._('error_offline_storage', [error.message])
                );
                selfoss.db.broken = true;
                selfoss.db.enableOffline.update(false);
                selfoss.db.reloadList();

                // If this is a QuotaExceededError, garbage collect more
                // entries and hope it helps.
                if (error.name === Dexie.errnames.QuotaExceeded) {
                    selfoss.dbOffline.GCEntries(true);
                }

                return Promise.reject(error);
            });
    },


    init: function() {
        if (!selfoss.db.enableOffline.value) {
            return Promise.reject();
        }

        selfoss.db.broken = false;
        selfoss.db.storage = new Dexie('selfoss');
        selfoss.db.storage.version(1).stores({
            entries: '&id,*datetime,[datetime+id]',
            statusq: '++id,*entryId',
            stamps: '&name,datetime',
            stats: '&name',
            tags: '&name',
            sources: '&id'
        });

        selfoss.db.storage.on('populate', function() {
            selfoss.db.storage.stats.add({name: 'unread', value: 0});
            selfoss.db.storage.stats.add({name: 'starred', value: 0});
            selfoss.db.storage.stats.add({name: 'total', value: 0});
        });

        // retrieve last update stats in offline db
        return selfoss.dbOffline._tr('r',
            selfoss.db.storage.entries,
            selfoss.db.storage.stamps,
            function() {
                selfoss.dbOffline._memLastItemId();
                selfoss.db.storage.stamps.get('lastItemsUpdate', function(stamp) {
                    if (stamp) {
                        selfoss.db.lastUpdate = stamp.datetime;
                        selfoss.dbOnline.firstSync = false;
                    } else {
                        selfoss.dbOffline.shouldLoadEntriesOnline = true;
                    }
                });
                selfoss.db.storage.stamps.get('newestGCedEntry', function(stamp) {
                    if (stamp) {
                        selfoss.dbOffline.newestGCedEntry = stamp.datetime;
                    }

                    var limit = new Date(Date.now() - 3 * 24 * 3600 * 1000);
                    if (!stamp || selfoss.dbOffline.newestGCedEntry < limit) {
                        selfoss.dbOffline.newestGCedEntry = new Date(Date.now() - 24 * 3600 * 1000);
                    }
                });
            })
            .then(function() {
                var offlineDays = window.localStorage.getItem('offlineDays');
                if (offlineDays !== null) {
                    selfoss.dbOffline.offlineDays = parseInt(offlineDays);
                }
                // The newest garbage collected entry is either what's already
                // in the offline db or if more recent the entry older than
                // offlineDays ago.
                selfoss.dbOffline.newestGCedEntry = new Date(Math.max(
                    selfoss.dbOffline.newestGCedEntry,
                    Date.now() - (selfoss.dbOffline.offlineDays * 86400000)
                ));

                $(window).bind('online', function() {
                    selfoss.db.tryOnline();
                });
                $(window).bind('offline', function() {
                    selfoss.db.setOffline().catch((error) => {
                        if (error instanceof OfflineStorageNotAvailableError) {
                            selfoss.ui.showError(selfoss.ui._('error_offline_storage_not_available', [
                                '<a href="https://caniuse.com/#feat=indexeddb">',
                                '</a>'
                            ]));
                        } else {
                            throw error;
                        }
                    });
                });

                selfoss.ui.setOnline();
                selfoss.db.tryOnline()
                    .then(function() {
                        selfoss.reloadTags();
                    })
                    .finally(selfoss.events.init);
                selfoss.dbOffline.reloadOnlineStats();
                selfoss.dbOffline.refreshStats();
            }).catch(function() {
                selfoss.db.broken = true;
                selfoss.db.enableOffline.update(false);
            });
    },


    _memLastItemId: function() {
        return selfoss.db.storage.entries.orderBy('id').reverse().first(function(entry) {
            if (entry) {
                selfoss.dbOffline.lastItemId = entry.id;
            } else {
                selfoss.dbOffline.lastItemId = 0;
            }
        });
    },


    storeEntries: function(entries) {
        return selfoss.dbOffline._tr('rw',
            selfoss.db.storage.entries,
            selfoss.db.storage.stamps,
            function() {
                selfoss.dbOffline.GCEntries();

                // store entries offline
                selfoss.db.storage.entries.bulkPut(entries).then(function() {
                    selfoss.dbOffline._memLastItemId();
                    selfoss.dbOffline.refreshStats();
                });
            });
    },


    GCEntries: function(more = false) {
        if (more) {
            // We need to garbage collect more, as the browser storage limit
            // seems to be exceeded: decrease the amount of days entries are
            // kept offline.
            var keptDays = Math.floor((new Date() -
                                       selfoss.dbOffline.newestGCedEntry) /
                                       86400000);
            selfoss.dbOffline.offlineDays = Math.max(
                Math.min(keptDays - 1, selfoss.dbOffline.offlineDays - 1),
                0
            );
            window.localStorage.setItem('offlineDays',
                selfoss.dbOffline.offlineDays);
        }

        return selfoss.db.storage.transaction('rw',
            selfoss.db.storage.entries,
            selfoss.db.storage.stamps,
            function() {
                // cleanup and remember when
                selfoss.db.storage.stamps.get('lastCleanup', function(stamp) {
                    // Cleanup once a day or once after db reset
                    if (!stamp || more || (stamp && Date.now() - stamp.datetime > 24 * 3600 * 1000)) {
                        // Cleanup items older than offlineDays days, not of
                        // interest.
                        var limit = new Date(Date.now() -
                        selfoss.dbOffline.offlineDays * 24 * 3600 * 1000);

                        selfoss.db.storage.entries.where('datetime').below(limit)
                            .filter(function(entry) {
                                return !entry.unread && !entry.starred;
                            }).each(function(entry) {
                                selfoss.db.storage.entries.delete(entry.id);
                                if (selfoss.dbOffline.newestGCedEntry < entry.datetime) {
                                    selfoss.dbOffline.newestGCedEntry = entry.datetime;
                                }
                            }
                            ).then(function() {
                                selfoss.db.storage.stamps.bulkPut([
                                    {name: 'lastCleanup', datetime: new Date()},
                                    {
                                        name: 'newestGCedEntry',
                                        datetime: selfoss.dbOffline.newestGCedEntry
                                    }
                                ]);
                            });
                    }
                });
            });
    },


    storeStats: function(stats) {
        return selfoss.dbOffline._tr('rw', selfoss.db.storage.stats,
            function() {
                for (let [name, value] of Object.entries(stats)) {
                    selfoss.db.storage.stats.put({
                        name,
                        value
                    });
                }
            });
    },


    storeLastUpdate: function(lastUpdate) {
        return selfoss.dbOffline._tr('rw', selfoss.db.storage.stamps,
            function() {
                if (lastUpdate) {
                    selfoss.db.storage.stamps.put({
                        name: 'lastItemsUpdate',
                        datetime: lastUpdate
                    });
                }
            });
    },


    reloadList: function() {
        let hasMore = false;
        return selfoss.dbOffline._tr('r', selfoss.db.storage.entries,
            function() {
                var howMany = 0;

                var ascOrder = selfoss.db.ascOrder();
                var entries = selfoss.db.storage.entries.orderBy('[datetime+id]');
                if (!ascOrder) {
                    entries = entries.reverse();
                }

                const fromDatetime = selfoss.filter.fromDatetime;
                const fromId = selfoss.filter.fromId;
                const seek = fromDatetime && fromId;
                const alwaysInDb = selfoss.filter.type === FilterType.STARRED
                             || selfoss.filter.type === FilterType.UNREAD;

                return entries.filter(function(entry) {
                    var keepEntry = false;

                    if (selfoss.filter.extraIds.includes(entry.id)) {
                        return true;
                    }

                    if (selfoss.filter.type === FilterType.STARRED) {
                        keepEntry = entry.starred;
                    } else if (selfoss.filter.type === FilterType.UNREAD) {
                        keepEntry = entry.unread;
                    } else {
                        keepEntry = true;
                    }

                    // seek pagination
                    if (seek) {
                        if (ascOrder) {
                            keepEntry &&= entry.datetime > fromDatetime
                            || (entry.datetime.getTime() == fromDatetime.getTime()
                                && entry.id > fromId);
                        } else {
                            keepEntry &&= entry.datetime < fromDatetime
                            || (entry.datetime.getTime() == fromDatetime.getTime()
                                && entry.id < fromId);
                        }
                    }

                    return keepEntry;
                }).until(function(entry) {
                    howMany += 1;

                    if (!ascOrder && !alwaysInDb && entry.datetime < selfoss.dbOffline.newestGCedEntry) {
                        // the offline db is missing older entries, the next
                        // seek will have to find them online.
                        selfoss.dbOffline.olderEntriesOnline = true;
                        hasMore = true;
                        return true; // stop iteration
                    }

                    // stop iteration if enough entries have been shown
                    // go one further to assess if has more
                    if (howMany >= selfoss.filter.itemsPerPage + 1) {
                        hasMore = true;
                        return true;
                    }

                    return false;
                });
            })
            .then((entriesCollection) => entriesCollection.toArray())
            .then((entries) => ({ entries, hasMore }));
    },


    reloadOnlineStats: function() {
        return selfoss.dbOffline._tr('r', selfoss.db.storage.stats,
            function() {
                selfoss.db.storage.stats.toArray(function(stats) {
                    var newStats = {};
                    stats.forEach(function(stat) {
                        newStats[stat.name] = stat.value;
                    });
                    selfoss.refreshStats(newStats.total,
                        newStats.unread, newStats.starred);
                });
            });
    },


    refreshStats: function() {
        return selfoss.dbOffline._tr('r', selfoss.db.storage.entries,
            function() {
                var offlineCounts = {newest: 0, unread: 0, starred: 0};

                // IDBKeyRange does not support boolean indexes, so we need to
                // iterate over all the entries.
                selfoss.db.storage.entries.each(function(entry) {
                    offlineCounts.newest = offlineCounts.newest + 1;
                    if (entry.unread) {
                        offlineCounts.unread = offlineCounts.unread + 1;
                    }
                    if (entry.starred) {
                        offlineCounts.starred = offlineCounts.starred + 1;
                    }
                }).then(function() {
                    selfoss.ui.refreshOfflineCounts(offlineCounts);
                });
            });
    },


    enqueueStatuses: function(statuses) {
        if (statuses) {
            selfoss.dbOffline.needsSync = true;
        }

        var d = new Date();
        let newQueuedStatuses = statuses.map(newStatus => ({
            entryId: parseInt(newStatus.entryId),
            name: newStatus.name,
            value: newStatus.value,
            datetime: d
        }));

        return selfoss.dbOffline._tr('rw', selfoss.db.storage.statusq,
            function() {
                selfoss.db.storage.statusq.bulkAdd(newQueuedStatuses);
            }
        );
    },


    enqueueStatus: function(entryId, statusName, statusValue) {
        return selfoss.dbOffline.enqueueStatuses([{
            entryId: entryId,
            name: statusName,
            value: statusValue
        }]);
    },


    sendNewStatuses: function() {
        selfoss.db.storage.statusq.toArray().then(statuses => {
            return statuses.map(s => {
                let statusUpdate = {
                    id: s.entryId,
                    datetime: s.datetime
                };
                statusUpdate[s.name] = s.value;

                return statusUpdate;
            });
        }).then(statuses => {
            const s = statuses.length > 0 ? statuses : undefined;
            selfoss.dbOnline.sync(s, true).then(function() {
                selfoss.dbOffline.needsSync = false;
            });
        });

        return selfoss.dbOnline._syncBegin();
    },


    storeEntryStatuses: function(itemStatuses, dequeue = false, updateStats = true) {
        return selfoss.dbOffline._tr('rw',
            selfoss.db.storage.entries,
            selfoss.db.storage.stats,
            selfoss.db.storage.statusq,
            function() {
                var statsDiff = {};

                // update entries statuses
                itemStatuses.forEach(function(itemStatus) {
                    var newStatus = {};

                    selfoss.db.entryStatusNames.forEach(function(statusName) {
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

                    var id = parseInt(itemStatus.id);
                    selfoss.db.storage.entries.get(id).then(function() {
                        selfoss.db.storage.entries.update(id, newStatus);
                    }, function() {
                        // the key was not found, the status of an entry
                        // missing in db was updated, request sync.
                        selfoss.dbOffline.needsSync = true;
                    });

                    if (dequeue) {
                        // status update from server, remove from status queue
                        selfoss.db.storage.statusq
                            .where('entryId').equals(id)
                            .delete();
                    }
                });

                if (updateStats) {
                    for (let [name, value] of Object.entries(statsDiff)) {
                        selfoss.db.storage.stats.get(name, function(stat) {
                            selfoss.db.storage.stats.put({
                                name,
                                value: stat.value + value
                            });
                        });
                    }
                }
            }).then(selfoss.dbOffline.refreshStats);
    },


    entriesMark: function(itemIds, unread) {
        selfoss.dbOnline.statsDirty = true;
        var newStatuses = itemIds.map(function(itemId) {
            return {id: itemId, unread: unread};
        });
        return selfoss.dbOffline.storeEntryStatuses(newStatuses);
    },


    entryMark: function(itemId, unread) {
        return selfoss.dbOffline.entriesMark([itemId], unread);
    },


    entryStar: function(itemId, starred) {
        return selfoss.dbOffline.storeEntryStatuses([{
            id: itemId,
            starred: starred
        }]);
    }


};
