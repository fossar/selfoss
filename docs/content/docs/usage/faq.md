+++
title = "Frequently asked questions"
weight = 60
+++

## How do I find a feed? {#finding-feeds}

Some sites list an `alternate` link in their header. In Firefox, you can install [Awesome RSS](https://addons.mozilla.org/en-US/firefox/addon/awesome-rss/) extension and it will show an RSS icon in the address bar for such pages. Clicking the icon will open the feed.

selfoss also supports feed auto-detection. If you enter a URL of a web site into selfoss’s “Add source” form, and selfoss will look for an `alternate` link, as well as for some common file paths.

Some sites offer a feed but do not provide any machine findable link. They might still include a link with an RSS icon somewhere in the footer or sidebar.

If a site does not provide a feed and you still want to subscribe to its changes, you might need to try a proxy like [RSS-Bridge](https://rss-bridge.github.io/rss-bridge/).


## Why does selfoss keep logging me out? {#frequent-deauth}

selfoss uses PHP’s built-in [session support](https://www.php.net/manual/en/intro.session.php) to remember that the user is logged in. The session identifier is stored in a [cookie](https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies), which is set to expire in one month. If you are logged out sooner, make sure your web browser is not set to clear cookies on exit.

Additionally, PHP has [`session.gc_maxlifetime`](https://www.php.net/manual/en/session.configuration.php#ini.session.gc-maxlifetime) option in `php.ini` that defaults to 24 minutes, try [setting it](https://www.php.net/manual/en/configuration.php) to a larger value.

This setting should be change globally (e.g. in `php.ini`), otherwise the session might still end up being cleaned by PHP when another app runs on the same server and has shorter lifetime.


## How do I subscribe to a password protected feed? {#password-auth}

If the site uses [HTTP basic authentication](https://en.wikipedia.org/wiki/Basic_access_authentication), just enter the username and password before the url:

```
https://user:password@example.com/rss
```


## How do I import my subscriptions from YouTube? {#youtube-import}

Previously, it was possible to get OPML file with your subscriptions [directly on YouTube](https://web.archive.org/web/20200619150829/https://support.google.com/youtube/answer/6224202?hl=en). Unfortunately, Google removed this option so you will need to get the data from [Takeout](https://takeout.google.com/) and use one of the tools listed in this [Reddit thread](https://www.reddit.com/r/youtube/comments/jqlks2/where_did_opml_export_go/):

- [Manually](https://www.reddit.com/r/youtube/comments/jqlks2/comment/gcdii2n/)
- [Python script](https://gist.github.com/rptb1/cba49b801825ef3fffe4698dd96e360e)
- [JavaScript code to paste into developer tools](https://gist.github.com/aaronclimbs/091232147cca7c43349d3800695be21b) (Takeout not needed.)
- [Online tool](https://iuriioapps.com/tools/youtube-subs-opml/)

Once you have the OPML file, you can import it using the import link at the top of Sources settings page.
