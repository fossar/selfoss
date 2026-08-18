import React from 'react';
import { KEYBINDINGS } from '../shortcuts';
import { intersperse } from 'ramda';

export default function HelpShortcuts(): React.JSX.Element {
    return (
        <>
            <h2>Keybindings</h2>
            <dl>
                {Object.entries(KEYBINDINGS).map(([keycombo, keybinding]) => {
                    let readableKeycombo: React.JSX.Element;
                    if (keybinding.readableName) {
                        readableKeycombo = <kbd>{keybinding.readableName}</kbd>;
                    } else if (keycombo.includes('+')) {
                        const keys = keycombo
                            .split('+')  // key={key} required for TS
                            .map((key) => <kbd key={key}>{key}</kbd>);
                        readableKeycombo = (
                            <>{intersperse(<span>+</span>, keys)}</>
                        );
                    } else {
                        readableKeycombo = <kbd>{keycombo}</kbd>;
                    }
                    return (
                        <>
                            <dt>{readableKeycombo}</dt>
                            <dd>{keybinding.description}</dd>
                        </>
                    );
                })}
            </dl>
        </>
    );
}
