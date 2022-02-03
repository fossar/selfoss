import React from 'react';

function Heading(props) {
    let { name, children, id, ...remainingProps } = props;

    if (typeof children === 'string') {
        const matches = children.match(/^(.*)\s+\{#(.+)\}$/);
        if (matches !== null) {
            [, children, id] = matches;
        }
    }

    return React.createElement(
        name,
        {
            ...remainingProps,
            id,
        },
        children,
    );
}

module.exports = Heading;
