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


selfoss.dbOnline = {


    syncing: false,
    statsDirty: false,
    firstSync: true,


    _syncBegin: function() {
        if (!selfoss.dbOnline.syncing) {
            selfoss.dbOnline.syncing = $.Deferred();
            selfoss.dbOnline.syncing.always(function() {
                selfoss.dbOnline.syncing = false;
                selfoss.db.userWaiting = false;
            });

            var monitor = window.setInterval(function() {
                var stopChecking = false;
                if (selfoss.dbOnline.syncing) {
                    if (selfoss.db.userWaiting) {
                        // reject if user has been waiting for more than 10s,
                        // this means that connectivity is bad: user will get
                        // local content and server request will continue in
                        // the background.
                        selfoss.dbOnline.syncing.reject();
                        stopChecking = true;
                    }
                } else {
                    stopChecking = true;
                }

                if (stopChecking) {
                    window.clearInterval(monitor);
                }
            }, 10000);
        }

        return selfoss.dbOnline.syncing;
    },


    _syncDone: function(success) {
        success = (typeof success !== 'undefined') ? success : true;

        if (selfoss.dbOnline.syncing) {
            if (success) {
                selfoss.dbOnline.syncing.resolve();
            } else {
                var request = selfoss.dbOnline.syncing.request;
                selfoss.dbOnline.syncing.reject();
                if (request) {
                    request.abort();
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
        if (selfoss.dbOnline.syncing && !chained) {
            if (updatedStatuses) {
                // Ensure the status queue is not cleared and gets sync'ed at
                // next sync.
                var d = $.Deferred();
                d.reject();
                return d;
            } else {
                return selfoss.dbOnline.syncing;
            }
        }

        var syncing = selfoss.dbOnline._syncBegin();

        var getStatuses = true;
        if (selfoss.db.lastUpdate === null || selfoss.dbOnline.firstSync) {
            selfoss.db.lastUpdate = new Date(0);
            getStatuses = undefined;
        }

        var syncParams = {
            since: selfoss.db.lastUpdate.toISOString(),
            tags: true,
            sources: selfoss.filter.sourcesNav ? true : undefined,
            itemsStatuses: getStatuses
        };

        if (updatedStatuses && updatedStatuses.length > 0) {
            syncParams.updatedStatuses = updatedStatuses;
        }

        if (selfoss.db.storage) {
            syncParams.itemsSinceId = selfoss.dbOffline.lastItemId;
            syncParams.itemsNotBefore = selfoss.dbOffline.newestGCedEntry.toISOString();
            syncParams.itemsHowMany = selfoss.filter.itemsPerPage;
        }

        selfoss.dbOnline.statsDirty = false;

        syncing.request = $.ajax({
            url: 'items/sync',
            type: updatedStatuses ? 'POST' : 'GET',
            dataType: 'json',
            data: syncParams,
            success: function(data) {
                selfoss.db.setOnline();

                selfoss.db.lastSync = Date.now();
                selfoss.dbOnline.firstSync = false;

                var dataDate = new Date(data.lastUpdate);

                var storing = false;

                if (selfoss.db.storage) {
                    if ('newItems' in data) {
                        var maxId = 0;
                        data.newItems.forEach(function(item) {
                            item.datetime = new Date(item.datetime);
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

                                // fetch more if server has more
                                if (selfoss.dbOffline.newerEntriesMissing) {
                                    selfoss.dbOnline.sync();
                                }
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

                if ('tagshtml' in data) {
                    selfoss.refreshTags(data.tagshtml);
                }

                if ('sourceshtml' in data) {
                    selfoss.refreshSources(data.sourceshtml);
                }

                if ('stats' in data && data.stats.unread > 0 &&
                    ($('.stream-empty').is(':visible') ||
                    $('.stream-error').is(':visible'))) {
                    selfoss.db.reloadList();
                } else {
                    if ('itemUpdates' in data) {
                        selfoss.ui.refreshEntryStatuses(data.itemUpdates);
                    }

                    if (selfoss.filter.type == 'unread') {
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
            },
            error: function(jqXHR, textStatus, errorThrown) {
                selfoss.dbOnline._syncDone(false);
                selfoss.handleAjaxError(jqXHR.status).fail(function() {
                    selfoss.ui.showError($('#lang').data('error_sync') + ' ' +
                                         textStatus + ' ' + errorThrown);
                });
            },
            complete: function() {
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
            selfoss.activeAjaxReq.abort();
        }

        selfoss.activeAjaxReq = $.ajax({
            url: $('base').attr('href'),
            type: 'GET',
            dataType: 'json',
            data: selfoss.filter,
            success: function(data) {
                selfoss.db.setOnline();

                if (!selfoss.db.storage) {
                    selfoss.db.lastSync = Date.now();
                    selfoss.db.lastUpdate = new Date(data.lastUpdate);
                }

                selfoss.refreshStats(data.all, data.unread, data.starred);

                $('#content').append(data.entries);
                selfoss.ui.refreshStreamButtons(true, data.hasMore);

                // update tags
                selfoss.refreshTags(data.tags);

                // drop loaded sources
                var currentSource = -1;
                if (selfoss.sourcesNavLoaded) {
                    currentSource = $('#nav-sources li').index($('#nav-sources .active'));
                    $('#nav-sources li').remove();
                    selfoss.sourcesNavLoaded = false;
                }
                if (selfoss.filter.sourcesNav) {
                    selfoss.refreshSources(data.sources, currentSource);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                if (textStatus == 'abort') {
                    return;
                }

                selfoss.handleAjaxError(jqXHR.status).then(function() {
                    selfoss.dbOffline.reloadList();
                    selfoss.ui.afterReloadList();
                }, function() {
                    selfoss.ui.showError($('#lang').data('error_loading') +
                                         ' ' + textStatus + ' ' + errorThrown);
                    selfoss.events.entries();
                    selfoss.ui.refreshStreamButtons();
                    $('.stream-error').show();
                });
            },
            complete: function() {
                // clean up
                selfoss.activeAjaxReq = null;
            }
        });

        return selfoss.activeAjaxReq;
    }


};


selfoss.dbOffline = {


    // the datetime of the newest garbage collected entry, i.e. deleted
    // because not of interest.
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
                selfoss.ui.showError(selfoss.ui._('error_offline_storage', [error.message]));
                selfoss.db.storage = null;
                selfoss.db.reloadList();

                // If this is a QuotaExceededError, garbage collect more
                // entries and hope it helps.
                if (error.name === Dexie.errnames.QuotaExceeded) {
                    selfoss.dbOffline.GCEntries(true);
                }

                throw (error);
            });
    },


    init: function() {
        if (!selfoss.db.enableOffline) {
            var d = $.Deferred();
            d.catch = function(fn) {
                d.then(null, fn);
            };
            d.reject();
            return d;
        }

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
                    selfoss.db.setOffline();
                });

                selfoss.ui.setOnline();
                $('#content').addClass('loading');
                selfoss.db.tryOnline()
                    .then(function() {
                        selfoss.reloadTags();
                    })
                    .always(selfoss.events.init);
                selfoss.dbOffline.reloadOnlineStats();
                selfoss.dbOffline.refreshStats();
            }).catch(function() {
                selfoss.db.storage = null;
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


    GCEntries: function(more) {
        more = (typeof more !== 'undefined') ? more : false;

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
                for (var stat in stats) {
                    if (stats.hasOwnProperty(stat)) {
                        selfoss.db.storage.stats.put({
                            name: stat,
                            value: stats[stat]
                        });
                    }
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
                    fromDatetime = new Date(fromDatetime);
                }
                var isMore = false;
                var alwaysInDb = selfoss.filter.type === 'starred'
                             || selfoss.filter.type === 'unread';

                entries.filter(function(entry) {
                    if (selfoss.filter.extraIds.indexOf(entry.id) > -1) {
                        return true;
                    }

                    if (selfoss.filter.type == 'starred') {
                        return entry.starred;
                    } else if (selfoss.filter.type == 'unread') {
                        return entry.unread;
                    }

                    return true;
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
                        newContent.append(entry.html);
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

        var newQueuedStatuses = [];
        var d = new Date().toISOString();
        statuses.forEach(function(newStatus) {
            newQueuedStatuses.push({
                entryId: parseInt(newStatus.entryId),
                name: newStatus.name,
                value: newStatus.value,
                datetime: d
            });
        });

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
        var statuses = [];
        selfoss.dbOffline._tr('r', selfoss.db.storage.statusq, function() {
            selfoss.db.storage.statusq.each(function(s) {
                var statusUpdate = {
                    id: s.entryId,
                    datetime: s.datetime
                };
                statusUpdate[s.name] = s.value;
                statuses.push(statusUpdate);
            });
        }).then(function() {
            var s = undefined;
            if (statuses.length > 0) {
                s = statuses;
            }
            selfoss.dbOnline.sync(s, true).then(function() {
                selfoss.dbOffline.needsSync = false;
            });
        });

        return selfoss.dbOnline._syncBegin();
    },


    storeEntryStatuses: function(itemStatuses, dequeue, updateStats) {
        dequeue = (typeof dequeue !== 'undefined') ? dequeue : false;
        updateStats = (typeof updateStats !== 'undefined') ? updateStats : true;

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
                    for (var statusName in statsDiff) {
                        if (statsDiff.hasOwnProperty(statusName)) {
                            selfoss.db.storage.stats.get(statusName, function(stat) {
                                selfoss.db.storage.stats.put({
                                    name: statusName,
                                    value: stat.value + statsDiff[statusName]
                                });
                            });
                        }
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
        var d = $.Deferred();

        if (selfoss.db.storage) {
            selfoss.dbOnline._syncDone(false);
            d.resolve();
            selfoss.db.online = false;
            selfoss.ui.setOffline();
        } else {
            d.reject();
        }
        return d;
    },


    clear: function() {
        if (selfoss.db.storage) {
            window.localStorage.removeItem('offlineDays');
            var clearing = selfoss.db.storage.delete();
            selfoss.db.storage = null;
            selfoss.db.lastUpdate = null;
            return clearing;
        } else {
            var d = $.Deferred();
            d.resolve();
            return d;
        }
    },


    isValidTag: function(tag) {
        var isValid = false;
        $('#nav-tags > li:not(:first)').each(function() {
            isValid = $('.tag', this).html() == tag;
            return !isValid; // break the loop if valid
        });
        return isValid;
    },


    isValidSource: function(id) {
        var isValid = false;
        $('#nav-sources > li').each(function() {
            isValid = $(this).data('source-id') == id;
            return !isValid; // break the loop if valid
        });
        return isValid;
    },


    ascOrder: function() {
        return $('#config').data('unread_order') == 'asc'
               && selfoss.filter.type == 'unread';
    },


    lastSync: null,


    sync: function(force) {
        force = (typeof force !== 'undefined') ? force : false;

        var lastUpdateIsOld = selfoss.db.lastUpdate === null || selfoss.db.lastSync === null || Date.now() - selfoss.db.lastSync > 5 * 60 * 1000;
        var shouldSync = force || selfoss.dbOffline.needsSync || lastUpdateIsOld;
        if (selfoss.loggedin && shouldSync) {
            if (selfoss.db.storage) {
                return selfoss.dbOffline.sendNewStatuses();
            } else {
                return selfoss.dbOnline.sync();
            }
        } else {
            return $.Deferred().resolve(); // ensure any chained function runs
        }
    },


    reloadList: function(append, waitForSync) {
        append = (typeof append !== 'undefined') ? append : false;
        waitForSync = (typeof waitForSync !== 'undefined') ? waitForSync : true;

        if (location.hash == '#sources') {
            return;
        }

        if (selfoss.events.entryId && selfoss.filter.fromId === undefined) {
            selfoss.filter.extraIds.push(selfoss.events.entryId);
        }

        if (!append || selfoss.filter.type != 'newest') {
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
            if (!selfoss.db.storage || (selfoss.db.online && forceLoadOnline)) {
                reloader = selfoss.dbOnline.reloadList;
            }

            reloader().then(function() {
                selfoss.ui.afterReloadList(!append);
            });
        };

        if (waitForSync && selfoss.dbOnline.syncing) {
            selfoss.db.userWaiting = true;
            selfoss.dbOnline.syncing.always(reload);
        } else {
            reload();
        }
    }


};
