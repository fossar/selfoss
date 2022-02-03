const React = require('react');
const Heading = require('./heading');

function h2(props) {
    return (
        <Heading name="h2" {...props} />
    );
}

module.exports = h2;
