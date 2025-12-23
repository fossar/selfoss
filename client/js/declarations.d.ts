declare module '@siteparts/show-hide-effects' {
    export function fadeOut(
        element: HTMLElement,
        options?: {
            duration?: number;
            complete?: () => void;
        },
    ): void;
}

// Workaround for https://github.com/jamiebuilds/tinykeys/issues/191
// Subset of tinykeys.d.ts from the upstream package
declare module 'tinykeys' {
    export interface KeyBindingMap {
        [keybinding: string]: (event: KeyboardEvent) => void;
    }
    export declare function tinykeys(
        target: Window | HTMLElement,
        keyBindingMap: KeyBindingMap,
    ): () => void;
}
