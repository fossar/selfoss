/**
 * db functions: client data repository (and offline storage)
 *
 * db is a dispatcher class and holds the logic for deciding whether selfoss
 * is running online with access to the server or offline.
 *
 * dbOnline contains AJAX calls that provide access to the server db.
 *
 * dbOffline is the entry point for the offline database held in the client.
 */

import React from 'jsx-dom';
import selfoss from './selfoss-base';
import * as itemsRequests from './requests/items';
import { OfflineStorageNotAvailableError } from './errors';
import Item from './templates/Item';
import Dexie from 'dexie';
import { FilterType } from './Filter';

selfoss.dbOnline = {


    syncing: {
        promise: null,
        request: null,
        resolve: null,
        reject: null
    },
    statsDirty: false,
    firstSync: true,


    _syncBegin: function() {
        if (!selfoss.dbOnline.syncing.promise) {
            selfoss.dbOnline.syncing.promise = new Promise(function(resolve, reject) {
                selfoss.dbOnline.syncing.resolve = resolve;
                selfoss.dbOnline.syncing.reject = reject;
                var monitor = window.setInterval(function() {
                    var stopChecking = false;
                    if (selfoss.dbOnline.syncing.promise) {
                        if (selfoss.db.userWaiting) {
                            // reject if user has been waiting for more than 10s,
                            // this means that connectivity is bad: user will get
                            // local content and server request will continue in
                            // the background.
                            reject();
                            stopChecking = true;
                        }
                    } else {
                        stopChecking = true;
                    }

                    if (stopChecking) {
                        window.clearInterval(monitor);
                    }
                }, 10000);
            });

            selfoss.dbOnline.syncing.promise.finally(function() {
                selfoss.dbOnline.syncing.promise = null;
                selfoss.db.userWaiting = false;
            });
        }

        return selfoss.dbOnline.syncing.promise;
    },


    _syncDone: function(success = true) {
        if (selfoss.dbOnline.syncing.promise) {
            if (success) {
                selfoss.dbOnline.syncing.resolve();
            } else {
                let request = selfoss.dbOnline.syncing.request;
                selfoss.dbOnline.syncing.reject();
                if (request) {
                    request.controller.abort();
                }
            }
        }
    },


    /**
     * sync server status.
     *
     * @return Promise
     */
    sync: function(updatedStatuses, chained) {
        if (selfoss.dbOnline.syncing.promise && !chained) {
            if (updatedStatuses) {
                // Ensure the status queue is not cleared and gets sync'ed at
                // next sync.
                return Promise.reject();
            } else {
                return selfoss.dbOnline.syncing.promise;
            }
        }

        var syncing = selfoss.dbOnline._syncBegin();

        var getStatuses = true;
        if (selfoss.db.lastUpdate === null || selfoss.dbOnline.firstSync) {
            selfoss.db.lastUpdate = new Date(0);
            getStatuses = undefined;
        }

        var syncParams = {
            since: selfoss.db.lastUpdate,
            tags: true,
            sources: selfoss.filter.sourcesNav ? true : undefined,
            itemsStatuses: getStatuses
        };

        if (updatedStatuses && updatedStatuses.length > 0) {
            syncParams.updatedStatuses = updatedStatuses;
        }

        if (selfoss.db.enableOffline) {
            syncParams.itemsSinceId = selfoss.dbOffline.lastItemId;
            syncParams.itemsNotBefore = selfoss.dbOffline.newestGCedEntry;
            syncParams.itemsHowMany = selfoss.filter.itemsPerPage;
        }

        selfoss.dbOnline.statsDirty = false;

        selfoss.dbOnline.syncing.request = itemsRequests.sync(updatedStatuses, syncParams);

        selfoss.dbOnline.syncing.request.promise.then((data) => {
            selfoss.db.setOnline();

            selfoss.db.lastSync = Date.now();
            selfoss.dbOnline.firstSync = false;

            var dataDate = data.lastUpdate;

            var storing = false;

            if (selfoss.db.enableOffline) {
                if ('newItems' in data) {
                    var maxId = 0;
                    data.newItems.forEach(function(item) {
                        maxId = Math.max(item.id, maxId);
                    });

                    selfoss.dbOffline.newerEntriesMissing = 'lastId' in data
                        && data.lastId > selfoss.dbOffline.lastItemId
                        && data.lastId > maxId;
                    storing = selfoss.dbOffline.newerEntriesMissing;

                    selfoss.dbOffline
                        .shouldLoadEntriesOnline = 'lastId' in data
                        && data.lastId - selfoss.dbOffline.lastItemId >
                        2 * selfoss.filter.itemsPerPage;

                    selfoss.dbOffline.storeEntries(data.newItems)
                        .then(function() {
                            selfoss.dbOffline.storeLastUpdate(dataDate);
                            selfoss.dbOnline._syncDone();
                        });
                }

                if (selfoss.dbOffline.newerEntriesMissing
                    || selfoss.dbOffline.needsSync) {
                    // There are still new items to fetch
                    // or statuses to send
                    syncing.then(function() {
                        selfoss.dbOffline.sendNewStatuses();
                    });
                }

                if ('itemUpdates' in data) {
                    // refresh entry statuses in db and dequeue queued
                    // statuses but do not calculate stats as they are taken
                    // directly from the server as provided.
                    selfoss.dbOffline
                        .storeEntryStatuses(data.itemUpdates, true, false)
                        .then(function() {
                            selfoss.dbOffline.storeLastUpdate(dataDate);
                        });
                }

                if ('stats' in data) {
                    selfoss.dbOffline.storeStats(data.stats);
                }
            }

            if (!selfoss.dbOnline.statsDirty && 'stats' in data) {
                selfoss.refreshStats(data.stats.total,
                    data.stats.unread,
                    data.stats.starred);
            }

            if ('tags' in data) {
                selfoss.tags.update(data.tags);
            }

            if ('sources' in data) {
                selfoss.sources.update(data.sources);
            }

            if ('stats' in data && data.stats.unread > 0 &&
                ($('.stream-empty').is(':visible') ||
                $('.stream-error').is(':visible'))) {
                selfoss.db.reloadList();
            } else {
                if ('itemUpdates' in data) {
                    selfoss.ui.refreshEntryStatuses(data.itemUpdates);
                }

                if (selfoss.filter.type == FilterType.UNREAD) {
                    var unreadCount = 0;
                    if ('stats' in data) {
                        unreadCount = data.stats.unread;
                    } else {
                        unreadCount = parseInt($('.unread-count .count')
                            .html());
                    }
                    if (unreadCount > $('.entry.unread').length) {
                        $('.stream-more').show();
                    }
                }
            }

            selfoss.db.lastUpdate = dataDate;

            if (!storing) {
                selfoss.dbOnline._syncDone();
            }
        }).catch(function(error) {
            selfoss.dbOnline._syncDone(false);
            selfoss.handleAjaxError(error).catch(function(error) {
                selfoss.ui.showError(selfoss.ui._('error_sync') + ' ' + error.message);
            });
        }).finally(function() {
            if (selfoss.dbOnline.syncing.promise) {
                selfoss.dbOnline.syncing.request = null;
            }
        });

        return syncing;
    },


    /**
     * refresh current items.
     *
     * @return void
     */
    reloadList: function() {
        if (selfoss.activeAjaxReq !== null) {
            selfoss.activeAjaxReq.controller.abort();
        }

        selfoss.activeAjaxReq = itemsRequests.getItems(selfoss.filter);

        let promise = selfoss.activeAjaxReq.promise.then((data) => {
            selfoss.db.setOnline();

            if (!selfoss.db.enableOffline) {
                selfoss.db.lastSync = Date.now();
                selfoss.db.lastUpdate = data.lastUpdate;
            }

            selfoss.refreshStats(data.all, data.unread, data.starred);

            const firstPage = selfoss.filter.offset == 0 && typeof selfoss.filter.fromId === 'undefined' && typeof selfoss.filter.fromDatetime === 'undefined';
            const allowedToUpdate = !selfoss.config.authEnabled || selfoss.config.allowPublicUpdate || document.body.classList.contains('loggedin');
            if (selfoss.filter.source && allowedToUpdate && firstPage) {
                $('#content').append(<button type="button" id="refresh-source" class="refresh-source">{selfoss.ui._('source_refresh')}</button>);
            }
            $('#content').append(data.entries.map(entry => <Item item={entry} />));
            selfoss.ui.refreshStreamButtons(true, data.hasMore);

            // update tags
            selfoss.tags.update(data.tags);

            if (selfoss.filter.sourcesNav) {
                selfoss.sources.update(data.sources);
            }
        }).catch((error) => {
            if (error.name == 'AbortError') {
                return;
            }

            selfoss.handleAjaxError(error).then(function() {
                selfoss.dbOffline.reloadList();
                selfoss.ui.afterReloadList();
            }).catch(function(error) {
                selfoss.ui.showError(selfoss.ui._('error_loading') + ' ' + error.message);
                selfoss.events.entries();
                selfoss.ui.refreshStreamButtons();
                $('.stream-error').show();
            });
        }).finally(() => {
            // clean up
            selfoss.activeAjaxReq = null;
        });

        return promise;
    }


};


