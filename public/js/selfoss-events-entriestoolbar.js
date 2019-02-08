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

        // os ext 2018-12-25 "read up-to-here" / code taken from read/unread below and selfoss.markVisibleRead
        // this is not available in fullscreen mode -> display:none in css
        // 2019-02-03 changed to NOT include the entry this is called from
        // because (1) to find the cmd, the entry is open = read anyway and
        // (2) if the entry has been marked "unread" by the user, we can assume
        // he / she did not want it to be marked read by this.
        parent.find('.entry-readuptohere').unbind('click').click(function() {
            var ids = [];
            var entry = $(this).parents('.entry');
            // // add this one - regardless of whether it is already read or not
            // 2019-02-03 > NO!, see above
            // ids.push( entry.attr('data-entry-id') );
            entry.prevAll('.entry.unread').each(function(index, item) {
            //entry.prevUntil("fff").each( function(index, item){
                ids.push($(item).attr('data-entry-id'));
            // ids.push($(item).attr('id').substr(5));
            });
            // there will always be at least one element: the current one
            // 2019-02-03 NO: with the change above, ids[] could be empty


            // this is from markVisibleRead

            // show loading
            var content = $('#content');
            var articleList = content.html();
            $('#content').addClass('loading').html('');
            var hadMore = $('.stream-more').is(':visible');
            selfoss.ui.refreshStreamButtons();

            // close opened entry and list
            selfoss.events.setHash();
            selfoss.filterReset();

            // this is MODIFIED from markVisibleRead > mark + reload list from server
            // mark fails with empty ids[] list

            if (ids.length > 0) {
                $.ajax({
                    url: $('base').attr('href') + 'mark',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        ids: ids
                    },
                    success: function() {
                        // refresh list
                        selfoss.dbOnline.reloadList();
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        content.html(articleList);
                        $('#content').removeClass('loading');
                        selfoss.ui.refreshStreamButtons(true, true, hadMore);
                        selfoss.events.entries();
                        selfoss.ui.showError('Can not mark all previous items: ' +
                                             textStatus + ' ' + errorThrown);
                    }
                });
            } else {
                // ids[] was empty, refresh list anyway
                selfoss.dbOnline.reloadList();
            }
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
