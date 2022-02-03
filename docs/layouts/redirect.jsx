'use strict';

import React from 'react';
import { getUrl } from '../helpers/url';

function Layout({ meta }) {
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
