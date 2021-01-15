import selfoss from './selfoss-base';

selfoss.shortcuts = {
    DIRECTION_PREV: 'prev',
    DIRECTION_NEXT: 'next',

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
                selfoss.shortcuts.nextprev(selfoss.shortcuts.DIRECTION_NEXT, true);
            }
            e.preventDefault();
            return false;
        });

        // 'n': next article
        $(document).bind('keydown', 'n', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.shortcuts.nextprev(selfoss.shortcuts.DIRECTION_NEXT, false);
            e.preventDefault();
            return false;
        });

        // 'right cursor': next article
        $(document).bind('keydown', 'right', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.shortcuts.entrynav(selfoss.shortcuts.DIRECTION_NEXT);
            e.preventDefault();
            return false;
        });

        // 'j': next article
        $(document).bind('keydown', 'j', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.shortcuts.nextprev(selfoss.shortcuts.DIRECTION_NEXT, true);
            e.preventDefault();
            return false;
        });

        // 'shift+space': previous article
        $(document).bind('keydown', 'shift+space', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.shortcuts.nextprev(selfoss.shortcuts.DIRECTION_PREV, true);
            e.preventDefault();
            return false;
        });

        // 'p': previous article
        $(document).bind('keydown', 'p', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.shortcuts.nextprev(selfoss.shortcuts.DIRECTION_PREV, false);
            e.preventDefault();
            return false;
        });

        // 'left': previous article
        $(document).bind('keydown', 'left', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.shortcuts.entrynav(selfoss.shortcuts.DIRECTION_PREV);
            e.preventDefault();
            return false;
        });

        // 'k': previous article
        $(document).bind('keydown', 'k', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            selfoss.shortcuts.nextprev(selfoss.shortcuts.DIRECTION_PREV, true);
            e.preventDefault();
            return false;
        });

        // 's': star/unstar
        $(document).bind('keydown', 's', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            var selected = selfoss.ui.entryGetSelected();

            if (selected !== null) {
                document.querySelector(`.entry[data-entry-id="${selected}"] .entry-starr`).click();
            }

            e.preventDefault();
            return false;
        });

        // 'm': mark/unmark
        $(document).bind('keydown', 'm', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            var selected = selfoss.ui.entryGetSelected();

            if (selected !== null) {
                document.querySelector(`.entry[data-entry-id="${selected}"] .entry-unread`).click();
            }

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

            var selected = selfoss.ui.entryGetSelected();

            if (selected !== null) {
                const elem = document.querySelector(`.entry[data-entry-id="${selected}"]`);
                window.open(elem.querySelector('.entry-datetime').getAttribute('href'));
            }

            e.preventDefault();
            return false;
        });

        // 'Shift + v': open target and mark read
        $(document).bind('keydown', 'Shift+v', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            e.preventDefault();

            var selected = selfoss.ui.entryGetSelected();

            if (selected !== null) {
                const elem = document.querySelector(`.entry[data-entry-id="${selected}"]`);
                // mark item as read
                if (elem.querySelector('.entry-unread').classList.contains('active')) {
                    elem.querySelector('.entry-unread').click();
                }

                // open item in new window
                elem.querySelector('.entry-datetime').click();
            }
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

            let selected = selfoss.ui.entryGetSelected();

            if (selected !== null) {
                const elem = document.querySelector(`.entry[data-entry-id="${selected}"]`);
                // mark item as read if it is not already
                if (elem.querySelector('.entry-unread').classList.contains('active')) {
                    elem.querySelector('.entry-unread').click();
                }
            }

            selfoss.shortcuts.nextprev(selfoss.shortcuts.DIRECTION_NEXT, true);
            return false;
        });

        // throw (mark as read & open previous)
        $(document).bind('keydown', 'Shift+t', function(e) {
            if (!selfoss.shortcuts.active()) {
                return false;
            }

            let selected = selfoss.ui.entryGetSelected();

            if (selected !== null) {
                const elem = document.querySelector(`.entry[data-entry-id="${selected}"]`);
                // mark item as read if it is not already
                if (elem.querySelector('.entry-unread').classList.contains('active')) {
                    elem.querySelector('.entry-unread').click();
                }
            }

            selfoss.shortcuts.nextprev(selfoss.shortcuts.DIRECTION_PREV, true);
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
    nextprev: function(direction, open = true) {
        if (direction != selfoss.shortcuts.DIRECTION_NEXT && direction != selfoss.shortcuts.DIRECTION_PREV) {
            throw new Error('direction must be one of selfoss.shortcuts.DIRECTION_{PREV,NEXT}');
        }

        // when there are no entries
        if (selfoss.entriesPage.state.entries.length == 0) {
            return;
        }

        // select current
        const old = selfoss.ui.entryGetSelected();
        const oldIndex = old !== null ? selfoss.entriesPage.state.entries.findIndex(({ id }) => id === old) : null;
        let current = null;

        // select next/prev entry and save it to "current"
        // if we would overflow, we stay on the old one
        if (direction == selfoss.shortcuts.DIRECTION_NEXT) {
            if (old === null) {
                current = selfoss.entriesPage.state.entries[0].id;
            } else {
                const nextIndex = oldIndex + 1;
                if (nextIndex >= selfoss.entriesPage.state.entries.length) {
                    current = old;

                    // attempt to load more
                    document.querySelector('.stream-more').click();
                } else {
                    current = selfoss.entriesPage.state.entries[nextIndex].id;
                }
            }
        } else {
            if (old === null) {
                return;
            } else {
                if (oldIndex <= 0) {
                    current = old;
                } else {
                    current = selfoss.entriesPage.state.entries[oldIndex - 1].id;
                }
            }
        }

        if (old !== current) {
            // remove active
            selfoss.ui.entryDeactivate(old);

            if (open) {
                selfoss.ui.entryActivate(current);
            } else {
                selfoss.ui.entrySelect(current);
            }

            const currentElement = document.querySelector(`.entry[data-entry-id="${current}"]`);

            // scroll to element
            selfoss.shortcuts.autoscroll(currentElement);

            // focus the title link for better keyboard navigation
            currentElement.querySelector('.entry-title-link').focus();
        }
    },


    /**
     * autoscroll
     */
    autoscroll: function(next) {
        next = $(next);
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
        if (direction != selfoss.shortcuts.DIRECTION_NEXT && direction != selfoss.shortcuts.DIRECTION_PREV) {
            throw new Error('direction must be one of selfoss.shortcuts.DIRECTION_{PREV,NEXT}');
        }

        const open = selfoss.ui.entryIsExpanded(selfoss.ui.entryGetSelected());
        selfoss.shortcuts.nextprev(direction, open);
    },

    /**
     * Check whether keyboard shortcuts should be active
     */
    active: function() {
        var fancyboxInactive = !$.fancybox.getInstance();

        return fancyboxInactive;
    }
};
