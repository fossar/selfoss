'use strict';

import React from 'react';
import { getUrl } from '../helpers/url';

function Layout({
    title,
    postHeader,
    side,
    scripts,
    mdxContent,
    meta,
}) {
    return (
        <html lang="en">
            <head>
                <title>{title ?? meta.title}</title>

                <meta charSet="utf-8" />
                <meta name="copyright" content={meta.author} />
                <meta name="keywords" content="selfoss rss reader webbased mashup aggregator tobias zeising aditu" />
                <meta name="description" content="selfoss the web based open source rss reader and multi source mashup aggregator" />
                <meta name="robots" content="all" />

                <link rel="alternate" type="application/atom+xml" title="RSS Feed" href="https://github.com/fossar/selfoss/releases.atom" />

                <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />

                <link rel="stylesheet" type="text/css" media="screen" href="/assets/bundle.css" />
            </head>
            <body className={meta.url == '/' ? 'homepage' : ''}>

                {/* header */}
                <div id="header" className={meta.url === '/' ? 'header-homepage' : ''}>
                    <h1 id="header-name"><a href="/"><span>selfoss</span></a></h1>

                    <ul id="header-navigation">
                        <li><a href={getUrl('@/index.mdx#screenshots')}>screenshots</a></li>
                        <li><a href={getUrl('@/index.mdx#documentation')}>documentation</a></li>
                        <li><a href={getUrl('@/docs/project/credits.mdx')}>about</a></li>
                        <li><a href="/forum">forum</a></li>
                        <li><a href={`https://github.com/fossar/selfoss/releases/download/${meta.currentVersion}/selfoss-${meta.currentVersion}.zip`}>download</a></li>
                    </ul>

                    <a id="header-fork" href="https://github.com/fossar/selfoss"></a>
                </div>

                {postHeader}

                {/* Documentations */}
                <div className="wrapper-bright">
                    {meta.url !== '/' && (
                        <aside>
                            {side}
                        </aside>
                    )}

                    <div className="main">
                        {mdxContent}
                    </div>
                </div>


                <div id="footer">
                    <p>
                        <a href="https://github.com/fossar/selfoss">Github</a>
                        {' | '}
                        <a href="/forum">Forum</a>
                        {' | '}
                        <a href="https://www.aditu.de">About me</a>
                        {' | '}
                    logo by <a href="http://blackbooze.com/">Artcore</a>
                    </p>
                    <p>
                    &copy; by {meta.author} &sdot; <a href={`mailto:${meta.authorAddress}`}>{meta.authorAddress}</a> &sdot; <a href="https://www.aditu.de">www.aditu.de</a>
                    </p>
                </div>

                {scripts}
                <script type="text/javascript" src="/javascript/jquery-3.2.1.min.js"></script>
                <script type="text/javascript" src="/javascript/jquery.fancybox.min.js"></script>
                <script type="text/javascript" src="/javascript/base.js"></script>


                {/* Piwik */}
                <script type="text/javascript" dangerouslySetInnerHTML={{
                    __html: `
var pkBaseURL = (("https:" == document.location.protocol) ? "https://piwik.aditu.de/" : "http://piwik.aditu.de/");
document.write(unescape("%3Cscript src='" + pkBaseURL + "piwik.js' type='text/javascript'%3E%3C/script%3E"));
`
                }} /><script type="text/javascript" dangerouslySetInnerHTML={{
                    __html: `
try {
var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", 7);
piwikTracker.trackPageView();
piwikTracker.enableLinkTracking();
} catch( err ) {}
`
                }} /><noscript><p><img src="http://piwik.aditu.de/piwik.php?idsite=7" style={{border: 0}} alt="" /></p></noscript>
                {/* End Piwik Tracking Code */}
            </body>
        </html>
    );
}

module.exports = Layout;
