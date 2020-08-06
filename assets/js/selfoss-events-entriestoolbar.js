import selfoss from './selfoss-base';
import * as ajax from './helpers/ajax';

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

    // open in new window
    parent.find('.entry-newwindow').unbind('click').click(function(e) {
        window.open($(this).parents('.entry').children('.entry-datetime').attr('href'));
        e.preventDefault();
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

            // update statistics in main menue
            var updateStats = function(starr) {
                var starred = parseInt($('.nav-filter-starred span.count').html());
                if (starr) {
                    starred++;
                } else {
                    starred--;
                }
                $('.nav-filter-starred span').html(starred);
            };
            updateStats(starr);

            if (selfoss.db.storage) {
                selfoss.dbOffline.entryStar(id, starr);
            }

            ajax.post(`${starr ? 'starr' : 'unstarr'}/${id}`).promise.then(() => {
                selfoss.db.setOnline();
            }).catch((error) => {
                selfoss.handleAjaxError(error?.response?.status || 0).then(function() {
                    selfoss.dbOffline.enqueueStatus(id, 'starred', starr);
                }).catch(function() {
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
            var updateStats = function(unread) {
                // update all unread counters
                var unreadstats = parseInt($('.nav-filter-unread span.count').html());
                var diff = unread ? -1 : 1;

                selfoss.refreshUnread(unreadstats + diff);

                // update unread on tags and sources
                var entryTags = [];
                $(`.entry[data-entry-id=${id}] .entry-tags-tag`).each(function() {
                    entryTags.push({tag: $(this).html(), count: diff});
                });
                selfoss.ui.refreshTagSourceUnread(
                    entryTags,
                    [{source: entry.attr('data-entry-source'), count: diff}]
                );
            };
            updateStats(unread);

            if (selfoss.db.storage) {
                selfoss.dbOffline.entryMark(id, !unread);
            }

            ajax.post(`${unread ? 'mark' : 'unmark'}/${id}`).promise.then(() => {
                selfoss.db.setOnline();
            }).catch((error) => {
                selfoss.handleAjaxError(error?.response?.status || 0).then(function() {
                    selfoss.dbOffline.enqueueStatus(id, 'unread', !unread);
                }).catch(function() {
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
