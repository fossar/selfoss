import { KeybindingsMap, tinykeys } from 'tinykeys';
import selfoss from './selfoss-base';
import { Direction } from './helpers/navigation';
import { RefObject } from 'react';

type KeyboardEventHandler = (event: KeyboardEvent) => void;

/**
 * Decorates an event handler so that it only runs
 * when not interacting with an input field or lightbox.
 */
function ignoreWhenInteracting(
    handler: KeyboardEventHandler,
    ignoreDialog: RefObject<HTMLDialogElement> = undefined,
): KeyboardEventHandler {
    return (event: KeyboardEvent): void => {
        if (selfoss.lightboxActive.value) {
            return;
        } else if (ignoreDialog && ignoreDialog.current.open) {
            return;
        }

        handler(event);
    };
}

interface IKeybinding {
    readableName?: string;
    description: string;
    action: KeyboardEventHandler;
}

/**
 * A selfoss-side definition of all keybindings
 * This is used to:
 * - Limit boilerplate
 * - Generate a tinykeys compatible KeybindingsMap (@see makeKeybindingsMap)
 * - Generate a keybindings overview for the help page (see HelpShortcuts component)
 */
export const KEYBINDINGS: { [keycombo: string]: IKeybinding } = {
    Space: {
        description: 'select and open next entry',
        action: () => {
            selfoss.entriesPage?.jumpToNext();
        },
    },
    n: {
        description: 'select next entry',
        action: () => {
            selfoss.entriesPage?.nextPrev(Direction.NEXT, false);
        },
    },
    Arrowright: {
        readableName: '→',
        description: 'select next entry (and open it when the current is open)',
        action: () => {
            selfoss.entriesPage?.entryNav(Direction.NEXT);
        },
    },
    j: {
        description: 'select and open next entry',
        action: () => {
            selfoss.entriesPage?.nextPrev(Direction.NEXT, true);
        },
    },
    'Shift+Space': {
        description: 'select and open previous entry',
        action: () => {
            selfoss.entriesPage?.nextPrev(Direction.PREV, true);
        },
    },
    p: {
        description: 'select previous entry',
        action: () => {
            selfoss.entriesPage?.nextPrev(Direction.PREV, false);
        },
    },
    ArrowLeft: {
        readableName: '←',
        description:
            'select previous entry (and open it when the current is open)',
        action: () => {
            selfoss.entriesPage?.entryNav(Direction.PREV);
        },
    },
    k: {
        description: 'select and open previous entry',
        action: () => {
            selfoss.entriesPage?.nextPrev(Direction.PREV, true);
        },
    },
    s: {
        description:
            'mark and unmark current selected entry as starred/unstarred',
        action: () => {
            selfoss.entriesPage?.toggleSelectedStarred();
        },
    },
    m: {
        description: 'mark and unmark current selected entry as read/unread',
        action: () => {
            selfoss.entriesPage?.toggleSelectedRead();
        },
    },
    'Control+m': {
        description: 'mark all as read',
        action: () => {
            document.querySelector<HTMLButtonElement>('#nav-mark').click();
        },
    },
    o: {
        description: 'open / close current entry',
        action: () => {
            selfoss.entriesPage?.toggleSelectedExpanded();
        },
    },
    'Shift+o': {
        description: 'close all open entries',
        action: () => {
            selfoss.entriesPage?.collapseAllEntries();
        },
    },
    v: {
        description: 'open url of current entry in new tab/window',
        action: () => {
            selfoss.entriesPage?.openSelectedTarget();
        },
    },
    'Shift+v': {
        description:
            'open url of current entry in new tab/window and mark read',
        action: () => {
            selfoss.entriesPage?.openSelectedTargetAndMarkRead();
        },
    },
    r: {
        description: 'reload the list',
        action: () => {
            selfoss.entriesPage?.reload();
        },
    },
    'Shift+r': {
        description: 'refresh sources',
        action: () => {
            document.querySelector<HTMLButtonElement>('#nav-refresh').click();
        },
    },
    t: {
        description: 'throw current entry to next (mark as read & open next)',
        action: () => {
            selfoss.entriesPage?.throw(Direction.NEXT);
        },
    },
    'Shift+t': {
        description:
            'throw current entry to previous (mark as read & open previous)',
        action: () => {
            selfoss.entriesPage?.throw(Direction.PREV);
        },
    },
    'Shift+n': {
        description: 'open newest entries page',
        action: () => {
            document
                .querySelector<HTMLAnchorElement>('#nav-filter-newest')
                .click();
        },
    },
    'Shift+u': {
        description: 'open unread entries page',
        action: () => {
            document
                .querySelector<HTMLAnchorElement>('#nav-filter-unread')
                .click();
        },
    },
    'Shift+s': {
        description: 'open starred entries page',
        action: () => {
            document
                .querySelector<HTMLAnchorElement>('#nav-filter-starred')
                .click();
        },
    },
    '[Shift]+?': {
        description: 'show help',
        action: () => {},
    },
};

function makeKeybindingsMap(
    shortcutsDialog: RefObject<HTMLDialogElement>,
): KeybindingsMap {
    const shortcuts = Object.entries(KEYBINDINGS);
    const keybindingsMap: KeybindingsMap = {};

    for (const [keycombo, keybind] of shortcuts) {
        if (keycombo === '[Shift]+?') {
            keybindingsMap[keycombo] = ignoreWhenInteracting(
                (event: KeyboardEvent) => {
                    event.preventDefault();
                    if (shortcutsDialog.current.open) {
                        shortcutsDialog.current.close();
                    } else {
                        shortcutsDialog.current.showModal();
                    }
                },
            );
        } else {
            keybindingsMap[keycombo] = ignoreWhenInteracting(
                (event: KeyboardEvent) => {
                    event.preventDefault();
                    keybind.action(event);
                },
                shortcutsDialog,
            );
        }
    }
    return keybindingsMap;
}

/**
 * Set up shortcuts on document.
 */
export default function makeShortcuts(
    shortcutsDialog: RefObject<HTMLDialogElement>,
): () => void {
    return tinykeys(window, makeKeybindingsMap(shortcutsDialog));
}
