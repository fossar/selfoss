import { tinykeys } from 'tinykeys';
import { Direction } from './helpers/navigation';

/**
 * Decorates an event handler so that it only runs
 * when not interacting with an input field or lightbox.
 */
function ignoreWhenInteracting(handler) {
    return (event) => {
        if (selfoss.lightboxActive.value) {
            return;
        }

        // Ignore shortcuts when on input elements.
        // https://github.com/jamiebuilds/tinykeys/issues/17
        const active = document.activeElement;
        const enteringText =
            active instanceof HTMLElement &&
            (active.isContentEditable ||
                active.tagName === 'INPUT' ||
                active.tagName === 'TEXTAREA');
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
        Space: ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.jumpToNext();
        }),

        // 'n': next article
        n: ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.nextPrev(Direction.NEXT, false);
        }),

        // 'right cursor': next article
        ArrowRight: ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.entryNav(Direction.NEXT);
        }),

        // 'j': next article
        j: ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.nextPrev(Direction.NEXT, true);
        }),

        // 'shift+space': previous article
        'Shift+Space': ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.nextPrev(Direction.PREV, true);
        }),

        // 'p': previous article
        p: ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.nextPrev(Direction.PREV, false);
        }),

        // 'left': previous article
        ArrowLeft: ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.entryNav(Direction.PREV);
        }),

        // 'k': previous article
        k: ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.nextPrev(Direction.PREV, true);
        }),

        // 's': star/unstar
        s: ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.toggleSelectedStarred();
        }),

        // 'm': mark/unmark
        m: ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.toggleSelectedRead();
        }),

        // 'o': open/close entry
        o: ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.toggleSelectedExpanded();
        }),

        // 'Shift + o': close open entries
        'Shift+o': ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.collapseAllEntries();
        }),

        // 'v': open target
        v: ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.openSelectedTarget();
        }),

        // 'Shift + v': open target and mark read
        'Shift+v': ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.openSelectedTargetAndMarkRead();
        }),

        // 'r': Reload the current view
        r: ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.reload();
        }),

        // 'Shift + r': Refresh sources
        'Shift+r': ignoreWhenInteracting((event) => {
            event.preventDefault();
            document.querySelector('#nav-refresh').click();
        }),

        // 'Control+m': mark all as read
        'Control+m': ignoreWhenInteracting((event) => {
            event.preventDefault();
            document.querySelector('#nav-mark').click();
        }),

        // 't': throw (mark as read & open next)
        t: ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.throw(Direction.NEXT);
        }),

        // throw (mark as read & open previous)
        'Shift+t': ignoreWhenInteracting((event) => {
            event.preventDefault();
            selfoss.entriesPage?.throw(Direction.PREV);
        }),

        // 'Shift+n': switch to newest items overview / menu item
        'Shift+n': ignoreWhenInteracting((event) => {
            event.preventDefault();
            document.querySelector('#nav-filter-newest').click();
        }),

        // 'Shift+u': switch to unread items overview / menu item
        'Shift+u': ignoreWhenInteracting((event) => {
            event.preventDefault();
            document.querySelector('#nav-filter-unread').click();
        }),

        // 'Shift+s': switch to starred items overview / menu item
        'Shift+s': ignoreWhenInteracting((event) => {
            event.preventDefault();
            document.querySelector('#nav-filter-starred').click();
        }),
    });
}
