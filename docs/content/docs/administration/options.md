+++
title = "Configuration options"
weight = 31
+++

selfoss offers the following configuration parameters. You can set the config parameters in the `config.ini` file.

### `db_type`
<div class="config-option">

database type (`sqlite`, `mysql` or `pgsql`)
</div>

### `db_file`
<div class="config-option">

location of database file for SQLite
</div>

### `db_host`
<div class="config-option">

address/hostname of the database server for MySQL/PostgreSQL
</div>

### `db_database`
<div class="config-option">

name of the database
</div>

### `db_username`
<div class="config-option">

database username
</div>

### `db_password`
<div class="config-option">

database password
</div>

### `db_prefix`
<div class="config-option">

Table prefix for MySQL/SQLite databases. This is useful to avoid conflicts when you are sharing a database with another application.
</div>

### `db_port`
<div class="config-option">

Port for database connections. By default `3306` will be used for MySQL and `5432` for PostgreSQL.
</div>

### `db_socket`
<div class="config-option">

A UNIX domain socket used for connecting to the MySQL database server. Usually, you want to use `db_host=localhost`, which should use the default socket path (typically `/run/mysqld/mysqld.sock` for MySQL or `/run/postgresql` for PostgreSQL) but if you need to specify a different location, you can. This is orthogonal to `db_host` option.
</div>

### `logger_destination`
<div class="config-option">

