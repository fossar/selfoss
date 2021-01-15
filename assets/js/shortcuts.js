import tinykeys from 'tinykeys';

const Direction = {
    PREV: 'prev',
    NEXT: 'next'
};


/**
 * autoscroll
 */
function autoscroll(next) {
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
}

/**
 * get next/prev item
 * @param direction
 */
export function nextprev(direction, open = true) {
    if (direction != Direction.NEXT && direction != Direction.PREV) {
        throw new Error('direction must be one of Direction.{PREV,NEXT}');
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
    if (direction == Direction.NEXT) {
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
        autoscroll(currentElement);

        // focus the title link for better keyboard navigation
        currentElement.querySelector('.entry-title-link').focus();
    }
}


/**
 * entry navigation (next/prev) with keys
 * @param direction
 */
function entrynav(direction) {
    if (direction != Direction.NEXT && direction != Direction.PREV) {
        throw new Error('direction must be one of Direction.{PREV,NEXT}');
    }

    const open = selfoss.ui.entryIsExpanded(selfoss.ui.entryGetSelected());
    nextprev(direction, open);
}

/**
 * Check whether keyboard shortcuts should be active
 */
function lightboxActive() {
    var fancyboxInactive = !$.fancybox.getInstance();

    return fancyboxInactive;
}

/**
 * Decorates an event handler so that it only runs
 * when not interacting with an input field or lightbox.
 */
function ignoreWhenInteracting(handler) {
    return (event) => {
        if (!lightboxActive()) {
            return;
        }

        // Ignore shortcuts when on input elements.
        // https://github.com/jamiebuilds/tinykeys/issues/17
        const active = document.activeElement;
        const enteringText = active instanceof HTMLElement && (active.isContentEditable || active.tagName === 'INPUT' || active.tagName === 'TEXTAREA');
        if (!enteringText) {
            return handler(event);
        }
    };
}

/**
 * Set up shortcuts on document.
 */
export default function makeShortcuts() {
    return tinykeys(document, {
        // 'space': next article
        'Space': ignoreWhenInteracting(function(e) {
            var selected = selfoss.ui.entryGetSelected();
            if (selected !== null && !selfoss.ui.entryIsExpanded(selected)) {
                selfoss.ui.entryActivate(selected);
            } else {
                nextprev(Direction.NEXT, true);
            }
            e.preventDefault();
            return false;
        }),

        // 'n': next article
        'n': ignoreWhenInteracting(function(e) {
            nextprev(Direction.NEXT, false);
            e.preventDefault();
            return false;
        }),

        // 'right cursor': next article
        'ArrowRight': ignoreWhenInteracting(function(e) {
            entrynav(Direction.NEXT);
            e.preventDefault();
            return false;
        }),

        // 'j': next article
        'j': ignoreWhenInteracting(function(e) {
            nextprev(Direction.NEXT, true);
            e.preventDefault();
            return false;
        }),

        // 'shift+space': previous article
        'Shift+Space': ignoreWhenInteracting(function(e) {
            nextprev(Direction.PREV, true);
            e.preventDefault();
            return false;
        }),

        // 'p': previous article
        'p': ignoreWhenInteracting(function(e) {
            nextprev(Direction.PREV, false);
            e.preventDefault();
            return false;
        }),

        // 'left': previous article
        'ArrowLeft': ignoreWhenInteracting(function(e) {
            entrynav(Direction.PREV);
            e.preventDefault();
            return false;
        }),

        // 'k': previous article
        'k': ignoreWhenInteracting(function(e) {
            nextprev(Direction.PREV, true);
            e.preventDefault();
            return false;
        }),

        // 's': star/unstar
        's': ignoreWhenInteracting(function(e) {
            var selected = selfoss.ui.entryGetSelected();

            if (selected !== null) {
                document.querySelector(`.entry[data-entry-id="${selected}"] .entry-starr`).click();
            }

            e.preventDefault();
            return false;
        }),

        // 'm': mark/unmark
        'm': ignoreWhenInteracting(function(e) {
            var selected = selfoss.ui.entryGetSelected();

            if (selected !== null) {
                document.querySelector(`.entry[data-entry-id="${selected}"] .entry-unread`).click();
            }

            e.preventDefault();
            return false;
        }),

        // 'o': open/close entry
        'o': ignoreWhenInteracting(function(e) {
            selfoss.ui.entryToggleExpanded(selfoss.ui.entryGetSelected());
            e.preventDefault();
            return false;
        }),

        // 'Shift + o': close open entries
        'Shift+o': ignoreWhenInteracting(function(e) {
            e.preventDefault();
            selfoss.ui.entryCollapseAll();
        }),

        // 'v': open target
        'v': ignoreWhenInteracting(function(e) {
            var selected = selfoss.ui.entryGetSelected();

            if (selected !== null) {
                const elem = document.querySelector(`.entry[data-entry-id="${selected}"]`);
                window.open(elem.querySelector('.entry-datetime').getAttribute('href'));
            }

            e.preventDefault();
            return false;
        }),

        // 'Shift + v': open target and mark read
        'Shift+v': ignoreWhenInteracting(function(e) {
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
        }),

        // 'r': Reload the current view
        'r': ignoreWhenInteracting(function(e) {
            e.preventDefault();
            $('#nav-filter-unread').click();
        }),

        // 'Shift + r': Refresh sources
        'Shift+r': ignoreWhenInteracting(function(e) {
            e.preventDefault();
            $('#nav-refresh').click();
        }),

        // 'Control+m': mark all as read
        'Control+m': ignoreWhenInteracting(function(e) {
            $('#nav-mark').click();
            e.preventDefault();
            return false;
        }),

        // 't': throw (mark as read & open next)
        't': ignoreWhenInteracting(function() {
            let selected = selfoss.ui.entryGetSelected();

            if (selected !== null) {
                const elem = document.querySelector(`.entry[data-entry-id="${selected}"]`);
                // mark item as read if it is not already
                if (elem.querySelector('.entry-unread').classList.contains('active')) {
                    elem.querySelector('.entry-unread').click();
                }
            }

            nextprev(Direction.NEXT, true);
            return false;
        }),

        // throw (mark as read & open previous)
        'Shift+t': ignoreWhenInteracting(function(e) {
            let selected = selfoss.ui.entryGetSelected();

            if (selected !== null) {
                const elem = document.querySelector(`.entry[data-entry-id="${selected}"]`);
                // mark item as read if it is not already
                if (elem.querySelector('.entry-unread').classList.contains('active')) {
                    elem.querySelector('.entry-unread').click();
                }
            }

            nextprev(Direction.PREV, true);
            e.preventDefault();
            return false;
        }),

        // 'Shift+n': switch to newest items overview / menu item
        'Shift+n': ignoreWhenInteracting(function(e) {
            e.preventDefault();
            $('#nav-filter-newest').click();
        }),

        // 'Shift+u': switch to unread items overview / menu item
        'Shift+u': ignoreWhenInteracting(function(e) {
            e.preventDefault();
            $('#nav-filter-unread').click();
        }),

        // 'Shift+s': switch to starred items overview / menu item
        'Shift+s': ignoreWhenInteracting(function(e) {
            e.preventDefault();
            $('#nav-filter-starred').click();
        })
    });
}
