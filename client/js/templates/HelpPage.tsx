import React from "react";
import { KEYBINDINGS } from "../shortcuts";

export default function HelpPage(): React.JSX.Element {
    const keycombos: string[] = Object.keys(KEYBINDINGS);
    return (
        <dl>
            {keycombos.map((keycombo) => {
                const keybinding = KEYBINDINGS[keycombo];

                let readableKeycombo: React.JSX.Element;
                if (keybinding.readableName) {
                    readableKeycombo = (<kbd>{keybinding.readableName}</kbd>);
                } else if (keycombo.includes("+")) {
                    const keys = keycombo.split("+");
                    readableKeycombo = (<>{keys.map((key: string, i: number) => (
                        <><kbd>{key}</kbd>{i < keys.length-1 ? <span>+</span> : null}</>
                    ))}</>);
                } else {
                    readableKeycombo = (<kbd>{keycombo}</kbd>);
                }
                return (
                    <>
                        <dt>{readableKeycombo}</dt>
                        <dd>{keybinding.description}</dd>
                    </>
                )
            })}
        </dl>
    )
}


