+++
title = "selfoss – the open source web based rss reader and multi source mashup aggregator"
+++

# Documentation

## Requirements {#requirements}
<div class="documentation-entry">

selfoss is not a hosted service. It has to be installed on your own web server. This web server must fulfil the following requirements (which are available from most providers)

* PHP 5.6 or higher with the `php-gd` and `php-http` extensions enabled. Some spouts may also require `curl` or `mbstring` extensions. The `php-imagick` extension is required if you want selfoss to support SVG site icons.
* MySQL 5.5.3 or higher, PostgreSQL, or SQLite
* Apache web server (nginx and Lighttpd also possible)

With Apache, ensure that you have `mod_authz_core`, `mod_rewrite` and `mod_headers` enabled.

selfoss supports all modern browsers, including Mozilla Firefox, Safari, Google Chrome, Opera and Internet Explorer. selfoss also supports mobile browsers on iPad, iPhone, Android and other devices.
</div>

## Installing selfoss {#installation}
<div class="documentation-entry">

selfoss is a lightweight php based application. Just follow the simple installation instructions:

1. Upload all files in the selfoss directory (IMPORTANT: also upload the hidden `.htaccess` files)
2. Make the directories `data/cache`, `data/favicons`, `data/logs`, `data/thumbnails` and `data/sqlite` writeable
3. Insert database access data in `config.ini` (see [below](#configuration_params) – you do not have to change anything if you would like to use SQLite.)
4. You do not need to create database tables, they will be created automatically.
5. Create cron job for updating feeds and point it to https://yoururl.com/update via `wget` or `curl`. You can also execute the `cliupdate.php` from command line.

For further questions or any problems, use our [support forum](forum). For a more detailed step-by-step example installation, please visit the [wiki](https://github.com/SSilence/selfoss/wiki/).
</div>

## Configuring selfoss {#configuration}
<div class="documentation-entry">

All configuration options are optional. Any settings in `config.ini` will override the settings in `defaults.ini`. To customize settings follow these instructions:

1. Copy `defaults.ini` to `config.ini`.
2. Edit `config.ini` and delete any lines you do not wish to override.
3. Do not delete the `[globals]` line.

Sample `config.ini` file which provides password protection:

```ini
[globals]
username=secretagent
password=$2y$10$xLurmBB0HJ60.sar1Z38r.ajtkruUIay7rwFRCvcaDl.1EU4epUH6
```

Sample `config.ini` file with a MySQL database connection:

```ini
[globals]
db_type=mysql
db_host=localhost
db_database=selfoss
db_username=secretagent
db_password=life0fD4ng3r
db_port=3306
```
</div>

## Importing feeds from a different RSS reader {#importing}
<div class="documentation-entry">

selfoss supports importing OPML files. Find the OPML export in the old application, it is usually located somewhere in settings.
Then visit the page `https://your-selfoss-url.com/opml` and upload it there.
</div>

## Updating selfoss {#updating}
<div class="documentation-entry">

Read carefully following instructions before you update your selfoss installation:

1. Backup your database and your `data/` directory
2. **IMPORTANT: do not delete the `data/` directory**. Delete all old files and folders excluding the directory `data/`.
3. Upload all new files and folders excluding the `data/` directory (IMPORTANT: also upload the hidden `.htaccess` files).
4. If upgrading from 1.3 or earlier, rename your directory `/data/icons` into `/data/favicons`
5. If upgrading from 2.17 or earlier, delete the files <code>/public/all-v<var>*</var>.css</code> and <code>/public/all-v<var>*</var>.js</code>. Additionally, when using Lighttpd, please check [the wiki](https://github.com/SSilence/selfoss/wiki/Lighttpd-configuration#upgrading-from-selfoss-217-or-lower).
6. Clean your browser cache.

For further questions or on any problem use our [support forum](forum).
</div>

## Configuration options {#configuration_params}
<div class="documentation-entry">

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

days until items will be deleted (starred items will never be deleted)
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

set this to `0` to disable automatic loading of more items when you scroll down. With `1`, a click on a button is required instead.
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
* German: `de`
* Hungarian: `hu`
* Italian: `it`
* Japanese: `ja`
* Latvian: `lv`
* Norwegian Bokmål: `nb`
* Polish: `pl`
* Portuguese (Brazil): `pt-BR`
* Romansh: `rm`
* Russian: `ru`
* Slovak: `sk`
* Spanish: `es`
* Swedish: `sv`
* Turkish: `tr`
* Ukrainian: `uk`
</div>

### `anonymizer`
<div class="config-option">

Set here your anonymizer service url. e.g.: `anonymizer=https://anonym.to/?`
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
<dt><code>w</code></dt><dd>Wallabag</dd>
<dt><code>e</code></dt><dd>E-mail</dd>
<dt><code>c</code></dt><dd>Copy to clipboard</dd>
</dl>

Include the letters for methods you want to use. For example, if you would like to only show Facebook and Twitter share buttons, use `share=ft`.

Defaults to `share=atfpde`.
</div>

### `wallabag`
<div class="config-option">

URL of your [Wallabag](https://www.wallabag.org/) instance.
</div>

### `wallabag_version`
<div class="config-option">

Set to `1` or `2` depending on your wallabag version (`1` for version 1.x or `2` for version 2.x).
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
</div>

