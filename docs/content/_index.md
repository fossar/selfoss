+++
title = "selfoss – the open source web based rss reader and multi source mashup aggregator"
+++

# Documentation

## Requirements
<div class="documentation-entry">

selfoss is not a hosted service. It has to be installed on your own webserver. This webserver must fulfil the following requirements (which are available from most providers)

* PHP 5.6 or higher with the php-gd and php-http extensions enabled. Some spouts may also require curl or mbstring extensions. The php-imagick extension is required if you want selfoss to support SVG site icons.
* MySQL 5.5.3 or higher, PostgreSQL, or SQLite
* Apache Webserver (nginx and lighttpd also possible)

Ensure that you have mod_authz_core, mod_rewrite and mod_headers enabled.

selfoss supports all modern browsers, including Mozilla Firefox, Safari, Google Chrome, Opera and Internet Explorer. selfoss also supports mobile browsers on iPad, iPhone, Android and other devices.
</div>

## Installation {#installation}
<div class="documentation-entry">

selfoss is a lightweight php based application. Just follow the simple installation instructions:

1. Upload all files of this folder (IMPORTANT: also upload the invisible .htaccess files)
2. Make the directories data/cache, data/favicons, data/logs, data/thumbnails, data/sqlite and public/ writeable
3. Insert database access data in config.ini (see below – you have not to change anything if you would like to use sqlite)
4. You don't have to install the database, it will be created automatically
5. Create cronjob for updating feeds and point it to https://yoururl.com/update via wget or curl. You can also execute the cliupdate.php from commandline.

