import selfoss from './selfoss-base';
import * as itemsRequests from './requests/items';
import { LoadingState } from './requests/LoadingState';
import { FilterType } from './Filter';
import {
    ResponseItem,
    StatusUpdate,
    SyncParams,
    SyncResponse,
} from './requests/items';

export type FetchParams = {
    type: FilterType;
    tag: string | null;
    source: number | null;
    extraIds: number[];
    sourcesNav: boolean;
    search: string | null;
    fromDatetime: Date | null;
    fromId: number | null;
};

export default class DbOnline {
    public syncing: {
        promise: Promise<void> | null;
        request: {
            promise: Promise<SyncResponse>;
            controller: AbortController;
        } | null;
        resolve: () => void | null;
        reject: () => void | null;
    } = {
        promise: null,
        request: null,
        resolve: null,
        reject: null,
    };
    public statsDirty: boolean = false;
    public firstSync: boolean = true;

    _syncBegin(): Promise<void> {
        if (!this.syncing.promise) {
            this.syncing.promise = new Promise((resolve, reject) => {
                this.syncing.resolve = resolve;
                this.syncing.reject = reject;
                const monitor = window.setInterval(() => {
                    let stopChecking = false;
                    if (this.syncing.promise) {
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

            this.syncing.promise.finally(() => {
                this.syncing.promise = null;
                selfoss.db.userWaiting = false;
            });
        }

        return this.syncing.promise;
    }

    _syncDone(success: boolean = true): void {
        if (this.syncing.promise) {
            if (success) {
                this.syncing.resolve();
            } else {
                const request = this.syncing.request;
                this.syncing.reject();
                if (request) {
                    request.controller.abort();
                }
            }
        }
    }

    /**
     * sync server status.
     */
    sync(
        updatedStatuses: Array<StatusUpdate> | undefined = undefined,
        chained: boolean = false,
    ): Promise<void> {
        if (this.syncing.promise && !chained) {
            if (updatedStatuses) {
                // Ensure the status queue is not cleared and gets sync'ed at
                // next sync.
                return Promise.reject();
            } else {
                return this.syncing.promise;
            }
        }

        const syncing = this._syncBegin();

        let getStatuses = true;
        if (selfoss.db.lastUpdate === null || this.firstSync) {
            selfoss.db.lastUpdate = new Date(0);
            getStatuses = undefined;
        }

        const syncParams: SyncParams = {
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

        this.statsDirty = false;

        this.syncing.request = itemsRequests.sync(updatedStatuses, syncParams);

        this.syncing.request.promise
            .then((data) => {
                selfoss.db.setOnline();

                selfoss.db.lastSync = Date.now();
                this.firstSync = false;

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
                                this._syncDone();
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

                if (!this.statsDirty && 'stats' in data) {
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
                        selfoss.entriesPage.state.loadingState ===
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
                                ({ unread }) => unread,
                            ).length
                        ) {
                            selfoss.entriesPage.setHasMore(true);
                        }
                    }
                }

                selfoss.db.lastUpdate = dataDate;

                if (!storing) {
                    this._syncDone();
                }
            })
            .catch((error) => {
                this._syncDone(false);
                selfoss.handleAjaxError(error).catch((error) => {
                    selfoss.app.showError(
                        selfoss.app._('error_sync') + ' ' + error.message,
                    );
                });
            })
            .finally(() => {
                if (this.syncing.promise) {
                    this.syncing.request = null;
                }
            });

        return syncing;
    }

    /**
     * refresh current items.
     */
    getEntries(
        fetchParams: FetchParams,
        abortController: AbortController,
    ): Promise<{ entries: ResponseItem[]; hasMore: boolean }> {
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
    }
}
