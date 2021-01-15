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

import selfoss from './selfoss-base';
import { OfflineStorageNotAvailableError } from './errors';
import { FilterType } from './Filter';
import { ValueListenable } from './helpers/ValueListenable';
import { LoadingState } from './requests/LoadingState';

selfoss.db = {

    /** When an error occurs we disable the offline mode and mark the database as broken so it can be retried. */
    broken: false,
    storage: null,
    online: true,
    enableOffline: new ValueListenable(window.localStorage.getItem('enableOffline') === 'true'),
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
        if (selfoss.loggedin.value && shouldSync) {
            if (selfoss.db.enableOffline.value) {
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

        selfoss.entriesPage?.setLoadingState(LoadingState.LOADING);

        var reload = function() {
            let reloader = selfoss.dbOffline.reloadList;

            // tag, source and search filtering not supported offline (yet?)
            if (selfoss.filter.tag || selfoss.filter.source
                || selfoss.filter.search) {
                reloader = selfoss.dbOnline.reloadList;
            }

            var forceLoadOnline = selfoss.dbOffline.olderEntriesOnline || selfoss.dbOffline.shouldLoadEntriesOnline;
            if (!selfoss.db.enableOffline.value || (selfoss.db.online && forceLoadOnline)) {
                reloader = selfoss.dbOnline.reloadList;
            }

            selfoss.entriesPage?.setLoadingState(LoadingState.LOADING);
            reloader().then(({ entries, hasMore }) => {
                selfoss.entriesPage.setLoadingState(LoadingState.SUCCESS);
                selfoss.entriesPage.setHasMore(hasMore);

                if (append) {
                    selfoss.entriesPage.appendEntries(entries);
                } else {
                    selfoss.entriesPage.setExpandedEntries({});
                    selfoss.entriesPage.setEntries(entries);
                }

                // open selected entry only if entry was requested (i.e. if not streaming
                // more)
                if (selfoss.events.entryId && selfoss.filter.fromId === undefined) {
                    var entry = document.querySelector(`.entry[data-entry-id="${selfoss.events.entryId}"]`);

                    if (!entry) {
                        return;
                    }

                    selfoss.ui.entryActivate(selfoss.events.entryId);
                    // ensure scrolling to requested entry even if scrolling to article
                    // header is disabled
                    if (!selfoss.config.scrollToArticleHeader) {
                        // needs to be delayed for some reason
                        requestAnimationFrame(function() {
                            entry.scrollIntoView();
                        });
                    }
                }
            }).catch(function(error) {
                selfoss.entriesPage.setLoadingState(LoadingState.FAILURE);
                selfoss.ui.showError(selfoss.ui._('error_loading') + ' ' + error.message);
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
