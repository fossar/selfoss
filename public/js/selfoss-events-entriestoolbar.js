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

    // next item on smartphone
    parent.find('.entry-toolbar .entry-next').unbind('click').click(function() {
        selfoss.shortcuts.nextprev('next', true);
        return false;
    });

    // next item on tablet
    parent.find('.entry-smartphone-share .entry-next').unbind('click').click(function() {
        var $selected = $('.entry.selected, .entry.fullscreen:visible');
        var id = $selected.attr('id').replace('entrr', 'entry');
        $selected.find('.entry-unread.active').click();
        $selected.find('.entry-title').click();
        $('#' + id).next('.entry').find('.entry-title').click();
        return false;
    });

    // configure shares
    var shares = selfoss.shares.getAll();
    if (shares.length > 0) {
        if (parent.find('.entry-toolbar').has('button.entry-share' + shares[0]).length == 0) {
            // add the share toolbar entries
            parent.find('.entry-smartphone-share button.entry-newwindow').after(selfoss.shares.buildLinks(shares, function(name) {
                return '<button class="entry-share entry-share' + name + '" title="' + name + '"><img class="entry-share" title="' + name + '" src="images/' + name + '.png" height="16" width="16">' + name + '</button>';
            }));
            parent.find('.entry-toolbar button.entry-next').after(selfoss.shares.buildLinks(shares, function(name) {
                return '<button class="entry-share entry-share' + name + '"><img title="' + name + '" src="images/' + name + '.png" height="16" width="16"></button>';
            }));
            // hookup the share icon click events
            for (var i = 0; i < shares.length; i++) {
                (function(share) {
                    parent.find('.entry-share' + share).unbind('click').click(function(e) {
                        var entry = $(this).parents('.entry');
                        selfoss.shares.share(share, entry.children('.entry-link').eq(0).attr('href'), entry.children('.entry-title').html());
                        e.preventDefault();
                        return false;
                    });
                })(shares[i]);
            }
        }
    }

    // only loggedin users
    if ($('body').hasClass('loggedin') == true) {
        // starr/unstarr
        parent.find('.entry-starr').unbind('click').click(function() {
            var parent = $(this).parents('.entry');
            var id = parent.attr('id').substr(5);
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

            $.ajax({
                url: $('base').attr('href') + (starr ? 'starr/' : 'unstarr/') + id,
                data: {},
                type: 'POST',
                success: function() {
                    selfoss.db.setOnline();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    selfoss.handleAjaxError(jqXHR.status).then(function() {
                        selfoss.dbOffline.enqueueStatus(id, 'starred', starr);
                    }, function() {
                        // rollback ui changes
                        selfoss.ui.entryStar(id, !starr);
                        updateStats(!starr);
                        selfoss.ui.showError($('#lang').data('error_star_item') + ' ' +
                                             textStatus + ' ' + errorThrown);
                    });
                }
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
                $('#entry' + id + ' .entry-tags-tag').each(function() {
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

            $.ajax({
                url: $('base').attr('href') + (unread ? 'mark/' : 'unmark/') + id,
                data: {},
                type: 'POST',
                success: function() {
                    selfoss.db.setOnline();
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    selfoss.handleAjaxError(jqXHR.status).then(function() {
                        selfoss.dbOffline.enqueueStatus(id, 'unread', !unread);
                    }, function() {
                        // rollback ui changes
                        selfoss.ui.entryMark(id, unread);
                        updateStats(!unread);
                        selfoss.ui.showError($('#lang').data('error_mark_item') + ' ' +
                                             textStatus + ' ' + errorThrown);
                    });
                }
            });

            return false;
        });
    }
};