For further questions or any problems, use our [support forum](forum). For a more detailed step-by-step example installation, please visit the [wiki](https://github.com/SSilence/selfoss/wiki/).
</div>

## Configuration {#configuration}
<div class="documentation-entry">

Configuration is optional. Any settings in config.ini will override the settings in defaults.ini. To customize settings follow these instructions:

1. Copy defaults.ini to config.ini.
2. Edit config.ini and delete any lines you do not wish to override.
3. Do not delete the `[globals]` line.

Sample config.ini file which provides password protection:

```ini
[globals]
username=secretagent
password=$2y$10$xLurmBB0HJ60.sar1Z38r.ajtkruUIay7rwFRCvcaDl.1EU4epUH6
```

Sample config.ini file with a MySQL database connection:

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

## Update
<div class="documentation-entry">

Read carefully following instructions before you update your selfoss installation:

1. Backup your database and your "data" folder
2. **IMPORTANT: don't delete the "data" folder**. Delete all old files and folders excluding the folder "data".
3. Upload all new files and folders excluding the data folder (IMPORTANT: also upload the invisible .htaccess files).
4. Rename your folder /data/icons into /data/favicons
5. If upgrading from 2.17 or earlier, delete the files <code>/public/all-v<var>*</var>.css</code> and <code>/public/all-v<var>*</var>.js</code>. Additionally, when using `Lighttpd`, please check [the wiki](https://github.com/SSilence/selfoss/wiki/Lighttpd-configuration#upgrading-from-selfoss-217-or-lower).
6. Clean your browser cache.

For further questions or on any problem use our [support forum](forum).
</div>

## Import your feeds from a different RSS reader
<div class="documentation-entry">

Selfoss supports importing OPML files. Find the OPML export in the old application, it is usually located somewhere in settings.
Then visit the page https://yourselfossurl.com/opml and upload it there.
</div>

## Configuration {#configuration_params}
<div class="documentation-entry">

selfoss offers the following configuration parameters. You can set the config parameters in the `config.ini` file.

### `db_type`
<div class="config-option">

database type (`sqlite`, `mysql` or `pgsql`)
</div>

### `db_file`
<div class="config-option">

sqlite databasefile
</div>

### `db_host`
<div class="config-option">

database hostname
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

table prefix for MySQL/SQLite databases
</div>

### `db_port`
<div class="config-option">

port for database connections (3306 for MySQL, 5432 for PostgreSQL)
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

base url of the selfoss page; use this option if you use a ssl proxy which changes the $_SERVER globals, most notably the URL path in which the app is installed.
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

if you use login (username and password is set), you can allow guests to see your stream. Enter 1 for enabling this write-protected mode
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

set this to 1 for automatically marking items as read after you fetched them via rss
</div>

### `homepage`
<div class="config-option">

set here your preferred homepage. Choose between `newest`, `unread` and `starred`. It is also possible to configure a tag (e.g. `unread/tag-yourtag`) or a source (e.g. `newest/source-123`). Default = `newest`.
</div>

### `auto_mark_as_read`
<div class="config-option">

set this to 1 for automatically marking items as read after open/read them.
</div>

### `auto_collapse`
<div class="config-option">

set this to 1 for automatically collapsing items when another one is opened.
</div>

### `auto_stream_more`
<div class="config-option">

set this to 0 to disable autoloading of more items when you scroll down. With 1, a click on a button is required instead.
</div>

### `language`
<div class="config-option">

set 0 or leave empty for auto detection (browser language) or use one of the following language codes:

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

set here your anonymizer service url. e.g.: anonymizer=https://anonym.to/?
</div>

### `allow_public_update_access`
<div class="config-option">

set allow_public_update_access=1 for allowing public access for /update (anybody can access and start the update job).
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
<dt><code>e</code></dt><dd>E-Mail</dd>
<dt><code>c</code></dt><dd>Copy to clipboard</dd>
</dl>

Include the letters for methods you want to use. For example, if you would like to only show Facebook and Twitter share buttons, use `share=ft`.

Defaults to `share=atfpde`.
</div>

### `wallabag`
<div class="config-option">

wallabag url. url to wallabag homepage
</div>

### `wallabag_version`
<div class="config-option">

set this to 1 or 2 depending on your wallabag version (1 for version 1.x or 2 for version 2.x)
</div>

### `unread_order`
<div class="config-option">

set unread_order=asc to read your unread items from the oldest to the newest, leave it empty or to desc to read from the newest to the oldest
</div>

### `load_images_on_mobile`
<div class="config-option">

set load_images_on_mobile=1 for allowing image lazy loading on mobile devices
</div>

### `auto_hide_read_on_mobile`
<div class="config-option">

hide read articles on mobile devices
</div>

### `scroll_to_article_header`
<div class="config-option">

scrolls to the article header after selecting an article (enabled by default)
</div>

### `env_prefix`
<div class="config-option">

only consider ENV variables that start with this prefix as additional config variables. Defaults to "SELFOSS_".
</div>

### `camo_domain`
<div class="config-option">

Camo domain used to proxify images (optional). See [atmos/camo](https://github.com/atmos/camo) for more details
</div>

### `camo_key`
<div class="config-option">

Camo domain used to proxify images (optional). See [atmos/camo](https://github.com/atmos/camo) for more details
</div>

### `show_thumbnails`
<div class="config-option">

If set to false, thumbnails are not shown in the collapsed view. Defaults to true.
</div>

### `datadir` {#datadir}
<div class="config-option">

location of the data directory; especially useful when selfoss is installed to write-protected file system. `.htaccess` file (or equivalent configuration file for non-Apache web servers) will need to be adjusted accordingly.
</div>
</div>

## Shortcuts
<div class="documentation-entry">

selfoss offers some keyboard shortcuts. They are very similar to the google reader shortcuts.

<dl>
<dt><kbd>space</kbd></dt>
<dd>select and open next entry</dd>
<dt><kbd>j</kbd></dt>
<dd>select and open next entry</dd>
<dt><kbd>n</kbd></dt>
<dd>select next entry</dd>
<dt><kbd>→</kbd></dt>
<dd>select next entry (and open it when the current is open)</dd>
<dt><kbd>shift</kbd>+<kbd>space</kbd></dt>
<dd>select and open previous entry</dd>
<dt><kbd>k</kbd></dt>
<dd>select and open previous entry</dd>
<dt><kbd>p</kbd></dt>
<dd>select previous entry</dd>
<dt><kbd>←</kbd></dt>
<dd>select previous entry (and open it when the current is open)</dd>
<dt><kbd>s</kbd></dt>
<dd>mark and unmark current selected entry as starred/unstarred</dd>
<dt><kbd>m</kbd></dt>
<dd>mark and unmark current selected entry as read/unread</dd>
<dt><kbd>t</kbd></dt>
<dd>throw current item (mark as read and open next)</dd>
<dt><kbd>shift</kbd>+<kbd>t</kbd></dt>
<dd>throw current item (mark as read and open previous)</dd>
<dt><kbd>v</kbd></dt>
<dd>open url of current entry in new tab/window</dd>
<dt><kbd>shift</kbd>+<kbd>v</kbd></dt>
<dd>open url of current entry in new tab/window and mark read</dd>
<dt><kbd>ctrl</kbd>+<kbd>m</kbd></dt>
<dd>mark all as read</dd>
<dt><kbd>r</kbd></dt>
<dd>reload the list</dd>
<dt><kbd>o</kbd></dt>
<dd>open / close current item</dd>
<dt><kbd>shift</kbd>+<kbd>o</kbd></dt>
<dd>close all open items</dd>
<dt><kbd>shift</kbd>+<kbd>n</kbd></dt>
<dd>open newest items page</dd>
<dt><kbd>shift</kbd>+<kbd>u</kbd></dt>
<dd>open unread items page</dd>
<dt><kbd>shift</kbd>+<kbd>s</kbd></dt>
<dd>open starred items page</dd>
</dl>
</div>

## Extend
<div class="documentation-entry">

You can easily add your own data sources. Spouts (aka plugins) fetch the content from the different sources. Some spouts are included:

* RSS Feeds
* Images from a RSS Feed
* Images from deviantArt Users
* Images from tumblr
* Your twitter timeline
* Tweets of a twitter user
* heise News with full content
* golem News with full content
* MMOSpy News with full content

If you want to get the newest entries from your own source (e.g. an IMAP Email Account, Log Files or any data from your own application), you can include a new spout in your selfoss stream by writing just one php class (saved in one php file).

Create a new php file under `src/spouts/your_spouts/your_spout.php` (choose a name for `your_spouts` and `your_spout`). The easiest way is to copy the [`src/spouts/rss/feed.php`](https://github.com/SSilence/selfoss/blob/mastersrc/spouts/rss/feed.php) and to modify this file.

### Member Variables
Set the `$name` and `$description` variable with the name and description of your spout. The `$params` contain the definition of the input fields which a user will have to fill to add a new source of your spout (e.g. username and password for accessing the source data).

A simple example for the member variables of a spout for accessing an IMAP email account:

```php
<?php
namespace spouts\mail;
class imap extends \spouts\spout {
public $name = 'Email';
public $description = 'email imap account as source';
public $params = array(
    "email" => array(
    "title"      => "Email",
    "type"       => "text",
    "default"    => "",
    "required"   => true,
    "validation" => array("email")
),
"password" => array(
    "title"      => "Password",
    "type"       => "password",
    "default"    => "",
    "required"   => true,
    "validation" => array("notempty")
),
"host" => array(
    "title"      => "URL",
    "type"       => "text",
    "default"    => "",
    "required"   => true,
    "validation" => array("notempty")
)
);
}
```

### Methods

Your source will have to implement a few methods. Following UML diagram shows the inheritance structure:

![selfoss source UML diagram](images/uml.png)

The class has to implement three things:

* A `load($params)` function will be executed by selfoss when the content will be updated (the https://your-selfoss-url.com/update will be executed). This `load` function has one parameter `$params` which contains the user defined parameters (e.g. username, password or anything which the user has configured (as you can define in the members variable `$params`). This function contains your source code for fetching the data (e.g. loading the emails from an IMAP email account).
* You have to implement the `Iterable` interface. selfoss will use it to iterate over all single entries of your source (e.g. the emails which were fetched by the load function). See [php.net manual (OOP5 iterators)](https://secure.php.net/manual/en/language.oop5.iterations.php) for more informations about this iterator functions.
* selfoss iterates over all the entries by using the iterable interface. selfoss will receive all information about the entries by using the functions defined by the abstract class `\spouts\spout` (e.g. it will get the email subject by executing the `getTitle()` method).

### Thumbnails

If you would like to show thumbnails instead of text, you have to implement the optional method `getThumbnail()`. This method have to return the url of the image. selfoss will load and generate the thumbnail automatically. See [`src/spouts/rss/images.php`](https://github.com/SSilence/selfoss/blob/master/src/spouts/rss/images.php) for an example. This spout searches for an image in an rss feed and returns it.

### Your Spouts

Feel free to send us your own spouts. We are really happy about new sources we can add to further versions of selfoss. You can send them by email to [tobias.zeising@aditu.de](tobias.zeising@aditu.de).
</div>

## API
<div class="documentation-entry">

selfoss offers a restful JSON API for accessing or changing all selfoss data. Just use this API for your selfoss App or any other programm or plugin. Visit this [github wiki page](https://github.com/SSilence/selfoss/wiki/Restful-API-for-Apps-or-any-other-external-access) for a detailed API documentation.
</div>

## License
<div class="documentation-entry">

selfoss is licensed under the [GPLv3 license](https://www.gnu.org/licenses/gpl-3.0.html).

You are allowed to use, modifiy or study this program completely for free. If you need any other licence than GPLv3 then feel free to contact me!
</div>

## About {#about}
<div class="documentation-entry">

selfoss is a project of [Tobias Zeising](http://www.aditu.de) ([tobias.zeising@aditu.de](mailto:tobias.zeising@aditu.de)).

More information about selfoss can be found on my german speaking blog [http://www.aditu.de](http://www.aditu.de).

The icon was created by [Nadja (ArtCore)](http://www.artcoreillustrations.com/), thanks for licensing it as CC.

Selfoss is named by the wonderfull waterfall in Iceland (see [Wikipedia](https://en.wikipedia.org/wiki/Selfoss_(waterfall))). Many single waterfalls converge to produce one big fall. This seems to be a good metaphor for the many sources from the web which will be shown in one stream.

FancyBox on this page is from [fancyapps.com](https://fancyapps.com/fancybox/3/).
</div>
