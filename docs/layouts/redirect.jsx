'use strict';

const React = require('react');
const { getUrl } = require('../helpers/url');

function Layout({
    meta,
    mdxContent,
    pageContext,
}) {
    return (
        <html>
        <meta charSet="utf-8" />
        <link rel="canonical" href={getUrl(meta.redirectTo)} />
        <meta httpEquiv="refresh" content={`0; url=${getUrl(meta.redirectTo)}`} />
        <title>Redirecting to {meta.title}</title>
        <p><a href={getUrl(meta.redirectTo)}>Click here</a> to be redirected.</p>
        </html>
    );
}

module.exports = Layout;
