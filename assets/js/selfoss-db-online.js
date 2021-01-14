import React from 'jsx-dom';
import selfoss from './selfoss-base';
import * as itemsRequests from './requests/items';
import Item from './templates/Item';
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
                        unreadCount = selfoss.unreadItemsCount.value;
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

            // update tags
            selfoss.tags.update(data.tags);

            if (selfoss.filter.sourcesNav) {
                selfoss.sources.update(data.sources);
            }

            return {
                entries: data.entries,
                hasMore: data.hasMore
            };
        }).catch((error) => {
            if (error.name == 'AbortError') {
                return;
            }

            return selfoss.handleAjaxError(error).then(function() {
                return selfoss.dbOffline.reloadList();
            });
        }).finally(() => {
            // clean up
            selfoss.activeAjaxReq = null;
        });

        return promise;
    }


};
