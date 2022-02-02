'use strict';

const React = require('react');

function Layout({
    title,
    postHeader,
    side,
    scripts,
    mdxContent,
    meta,
    pageContext,
}) {
    return (
        <html lang="en">
        <head>
            <title>{title ?? config.title}</title>

            <meta charset="utf-8">
            <meta name="copyright" content={config.extra.author}>
            <meta name="keywords" content="selfoss rss reader webbased mashup aggregator tobias zeising aditu">
            <meta name="description" content="selfoss the web based open source rss reader and multi source mashup aggregator">
            <meta name="robots" content="all">

            <link rel="alternate" type="application/atom+xml" title="RSS Feed" href="https://github.com/fossar/selfoss/releases.atom">

            <link rel="shortcut icon" href={get_url('favicon.ico')} type="image/x-icon">

            <link rel="stylesheet" type="text/css" media="screen" href={get_url('style.css')}>
            <link rel="stylesheet" type="text/css" media="screen" href={get_url('javascript/jquery.fancybox.min.css')}>
        </head>
        <body class={current_path == '/' ? 'homepage' : ''}>

            <!-- header -->
            <div id="header" class={current_path === '/' ? 'header-homepage' : ''}>
                <h1 id="header-name"><a href="/"><span>selfoss</span></a></h1>

                <ul id="header-navigation">
                    <li><a href={get_url('@/_index.md#screenshots')}>screenshots</a></li>
                    <li><a href={get_url('@/_index.md#documentation')}>documentation</a></li>
                    <li><a href={get_url('@/docs/project/credits.md')}>about</a></li>
                    <li><a href="/forum">forum</a></li>
                    <li><a href={`https://github.com/fossar/selfoss/releases/download/${config.extra.current_version}/selfoss-${config.extra.current_version}.zip`}>download</a></li>
                </ul>

                <a id="header-fork" href="https://github.com/fossar/selfoss"></a>
            </div>

            {postHeader}

            <!-- Documentations -->
            <div class="wrapper-bright">
                {current_path !== '/' && (
                    <aside>
                        {side}
                    </aside>
                )}

                <div class="main">
                    {mdxContent}
                </div>
            </div>


            <div id="footer">
                <p>
                    <a href="https://github.com/fossar/selfoss">Github</a>
                    |
                    <a href="/forum">Forum</a>
                    |
                    <a href="https://www.aditu.de">About me</a>
                    |
                    logo by <a href="http://blackbooze.com/">Artcore</a>
                </p>
                <p>
                    &copy; by {config.extra.author} &sdot; <a href={`mailto:${config.extra.author_address}`}>{config.extra.author_address}</a> &sdot; <a href="https://www.aditu.de">www.aditu.de</a>
                </p>
            </div>

            {scripts}
            <script type="text/javascript" src={get_url('javascript/jquery-3.2.1.min.js')}></script>
            <script type="text/javascript" src={get_url('javascript/jquery.fancybox.min.js')}></script>
            <script type="text/javascript" src={get_url('javascript/base.js')}></script>


            {/* Piwik */}
            <script type="text/javascript">
            var pkBaseURL = (("https:" == document.location.protocol) ? "https://piwik.aditu.de/" : "http://piwik.aditu.de/");
            document.write(unescape("%3Cscript src='" + pkBaseURL + "piwik.js' type='text/javascript'%3E%3C/script%3E"));
            </script><script type="text/javascript">
            try {
            var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", 7);
            piwikTracker.trackPageView();
            piwikTracker.enableLinkTracking();
            } catch( err ) {}
            </script><noscript><p><img src="http://piwik.aditu.de/piwik.php?idsite=7" style="border:0" alt=""></p></noscript>
            <!-- End Piwik Tracking Code -->
        </body>
        </html>
    );
}

module.exports = Layout;
