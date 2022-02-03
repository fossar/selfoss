'use strict';

const React = require('react');
const Layout = require('./default.jsx');
const { getUrl } = require('../helpers/url');

function IndexLayout({ mdxContent, meta, section, pageContext }) {
    const postHeader = (
        <React.Fragment>
            <div className="wrapper-light">
                <div id="header-logo"></div>

                <div id="header-just-updated"></div>

                <a id="header-download" href={`https://github.com/fossar/selfoss/releases/download/${meta.currentVersion}/selfoss-${meta.currentVersion}.zip`}><span>download selfoss {meta.currentVersion}</span></a>

                <a id="header-donate" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=LR67F3T9DMSC8"><span>donate</span></a>

                <div id="header-appstores">
                    <a href="https://f-droid.org/packages/apps.amine.bou.readerforselfoss"><img alt="Android app on F-Droid" src="images/f-droid.svg" width="141" height="42" /></a>
                </div>

                <div id="header-donate-tooltipp"><span>selfoss is completely free!<br />But if you like selfoss then feel free to donate the programmer a beer</span></div>

                <div id="header-teaser">
                    <h1>The new multi-purpose RSS reader, live stream, mash-up, aggregation web application</h1>

                    <h2>Features</h2>

                    <ul>
                        <li>web-based RSS reader and universal aggregator</li>
                        <li>use selfoss to live stream and collect all your posts, tweets, feeds in one place</li>
                        <li>open source and free</li>
                        <li>mobile support (Android, iOS, iPad)</li>
                        <li>off-line web app</li>
                        <li>OPML Import</li>
                        <li>easy installation: just upload and run</li>
                        <li>lightweight PHP application with less than 2 MB</li>
                        <li>supports MySQL, PostgreSQL and SQLite databases</li>
                        <li>easily extensible with an open plug-in system (write your own data connectors)</li>
                        <li>RESTful JSON API for developers</li>
                        <li>third party <a href="https://f-droid.org/packages/apps.amine.bou.readerforselfoss">app for Android</a> available</li>
                    </ul>
                </div>
            </div>

            {/* Screenshots */}
            <div className="wrapper-dark">
                <div id="screenshots">
                    <h1>Screenshots</h1>

                    <ul>
                        <li><a href="images/screenshot1.png" title="selfoss on desktop" data-fancybox="screenshots"><img src="images/screenshot1_thumb.png" alt="selfoss on desktop" /></a></li>
                        <li><a href="images/screenshot2.png" title="selfoss on ipad" data-fancybox="screenshots"><img src="images/screenshot2_thumb.png" alt="selfoss on ipad" /></a></li>
                        <li><a href="images/screenshot3.png" title="selfoss on smartphone" data-fancybox="screenshots"><img src="images/screenshot3_thumb.png" alt="selfoss on smartphone" /></a></li>
                    </ul>

                </div>
            </div>
        </React.Fragment>
    );

    const scripts = (
        meta.url === '/' ? (
            <script dangerouslySetInnerHTML={{__html: `
            switch (document.location.hash) {
            case '#configuration_params':
                document.location.href = '${getUrl("@/docs/administration/options.mdx")}';
                break;
            case '#about':
                document.location.href = '${getUrl("@/docs/project/credits.mdx")}';
                break;
            }
            `}} />
        ) : undefined
    );

    return (
        <Layout
            title={meta.title}
            postHeader={postHeader}
            scripts={scripts}
            mdxContent={mdxContent}
            meta={meta}
            pageContext={pageContext}
        />
    );
}

module.exports = IndexLayout;
