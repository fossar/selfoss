import selfoss from './selfoss-base';

selfoss.shortcuts = {


    /**
     * init shortcuts
     */
    init: function() {
        // 'space': next article
        $(document).bind('keydown', 'space', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            var selected = selfoss.ui.entryGetSelected();
            if (selected !== null && !selfoss.ui.entryIsExpanded(selected)) {
                selfoss.ui.entryActivate(selected);
            } else {
                selfoss.shortcuts.nextprev('next', true);
            }
            e.preventDefault();
            return false;
        });

        // 'n': next article
        $(document).bind('keydown', 'n', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.shortcuts.nextprev('next', false);
            e.preventDefault();
            return false;
        });

        // 'right cursor': next article
        $(document).bind('keydown', 'right', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.shortcuts.entrynav('next');
            e.preventDefault();
            return false;
        });

        // 'j': next article
        $(document).bind('keydown', 'j', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.shortcuts.nextprev('next', true);
            e.preventDefault();
            return false;
        });

        // 'shift+space': previous article
        $(document).bind('keydown', 'shift+space', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.shortcuts.nextprev('prev', true);
            e.preventDefault();
            return false;
        });

        // 'p': previous article
        $(document).bind('keydown', 'p', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.shortcuts.nextprev('prev', false);
            e.preventDefault();
            return false;
        });

        // 'left': previous article
        $(document).bind('keydown', 'left', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.shortcuts.entrynav('prev');
            e.preventDefault();
            return false;
        });

        // 'k': previous article
        $(document).bind('keydown', 'k', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.shortcuts.nextprev('prev', true);
            e.preventDefault();
            return false;
        });

        // 's': star/unstar
        $(document).bind('keydown', 's', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.events.entriesToolbar(selfoss.ui.entryGetSelected());
            $('.entry.selected .entry-starr').click();
            e.preventDefault();
            return false;
        });

        // 'm': mark/unmark
        $(document).bind('keydown', 'm', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.events.entriesToolbar(selfoss.ui.entryGetSelected());
            $('.entry.selected .entry-unread').click();
            e.preventDefault();
            return false;
        });

        // 'o': open/close entry
        $(document).bind('keydown', 'o', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.ui.entryToggleExpanded(selfoss.ui.entryGetSelected());
            e.preventDefault();
            return false;
        });

        // 'Shift + o': close open entries
        $(document).bind('keydown', 'Shift+o', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            e.preventDefault();
            selfoss.ui.entryCollapseAll();
        });

        // 'v': open target
        $(document).bind('keydown', 'v', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            window.open($('.entry.selected .entry-datetime').attr('href'));
            e.preventDefault();
            return false;
        });

        // 'Shift + v': open target and mark read
        $(document).bind('keydown', 'Shift+v', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            e.preventDefault();

            selfoss.events.entriesToolbar(selfoss.ui.entryGetSelected());

            // mark item as read
            if ($('.entry.selected .entry-unread').hasClass('active')) {
                $('.entry.selected .entry-unread').click();
            }

            // open item in new window
            $('.entry.selected .entry-datetime').click();
        });

        // 'r': Reload the current view
        $(document).bind('keydown', 'r', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            e.preventDefault();
            $('#nav-filter-unread').click();
        });

        // 'Shift + r': Refresh sources
        $(document).bind('keydown', 'Shift+r', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            e.preventDefault();
            $('#nav-refresh').click();
        });

        // 'Ctrl+m': mark all as read
        $(document).bind('keydown', 'ctrl+m', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            $('#nav-mark').click();
            e.preventDefault();
            return false;
        });

        // 't': throw (mark as read & open next)
        $(document).bind('keydown', 't', function() {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            $('.entry.selected.unread .entry-unread').click();
            selfoss.shortcuts.nextprev('next', true);
            return false;
        });

        // throw (mark as read & open previous)
        $(document).bind('keydown', 'Shift+t', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            $('.entry.selected.unread .entry-unread').click();
            selfoss.shortcuts.nextprev('prev', true);
            e.preventDefault();
            return false;
        });

        // 'Shift+n': switch to newest items overview / menu item
        $(document).bind('keydown', 'Shift+n', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            e.preventDefault();
            $('#nav-filter-newest').click();
        });

        // 'Shift+u': switch to unread items overview / menu item
        $(document).bind('keydown', 'Shift+u', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            e.preventDefault();
            $('#nav-filter-unread').click();
        });

        // 'Shift+s': switch to starred items overview / menu item
        $(document).bind('keydown', 'Shift+s', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            e.preventDefault();
            $('#nav-filter-starred').click();
        });
    },


    /**
     * get next/prev item
     * @param direction
     */
    nextprev: function(direction, open) {
        if (typeof direction == 'undefined' || (direction != 'next' && direction != 'prev')) {
            direction = 'next';
        }

        // select current
        var old = selfoss.ui.entryGetSelected();
        var current = null;

        // select next/prev entry and save it to "current"
        // if we would overflow, we stay on the old one
        if (direction == 'next') {
            if (old === null) {
                current = $('.entry:eq(0)');
            } else {
                // need to use nextAll because jquery.keyboardtrap adds a div after the current element
                if (old.nextAll('.entry:eq(0)').length === 0) {
                    current = old;

                    // attempt to load more
                    $('.stream-more').click();
                } else {
                    current = old.nextAll('.entry:eq(0)');
                }
            }
        } else {
            if (old === null) {
                return;
            } else {
                // need to use prevAll because jquery.keyboardtrap adds a div before the current element
                current = old.prevAll('.entry:eq(0)').length == 0 ? old : old.prevAll('.entry:eq(0)');
            }
        }

        // when there are no entries
        if (current.length == 0) {
            return;
        }

        if (old !== current) {
            // remove active
            selfoss.ui.entryDeactivate(old);

            if (open) {
                selfoss.ui.entryActivate(current);
            } else {
                selfoss.ui.entrySelect(current);
            }

            // scroll to element
            selfoss.shortcuts.autoscroll(current);

            // focus the title link for better keyboard navigation
            current.find('.entry-title-link').focus();
        }
    },


    /**
     * autoscroll
     */
    autoscroll: function(next) {
        var viewportHeight = $(window).height();
        var viewportScrollTop = $(window).scrollTop();

        // scroll down
        if (viewportScrollTop + viewportHeight < next.position().top + next.height() + 80) {
            if (next.height() > viewportHeight) {
                $(window).scrollTop(next.position().top);
            } else {
                var marginTop = (viewportHeight - next.height()) / 2;
                var scrollTop = next.position().top - marginTop;
                $(window).scrollTop(scrollTop);
            }
        }

        // scroll up
        if (next.position().top <= viewportScrollTop) {
            $(window).scrollTop(next.position().top);
        }
    },


    /**
     * entry navigation (next/prev) with keys
     * @param direction
     */
    entrynav: function(direction) {
        if (typeof direction == 'undefined' || (direction != 'next' && direction != 'prev')) {
            direction = 'next';
        }

        var content = selfoss.ui.entryIsExpanded($('.entry'));
        selfoss.shortcuts.nextprev(direction, content);
    },

    /**
     * Check whether keyboard shortcuts should be active
     */
    active: function() {
        var fancyboxInactive = !$.fancybox.getInstance();

        return fancyboxInactive;
    }
};
