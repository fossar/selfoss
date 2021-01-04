import selfoss from './selfoss-base';
import * as itemsRequests from './requests/items';

/**
 * toolbar of an single entry
 */
selfoss.events.entriesToolbar = function(parent) {
    if (typeof parent == 'undefined') {
        parent = $('#content');
    }

    // prevent close on links
    parent.find('a').unbind('click').click(function(e) {
        window.open($(this).attr('href'));
        e.preventDefault();
        return false;
    });

    // load images
    parent.find('.entry-loadimages').unbind('click').click(function() {
        $(this).parents('.entry').lazyLoadImages();
        $(this).fadeOut();
        return false;
    });

    // next item on tablet
    parent.find('.entry-toolbar .entry-next').unbind('click').click(function() {
        selfoss.shortcuts.nextprev('next', true);
        return false;
    });

    // next item on smartphone
    parent.find('.entry-smartphone-share .entry-next').unbind('click').click(function() {
        /**
         * Next button can be only accessed from selected element.
         * @type {!jQuery wrapped Element}
         */
        var selected = selfoss.ui.entryGetSelected();
        selfoss.ui.entryDeactivate(selected);

        var next = selected.next('.entry');
        selfoss.ui.entryActivate(next);

        return false;
    });

    // configure shares
    let shares = selfoss.shares.getAll();
    if (shares.length > 0) {
        if (parent.find('.entry-toolbar').has('button.entry-share' + shares[0].name).length == 0) {
            // add the share toolbar entries
            parent.find('.entry-smartphone-share button.entry-newwindow').parent().after(selfoss.shares.buildLinks(shares, ({name, label, icon}) => {
                return `<li>
                    <button type="button" class="entry-share entry-share${name}" title="${label}" aria-label="${label}">${icon} ${label}</button>
                </li>`;
            }));
            parent.find('.entry-toolbar button.entry-next').parent().after(selfoss.shares.buildLinks(shares, ({name, label, icon}) => {
                return `<li>
                    <button type="button" class="entry-share entry-share${name}" title="${label}" aria-label="${label}">${icon}</button>
                </li>`;
            }));
            // hookup the share icon click events
            for (let {name} of shares) {
                parent.find(`.entry-share${name}`).unbind('click').click(function(e) {
                    let entry = $(this).parents('.entry');
                    selfoss.shares.share(name, {
                        id: entry.data('entry-id'),
                        url: entry.data('entry-url'),
                        title: entry.find('.entry-title-link').text()
                    });
                    e.preventDefault();
                    return false;
                });
            }
        }
    }

    // only loggedin users
    if ($('body').hasClass('loggedin') == true) {
        // starr/unstarr
        parent.find('.entry-starr').unbind('click').click(function() {
            var parent = $(this).parents('.entry');
            var id = parent.attr('data-entry-id');
            var starr = $(this).hasClass('active') == false;

            selfoss.ui.entryStar(id, starr);

            // update statistics in main menu
            function updateStats(starr) {
                const starred = selfoss.starredItemsCount.value;
                if (starr) {
                    selfoss.starredItemsCount.update(starred + 1);
                } else {
                    selfoss.starredItemsCount.update(starred - 1);
                }
            }
            updateStats(starr);

            if (selfoss.db.enableOffline) {
                selfoss.dbOffline.entryStar(id, starr);
            }

            itemsRequests.starr(id, starr).then(() => {
                selfoss.db.setOnline();
            }).catch(function(error) {
                selfoss.handleAjaxError(error).then(function() {
                    selfoss.dbOffline.enqueueStatus(id, 'starred', starr);
                }).catch(function(error) {
                    // rollback ui changes
                    selfoss.ui.entryStar(id, !starr);
                    updateStats(!starr);
                    selfoss.ui.showError(selfoss.ui._('error_star_item') + ' ' + error.message);
                });
            });

            return false;
        });

        // read/unread
        parent.find('.entry-unread').unbind('click').click(function() {
            var entry = $(this).parents('.entry');
            var id = entry.attr('data-entry-id');
            var unread = $(this).hasClass('active') == true;

            selfoss.ui.entryMark(id, !unread);

            // update statistics in main menue and the currently active tag
            function updateStats(unread) {
                // update all unread counters
                const unreadstats = selfoss.unreadItemsCount.value;
                const diff = unread ? -1 : 1;

                selfoss.refreshUnread(unreadstats + diff);

                // update unread on tags and sources
                var entryTags = {};
                $(`.entry[data-entry-id="${id}"] .entry-tags-tag`).each(function() {
                    // Only a single instance of each tag per entry so we can just assign.
                    entryTags[$(this).html()] = diff;
                });
                selfoss.ui.refreshTagSourceUnread(
                    entryTags,
                    {[entry.attr('data-entry-source')]: diff}
                );
            }
            updateStats(unread);

            if (selfoss.db.enableOffline) {
                selfoss.dbOffline.entryMark(id, !unread);
            }

            itemsRequests.mark(id, !unread).then(() => {
                selfoss.db.setOnline();
            }).catch(function(error) {
                selfoss.handleAjaxError(error).then(function() {
                    selfoss.dbOffline.enqueueStatus(id, 'unread', !unread);
                }).catch(function(error) {
                    // rollback ui changes
                    selfoss.ui.entryMark(id, unread);
                    updateStats(!unread);
                    selfoss.ui.showError(selfoss.ui._('error_mark_item') + ' ' + error.message);
                });
            });

            return false;
        });
    }
};
