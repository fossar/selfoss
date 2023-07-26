import selfoss from './selfoss-base';
import * as itemsRequests from './requests/items';
import { LoadingState } from './requests/LoadingState';
import { FilterType } from './Filter';

selfoss.dbOnline = {
    syncing: {
        promise: null,
        request: null,
        resolve: null,
        reject: null,
    },
    statsDirty: false,
    firstSync: true,

    _syncBegin() {
        if (!selfoss.dbOnline.syncing.promise) {
            selfoss.dbOnline.syncing.promise = new Promise(
                (resolve, reject) => {
                    selfoss.dbOnline.syncing.resolve = resolve;
                    selfoss.dbOnline.syncing.reject = reject;
                    const monitor = window.setInterval(() => {
                        let stopChecking = false;
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
                },
            );

            selfoss.dbOnline.syncing.promise.finally(() => {
                selfoss.dbOnline.syncing.promise = null;
                selfoss.db.userWaiting = false;
            });
        }

        return selfoss.dbOnline.syncing.promise;
    },

    _syncDone(success = true) {
        if (selfoss.dbOnline.syncing.promise) {
            if (success) {
                selfoss.dbOnline.syncing.resolve();
            } else {
                const request = selfoss.dbOnline.syncing.request;
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
    sync(updatedStatuses, chained) {
        if (selfoss.dbOnline.syncing.promise && !chained) {
            if (updatedStatuses) {
                // Ensure the status queue is not cleared and gets sync'ed at
                // next sync.
                return Promise.reject();
            } else {
                return selfoss.dbOnline.syncing.promise;
            }
        }

        const syncing = selfoss.dbOnline._syncBegin();

        let getStatuses = true;
        if (selfoss.db.lastUpdate === null || selfoss.dbOnline.firstSync) {
            selfoss.db.lastUpdate = new Date(0);
            getStatuses = undefined;
        }

        const syncParams = {
            since: selfoss.db.lastUpdate,
            tags: true,
            sources: selfoss.app.state.navSourcesExpanded || undefined,
            itemsStatuses: getStatuses,
        };

        if (updatedStatuses && updatedStatuses.length > 0) {
            syncParams.updatedStatuses = updatedStatuses;
        }

        if (selfoss.db.enableOffline.value) {
            syncParams.itemsSinceId = selfoss.dbOffline.lastItemId;
            syncParams.itemsNotBefore = selfoss.dbOffline.newestGCedEntry;
            syncParams.itemsHowMany = selfoss.config.itemsPerPage;
        }

        selfoss.dbOnline.statsDirty = false;

        selfoss.dbOnline.syncing.request = itemsRequests.sync(
            updatedStatuses,
            syncParams,
        );

        selfoss.dbOnline.syncing.request.promise
            .then((data) => {
                selfoss.db.setOnline();

                selfoss.db.lastSync = Date.now();
                selfoss.dbOnline.firstSync = false;

                const dataDate = data.lastUpdate;

                let storing = false;

                if (selfoss.db.enableOffline.value) {
                    if ('newItems' in data) {
                        let maxId = 0;
                        data.newItems.forEach((item) => {
                            maxId = Math.max(item.id, maxId);
                        });

                        selfoss.dbOffline.newerEntriesMissing =
                            'lastId' in data &&
                            data.lastId > selfoss.dbOffline.lastItemId &&
                            data.lastId > maxId;
                        storing = selfoss.dbOffline.newerEntriesMissing;

                        selfoss.dbOffline.shouldLoadEntriesOnline =
                            'lastId' in data &&
                            data.lastId - selfoss.dbOffline.lastItemId >
                                2 * selfoss.config.itemsPerPage;

                        selfoss.dbOffline
                            .storeEntries(data.newItems)
                            .then(() => {
                                selfoss.dbOffline.storeLastUpdate(dataDate);
                                selfoss.dbOnline._syncDone();
                            });
                    }

                    if (
                        selfoss.dbOffline.newerEntriesMissing ||
                        selfoss.dbOffline.needsSync
                    ) {
                        // There are still new items to fetch
                        // or statuses to send
                        syncing.then(() => {
                            selfoss.dbOffline.sendNewStatuses();
                        });
                    }

                    if ('itemUpdates' in data) {
                        // refresh entry statuses in db and dequeue queued
                        // statuses but do not calculate stats as they are taken
                        // directly from the server as provided.
                        selfoss.dbOffline
                            .storeEntryStatuses(data.itemUpdates, true, false)
                            .then(() => {
                                selfoss.dbOffline.storeLastUpdate(dataDate);
                            });
                    }

                    if ('stats' in data) {
                        selfoss.dbOffline.storeStats(data.stats);
                    }
                }

                if (!selfoss.dbOnline.statsDirty && 'stats' in data) {
                    selfoss.refreshStats(
                        data.stats.total,
                        data.stats.unread,
                        data.stats.starred,
                    );
                }

                if ('tags' in data) {
                    selfoss.app.setTags(data.tags);
                    selfoss.app.setTagsState(LoadingState.SUCCESS);
                }

                if ('sources' in data) {
                    selfoss.app.setSources(data.sources);
                    selfoss.app.setSourcesState(LoadingState.SUCCESS);
                }

                if (
                    'stats' in data &&
                    data.stats.unread > 0 &&
                    selfoss.entriesPage &&
                    (selfoss.entriesPage.state.entries.length === 0 ||
                        selfoss.entriesPage.state.entries.loadingState ===
                            LoadingState.FAILURE)
                ) {
                    selfoss.entriesPage?.reload();
                } else {
                    if ('itemUpdates' in data) {
                        selfoss.entriesPage.refreshEntryStatuses(
                            data.itemUpdates,
                        );
                    }

                    if (
                        selfoss.entriesPage &&
                        selfoss.entriesPage.getActiveFilter() ===
                            FilterType.UNREAD
                    ) {
                        const unreadCount =
                            'stats' in data
                                ? data.stats.unread
                                : selfoss.app.state.unreadItemsCount;

                        if (
                            unreadCount >
                            selfoss.entriesPage.state.entries.filter(
                                ({ unread }) => unread == 1,
                            ).length
                        ) {
                            selfoss.entriesPage.setHasMore(true);
                        }
                    }
                }

                selfoss.db.lastUpdate = dataDate;

                if (!storing) {
                    selfoss.dbOnline._syncDone();
                }
            })
            .catch((error) => {
                selfoss.dbOnline._syncDone(false);
                selfoss.handleAjaxError(error).catch((error) => {
                    selfoss.app.showError(
                        selfoss.app._('error_sync') + ' ' + error.message,
                    );
                });
            })
            .finally(() => {
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
    getEntries(fetchParams, abortController) {
        return itemsRequests
            .getItems(
                {
                    ...fetchParams,
                    itemsPerPage: selfoss.config.itemsPerPage,
                },
                abortController,
            )
            .then((data) => {
                selfoss.db.setOnline();

                if (!selfoss.db.enableOffline.value) {
                    selfoss.db.lastSync = Date.now();
                    selfoss.db.lastUpdate = data.lastUpdate;
                }

                selfoss.refreshStats(data.all, data.unread, data.starred);

                // update tags
                selfoss.app.setTags(data.tags);
                selfoss.app.setTagsState(LoadingState.SUCCESS);

                if (
                    typeof data.sources !== 'undefined' &&
                    selfoss.app.state.navSourcesExpanded
                ) {
                    selfoss.app.setSources(data.sources);
                    selfoss.app.setSourcesState(LoadingState.SUCCESS);
                }

                return {
                    entries: data.entries,
                    hasMore: data.hasMore,
                };
            })
            .catch((error) => {
                if (error.name === 'AbortError') {
                    return;
                }

                return selfoss.handleAjaxError(error).then(() => {
                    return selfoss.dbOffline.getEntries(fetchParams);
                });
            });
    },
};