selfoss.dbOffline = {


    /** @var Date the datetime of the newest garbage collected entry, i.e. deleted because not of interest. */
    newestGCedEntry: null,
    offlineDays: 10,

    lastItemId: null,
    newerEntriesMissing: false,
    shouldLoadEntriesOnline: false,
    olderEntriesOnline: false,


    _tr: function() {
        let promise = selfoss.db.storage.transaction.apply(selfoss.db.storage, arguments);

        promise.catch(function(error) {
            selfoss.ui.showError(selfoss.ui._('error_offline_storage', [error.message]));
            selfoss.db.broken = true;
            selfoss.db.enableOffline = false;
            selfoss.db.reloadList();

            // If this is a QuotaExceededError, garbage collect more
            // entries and hope it helps.
            if (error.name === Dexie.errnames.QuotaExceeded) {
                selfoss.dbOffline.GCEntries(true);
            }

            throw error;
        });

        return promise;
    },


    init: function() {
        if (!selfoss.db.enableOffline) {
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
                $('#content').addClass('loading');
                selfoss.db.tryOnline()
                    .then(function() {
                        selfoss.reloadTags();
                    })
                    .finally(selfoss.events.init);
                selfoss.dbOffline.reloadOnlineStats();
                selfoss.dbOffline.refreshStats();
            }).catch(function() {
                selfoss.db.broken = true;
                selfoss.db.enableOffline = false;
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
        return selfoss.dbOffline._tr('r', selfoss.db.storage.entries,
            function() {
                var content = $('#content');
                var newContent = content.clone().empty();

                var howMany = 0;
                var hasMore = false;

                var ascOrder = selfoss.db.ascOrder();
                var entries = selfoss.db.storage.entries.orderBy('[datetime+id]');
                if (!ascOrder) {
                    entries = entries.reverse();
                }

                var seek = false;
                var fromDatetime = selfoss.filter.fromDatetime;
                var fromId = selfoss.filter.fromId;
                if (fromDatetime && fromId) {
                    seek = true;
                }
                var isMore = false;
                var alwaysInDb = selfoss.filter.type === FilterType.STARRED
                             || selfoss.filter.type === FilterType.UNREAD;
                var offset = selfoss.filter.offset;

                entries.filter(function(entry) {
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

                    if (keepEntry && offset > 0) {
                        offset = offset - 1;
                        return false;
                    }

                    return keepEntry;
                }).until(function(entry) {
                    // stop iteration if enough entries have been shown
                    // go one further to assess if has more
                    if (howMany >= selfoss.filter.itemsPerPage + 1) {
                        return true;
                    }

                    // seek pagination
                    isMore = !seek;
                    if (seek) {
                        if (ascOrder) {
                            isMore = entry.datetime > fromDatetime
                            || (entry.datetime.getTime() == fromDatetime.getTime()
                                && entry.id > fromId);
                        } else {
                            isMore = entry.datetime < fromDatetime
                            || (entry.datetime.getTime() == fromDatetime.getTime()
                                && entry.id < fromId);
                        }
                    }

                    if (!ascOrder && !alwaysInDb
                    && entry.datetime < selfoss.dbOffline.newestGCedEntry) {
                        // the offline db is missing older entries, the next
                        // seek will have to find them online.
                        selfoss.dbOffline.olderEntriesOnline = true;
                        hasMore = true;
                        // There are missing entries before this one, do not
                        // display it.
                        isMore = false;
                        return true; // stop iteration
                    } else if (isMore && howMany >= selfoss.filter.itemsPerPage) {
                        hasMore = true;
                        // stop iteration, this entry was only to assess
                        // if hasMore.
                        isMore = false;
                        return true; // stop iteration
                    }

                    return false;
                }, true).each(function(entry) {
                    if (isMore) {
                        newContent.append(<Item item={entry} />);
                        selfoss.ui.entryMark(entry.id, entry.unread, newContent);
                        selfoss.ui.entryStar(entry.id, entry.starred, newContent);

                        howMany = howMany + 1;
                    }
                }).then(function() {
                    if (seek) {
                        content.append(newContent.children());
                    } else {
                        content.replaceWith(newContent);
                    }
                    selfoss.ui.refreshStreamButtons(true, hasMore);
                });
            });
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


selfoss.db = {

    /** When an error occurs we disable the offline mode and mark the database as broken so it can be retried. */
    broken: false,
    storage: null,
    online: true,
    enableOffline: window.localStorage.getItem('enableOffline') === 'true',
    entryStatusNames: ['unread', 'starred'],
    userWaiting: true,


    /**
     * last db timestamp known client side
     */
    lastUpdate: null,


    setOnline: function() {
        if (!selfoss.db.online) {
            selfoss.db.online = true;
            selfoss.db.sync();
            selfoss.reloadTags();
            selfoss.ui.setOnline();
        }
    },


    tryOnline: function() {
        return selfoss.db.sync(true);
    },


    setOffline: function() {
        if (selfoss.db.storage && !selfoss.db.broken) {
            selfoss.dbOnline._syncDone(false);
            selfoss.db.online = false;
            selfoss.ui.setOffline();

            return Promise.resolve();
        } else {
            let err = new OfflineStorageNotAvailableError();
            return Promise.reject(err);
        }
    },


    clear: function() {
        if (selfoss.db.storage) {
            window.localStorage.removeItem('offlineDays');
            var clearing = selfoss.db.storage.delete();
            selfoss.db.storage = null;
            selfoss.db.lastUpdate = null;
            return clearing;
        } else {
            return Promise.resolve();
        }
    },


    isValidTag: function(name) {
        return selfoss.tags.tags.find((tag) => tag.tag === name) !== undefined;
    },


    isValidSource: function(id) {
        return selfoss.sources.sources.find((source) => source.id === id) !== undefined;
    },


    ascOrder: function() {
        return selfoss.config.unreadOrder === 'asc' && selfoss.filter.type === FilterType.UNREAD;
    },


    lastSync: null,


    sync: function(force = false) {
        var lastUpdateIsOld = selfoss.db.lastUpdate === null || selfoss.db.lastSync === null || Date.now() - selfoss.db.lastSync > 5 * 60 * 1000;
        var shouldSync = force || selfoss.dbOffline.needsSync || lastUpdateIsOld;
        if (selfoss.loggedin && shouldSync) {
            if (selfoss.db.enableOffline) {
                return selfoss.dbOffline.sendNewStatuses();
            } else {
                return selfoss.dbOnline.sync();
            }
        } else {
            return Promise.resolve(); // ensure any chained function runs
        }
    },


    reloadList: function(append = false, waitForSync = true) {
        if (location.hash == '#sources') {
            return;
        }

        if (selfoss.events.entryId && selfoss.filter.fromId === undefined) {
            selfoss.filter.update({ extraIds: [...selfoss.filter.extraIds, selfoss.events.entryId] });
        }

        if (!append || selfoss.filter.type !== FilterType.NEWEST) {
            selfoss.dbOffline.olderEntriesOnline = false;
        }

        selfoss.ui.beforeReloadList(!append);

        var reload = function() {
            var reloader = selfoss.dbOffline.reloadList;

            // tag, source and search filtering not supported offline (yet?)
            if (selfoss.filter.tag || selfoss.filter.source
                || selfoss.filter.search) {
                reloader = selfoss.dbOnline.reloadList;
            }

            var forceLoadOnline = selfoss.dbOffline.olderEntriesOnline || selfoss.dbOffline.shouldLoadEntriesOnline;
            if (!selfoss.db.enableOffline || (selfoss.db.online && forceLoadOnline)) {
                reloader = selfoss.dbOnline.reloadList;
            }

            reloader().then(function() {
                selfoss.ui.afterReloadList(!append);
            });
        };

        if (waitForSync && selfoss.dbOnline.syncing.promise) {
            selfoss.db.userWaiting = true;
            selfoss.dbOnline.syncing.promise.finally(reload);
        } else {
            reload();
        }
    }


};
