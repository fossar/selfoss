const React = require('react');
const { getUrl } = require('../helpers/url');

function Heading(props) {
    let { name, children, id, ...remainingProps } = props;

    if (typeof children === 'string') {
        const matches = children.match(/^(.*)\s+\{#(.+)\}$/);
        if (matches !== null) {
            [, children, id] = matches;
        }
    }

    return React.createElement(name, {
        ...remainingProps,
        children,
        id,
    });
}

module.exports = Heading;
