const React = require('react');
const { getUrl } = require('../helpers/url');

function a(props) {
    return (
        <a {...props} href={getUrl(props.href)} />
    );
}

module.exports = a;
