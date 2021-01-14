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
import { OfflineStorageNotAvailableError } from './errors';
import { FilterType } from './Filter';
import Item from './templates/Item';

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
        if (selfoss.loggedin.value && shouldSync) {
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
            let reloader = selfoss.dbOffline.reloadList;

            // tag, source and search filtering not supported offline (yet?)
            if (selfoss.filter.tag || selfoss.filter.source
                || selfoss.filter.search) {
                reloader = selfoss.dbOnline.reloadList;
            }

            var forceLoadOnline = selfoss.dbOffline.olderEntriesOnline || selfoss.dbOffline.shouldLoadEntriesOnline;
            if (!selfoss.db.enableOffline || (selfoss.db.online && forceLoadOnline)) {
                reloader = selfoss.dbOnline.reloadList;
            }

            reloader().then(({ entries, hasMore }) => {
                const firstPage = typeof selfoss.filter.fromId === 'undefined' && typeof selfoss.filter.fromDatetime === 'undefined';
                const allowedToUpdate = !selfoss.config.authEnabled || selfoss.config.allowPublicUpdate || selfoss.loggedin.value;

                let content = $('#content');
                let newContent = content.clone().empty();
                if (selfoss.filter.source && allowedToUpdate && firstPage && reloader === selfoss.dbOnline.reloadList) {
                    newContent.append(<button type="button" id="refresh-source" class="refresh-source">{selfoss.ui._('source_refresh')}</button>);
                }
                newContent.append(entries.map(entry => <Item item={entry} />));

                if (!firstPage) {
                    content.append(newContent.children());
                } else {
                    content.replaceWith(newContent);
                }

                selfoss.ui.refreshStreamButtons(true, hasMore);

                selfoss.ui.afterReloadList(!append);
            }).catch(function(error) {
                selfoss.ui.showError(selfoss.ui._('error_loading') + ' ' + error.message);
                selfoss.events.entries();
                selfoss.ui.refreshStreamButtons();
                $('.stream-error').show();
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