By default, the logs are saved to `data/logs/default.log` but you can choose a different file by specifying a file path prefixed by `file:`. Setting `file:php://stderr` is especially useful when running selfoss on a PaaS or inside Docker. Alternately, you can set the option to `error_log` to redirect the messages to [SAPI error log](https://secure.php.net/manual/en/function.error-log.php) – handy for PHP-FPM, which [discards stderr](https://secure.php.net/manual/en/install.fpm.configuration.php#catch-workers-output) by default.
</div>

### `logger_level`
<div class="config-option">

set logging level – following logging levels are available: `EMERGENCY`, `ALERT`, `CRITICAL`, `ERROR`, `WARNING`, `NOTICE`, `INFO`, `DEBUG`. Additionally, you can use `NONE` pseudo-level to turn the logging off completely.

Use this for troubleshooting on updating feeds (but be aware that the log file can become very large.)
</div>

### `items_perpage`
<div class="config-option">

number of entries per page on your stream
</div>

### `items_lifetime`
<div class="config-option">

Number of days since the item has been last seen after which it can be deleted. Set to `0` to disable item deletion. Starred items will never be deleted.
</div>

### `base_url`
<div class="config-option">

base URL of the selfoss page; use this option if you use a ssl proxy which changes the `$_SERVER` globals, most notably the URL path in which the app is installed.
</div>

### `username`
<div class="config-option">

username for optional login. Just set username and password for enabling login.
</div>

### `password`
<div class="config-option">

password hash for optional login. You can generate a password hash by using following page of your selfoss installation. https://your_selfoss_url.com/password
</div>

### <del>`salt`</del> (deprecated)
<div class="config-option">

salt for hashing the password (see [Wikipedia](https://en.wikipedia.org/wiki/Salt_(cryptography))). Not used for passwords generated using selfoss 2.19 or newer.
</div>

### `public`
<div class="config-option">

if you use login (`username` and `password` are set), you can allow guests to see your stream. Enter `1` for enabling this write-protected mode.
</div>

### `rss_title`
<div class="config-option">

title of the generated rss feed
</div>

### `rss_max_items`
<div class="config-option">

maximum amount of items in the generated rss feed
</div>

### `rss_mark_as_read`
<div class="config-option">

set this to `1` to automatically mark items as read after they appeared in selfoss’s RSS.
</div>

### `homepage`
<div class="config-option">

set here your preferred homepage. Choose between `newest`, `unread` and `starred`. It is also possible to configure a tag (e.g. `unread/tag-yourtag`) or a source (e.g. `newest/source-123`). Default = `newest`.
</div>

### `auto_mark_as_read`
<div class="config-option">

set this to `1` to automatically mark an item as read on opening it.
</div>

### `auto_collapse`
<div class="config-option">

set this to `1` to automatically collapse an item when another one is opened.
</div>

### `auto_stream_more`
<div class="config-option">

set this to `0` to disable automatic loading of more items when you scroll down. Click a button at the bottom of the page will be required instead.
</div>

### `open_in_background_tab`
<div class="config-option">

set this to `1` to try to make <kbd>v</kbd> shortcut open articles in new background tab. This [does not work in Chromium based browsers](https://crbug.com/431335).
</div>

### `language`
<div class="config-option">

set `0` or leave empty for auto detection (browser language) or use one of the following language codes:

* Catalan: `ca`
* Chinese (Simplified): `zh-CN`
* Chinese (Traditional): `zh-TW`
* Czech: `cs`
* Dutch: `nl`
* English: `en`
* English (United Kingdom): `en-GB`
* Estonian: `et`
* Finnish: `fi`
* French: `fr`
* French (Canada): `fr-CA`
* Galician: `gl`
* German: `de`
* Hebrew: `he`
* Hungarian: `hu`
* Italian: `it`
* Indonesian: `id`
* Japanese: `ja`
* Latvian: `lv`
* Norwegian Bokmål: `nb`
* Polish: `pl`
* Portuguese: `pt`
* Portuguese (Brazil): `pt-BR`
* Romansh: `rm`
* Russian: `ru`
* Slovak: `sk`
* Spanish: `es`
* Swedish: `sv`
* Turkish: `tr`
* Ukrainian: `uk`
</div>

### `allow_public_update_access`
<div class="config-option">

Set to `1` to allow public access for `/update` (anybody can access and start the update job).
</div>

### `share`
<div class="config-option">

`share` defines which sharing buttons beneath the entry are visible. The following methods are supported:

<dl>
<dt><code>a</code></dt><dd><a href="https://developer.mozilla.org/en-US/docs/Web/API/Navigator/share">Web Share API</a>, when available</dd>
<dt><code>f</code></dt><dd>Facebook</dd>
<dt><code>t</code></dt><dd>Twitter</dd>
<dt><code>p</code></dt><dd>Pocket</dd>
<dt><code>d</code></dt><dd>Diaspora</dd>
<dt><code>m</code></dt><dd>Mastodon (requires <a href="#mastodon"><code>mastodon</code></a> option to be set)</dd>
<dt><code>w</code></dt><dd>Wallabag (requires <a href="#wallabag"><code>wallabag</code></a> option to be set)</dd>
<dt><code>s</code></dt><dd>Wordpress (requires <a href="#wordpress"><code>wordpress</code></a> option to be set)</dd>
<dt><code>e</code></dt><dd>E-mail</dd>
<dt><code>c</code></dt><dd>Copy to clipboard</dd>
</dl>

Include the letters for methods you want to use. For example, if you would like to only show Facebook and Twitter share buttons, use `share=ft`.

Defaults to `share=atfpde`.
</div>

### `mastodon`
<div class="config-option">

URL of your Mastodon instance, for example `https://example.com`.
</div>

### `wallabag`
<div class="config-option">

URL of your [Wallabag](https://www.wallabag.org/) instance.
</div>

### `wallabag_version`
<div class="config-option">

Set to `2` or `1` depending on your wallabag version (`2` for version 2.x or `1` for version 1.x).
</div>

### `wordpress`
<div class="config-option">

URL of your WordPress blog for sharing links.
</div>

### `unread_order`
<div class="config-option">

Set to `asc` to read your unread items from the oldest to the newest, leave it empty or to `desc` to read from the newest to the oldest.
</div>

### `load_images_on_mobile`
<div class="config-option">

Set to `1` to allow image lazy loading on mobile devices.
</div>

### `auto_hide_read_on_mobile`
<div class="config-option">

Hide read articles on mobile devices.
</div>

### `scroll_to_article_header`
<div class="config-option">

Set to `0` to stop the interface from scrolling to the article header when an article is opened.
</div>

### `double_click_mark_as_read`
<div class="config-option">

set this to `1` to mark an item as read when double clicking on it.
</div>

### `env_prefix`
<div class="config-option">

selfoss can use environment variables that start with this prefix as additional configuration options. This is useful for tools like Docker, where creating a config file is impractical. Defaults to `SELFOSS_`.
</div>

### `camo_domain`
<div class="config-option">

Camo domain used to proxy images (optional). See [atmos/camo](https://github.com/atmos/camo) for more details.
</div>

### `camo_key`
<div class="config-option">

Camo domain used to proxy images (optional). See [atmos/camo](https://github.com/atmos/camo) for more details.
</div>

### `show_thumbnails`
<div class="config-option">

If set to `0`, thumbnails are not shown in the collapsed view. Defaults to `1`.
</div>

### `datadir` {#datadir}
<div class="config-option">

Location of the data directory; especially useful when selfoss is installed to write-protected file system. `.htaccess` file (or equivalent configuration file for non-Apache web servers) will need to be adjusted accordingly.
</div>

### `reading_speed_wpm`
<div class="config-option">

Reading speed in words per minute used to calculate the estimated reading time of each article. On average, adults can read between 200 and 300 wpm and you can find many tools that can help you determine your reading speed on-line. If set to `0` (the default value), the estimated reading time is not shown.
</div>
