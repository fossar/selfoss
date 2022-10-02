import tinykeys from 'tinykeys';
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
            selfoss.entriesPage?.jumpToNext();
            e.preventDefault();
            return false;
        }),

        // 'n': next article
        'n': ignoreWhenInteracting(function(e) {
            selfoss.entriesPage?.nextPrev(Direction.NEXT, false);
            e.preventDefault();
            return false;
        }),

        // 'right cursor': next article
        'ArrowRight': ignoreWhenInteracting(function(e) {
            selfoss.entriesPage?.entryNav(Direction.NEXT);
            e.preventDefault();
            return false;
        }),

        // 'j': next article
        'j': ignoreWhenInteracting(function(e) {
            selfoss.entriesPage?.nextPrev(Direction.NEXT, true);
            e.preventDefault();
            return false;
        }),

        // 'shift+space': previous article
        'Shift+Space': ignoreWhenInteracting(function(e) {
            selfoss.entriesPage?.nextPrev(Direction.PREV, true);
            e.preventDefault();
            return false;
        }),

        // 'p': previous article
        'p': ignoreWhenInteracting(function(e) {
            selfoss.entriesPage?.nextPrev(Direction.PREV, false);
            e.preventDefault();
            return false;
        }),

        // 'left': previous article
        'ArrowLeft': ignoreWhenInteracting(function(e) {
            selfoss.entriesPage?.entryNav(Direction.PREV);
            e.preventDefault();
            return false;
        }),

        // 'k': previous article
        'k': ignoreWhenInteracting(function(e) {
            selfoss.entriesPage?.nextPrev(Direction.PREV, true);
            e.preventDefault();
            return false;
        }),

        // 's': star/unstar
        's': ignoreWhenInteracting(function(e) {
            selfoss.entriesPage?.toggleSelectedStarred();
            e.preventDefault();
            return false;
        }),

        // 'm': mark/unmark
        'm': ignoreWhenInteracting(function(e) {
            selfoss.entriesPage?.toggleSelectedRead();
            e.preventDefault();
            return false;
        }),

        // 'o': open/close entry
        'o': ignoreWhenInteracting(function(e) {
            selfoss.entriesPage?.toggleSelectedExpanded();
            e.preventDefault();
            return false;
        }),

        // 'Shift + o': close open entries
        'Shift+o': ignoreWhenInteracting(function(e) {
            e.preventDefault();
            selfoss.entriesPage?.collapseAllEntries();
        }),

        // 'v': open target
        'v': ignoreWhenInteracting(function(e) {
            selfoss.entriesPage?.openSelectedTarget();
            e.preventDefault();
            return false;
        }),

        // 'Shift + v': open target and mark read
        'Shift+v': ignoreWhenInteracting(function(e) {
            e.preventDefault();
            selfoss.entriesPage?.openSelectedTargetAndMarkRead();
        }),

        // 'r': Reload the current view
        'r': ignoreWhenInteracting(function() {
            selfoss.entriesPage?.reload();
        }),

        // 'Shift + r': Refresh sources
        'Shift+r': ignoreWhenInteracting(function(e) {
            e.preventDefault();
            document.querySelector('#nav-refresh').click();
        }),

        // 'Control+m': mark all as read
        'Control+m': ignoreWhenInteracting(function(e) {
            document.querySelector('#nav-mark').click();
            e.preventDefault();
            return false;
        }),

        // 't': throw (mark as read & open next)
        't': ignoreWhenInteracting(function() {
            selfoss.entriesPage?.throw(Direction.NEXT);
            return false;
        }),

        // throw (mark as read & open previous)
        'Shift+t': ignoreWhenInteracting(function(e) {
            selfoss.entriesPage?.throw(Direction.PREV);
            e.preventDefault();
            return false;
        }),

        // 'Shift+n': switch to newest items overview / menu item
        'Shift+n': ignoreWhenInteracting(function(e) {
            e.preventDefault();
            document.querySelector('#nav-filter-newest').click();
        }),

        // 'Shift+u': switch to unread items overview / menu item
        'Shift+u': ignoreWhenInteracting(function(e) {
            e.preventDefault();
            document.querySelector('#nav-filter-unread').click();
        }),

        // 'Shift+s': switch to starred items overview / menu item
        'Shift+s': ignoreWhenInteracting(function(e) {
            e.preventDefault();
            document.querySelector('#nav-filter-starred').click();
        })
    });
}
