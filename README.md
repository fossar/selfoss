selfoss
=======

Copyright (c) 2013 Tobias Zeising, tobias.zeising@aditu.de  
http://selfoss.aditu.de  
Licensed under the GPLv3 license  
Version 2.12-SNAPSHOT


INSTALLATION
------------

1. Upload all files of this folder (IMPORTANT: also upload the invisible .htaccess files)
2. Make the directories data/cache, data/favicons, data/logs, data/thumbnails, data/sqlite and public/ writeable
3. Insert database access data in config.ini (see below -- you don't have to change anything if you want to use sqlite)
3. You don't have to install the database, it will be created automatically (ensure that your database has enought rights for creating triggers)
4. Create cronjob for updating feeds and point it to http://yourselfossurl.com/update via wget or curl. You can also execute the update.php from commandline.

For further questions or on any problem use our support forum: http://selfoss.aditu.de/forum

CONFIGURATION
-------------

1. Copy defaults.ini to config.ini
2. Edit config.ini and delete any lines you do not wish to override
3. Do not delete the [globals] line
4. See http://selfoss.aditu.de/ for examples


UPDATE
------

1. backup your database and your "data" folder
2. (IMPORTANT: don't delete the "data" folder) delete all old files and folders excluding the folder "data"
3. upload all new files and folders excluding the data folder (IMPORTANT: also upload the invisible .htaccess files)
4. Rename your folder /data/icons into /data/favicons
5. Delete the files /public/all.css and /public/all.js
6. Clean your browser cache
7. insert your current database connection and your individual configuration in config.ini. Important: we change the config.ini and add new options in newer versions. You have to update the config.ini too.
8. The database will be updated automatically (ensure that your database has enought rights for creating triggers)

For further questions or on any problem use our support forum: http://selfoss.aditu.de/forum


OPML Import
-----------

Visit the page http://yourselfossurl.com/opml for importing your OPML File. If you are a user of the google reader then use https://www.google.com/takeout/ to get all your feeds in one opml file.


CHANGELOG
---------

Version 2.12-SNAPSHOT
* fix prefix bug on mysql

Version 2.11
* little fix to Polish translation
* instapaper spout: use HTTPS
* A new spout to get full text for entries in the Teltarif RSS feed
* fix pgsql VACUUM ANALYZE syntax error
* A new spout to get full text for entries in the Lightreading RSS feed
* Multi-language support of search and error fix.
* Make it possible to disable auto stream more, add handy "Mark these read" button
* Use PHP to set the fore color of all tags
* itemsPerPage value is set from INI file.
* API header returns application/json
* added estonian translation
* allow sub and sup elements
* entry CSS tweaks
* REST API : Get only items updated since given time #532
* Bugfix: API REST : /login should return true if auth is disable
* Bugfix: Heise feed pull kills Update process #499
* Bugfix: https for openshift #488
* Bugfix: heise spout error handling #517

Version 2.10
* fix error 500 on icon fetching
* add heise hardware-hacks
* reddit2 spout: fix link to return http
* reddit2 spout: add empty validation on username and password
* setting to lazy load images on mobile devices
* update fat free php framework version 3.2.0
* improve heise spout
* fix duplicate items with MySQL
* fix auto language detection
* save OPML export file with xml extension
* sqlite's "optimize()" was implemented
* sources: show sources with error first
* fix bug on base url determining using https
* support search terms with quotes to find exact phrase like "Windows 8"
* github spout fix (set user agent)
* more opml export logging

Version 2.9
* new configuration parameter for share buttons
* new Ukrainian translation
* fix Italian translation
* new error message bar
* fix php 5.5 bug for some spouts
* fix 'Undefined Index' error in item tpl when no shares available
* add multi reddit support
* avoid duplicate sources while importing OPML
* prevent reflected XSS vulnerability in search form
* add support for fullscreen Webapp on iPhone 5
* added new config parameter (unread_order) to be able to read unread items from oldest to newest
* update twitter api
* the processing of the parameter of the session cookie is updated
* prevent stored XSS vulnerability in the source add form
* sort spouts by name
* allow dd-element and style definition list elements
* new GitHub spout to list commits on a repository
* performance improvement on feed update

Version 2.8
* new Polish translation
* improved Expires section and Compression in .htaccess
* make api item listing, tags and sources stats accessible for non loggedin users in public mode
* update fat free php framework version 3.0.8
* new configuration parameter for default readability api key
* new configuration parameter for allowing unauthorized access for the update job
* new delicious support
* support ssl proxy
* new readability support
* pass original url to external sites except for opening the anonymized url
* new finnish translation
* new spanish translation

Version 2.7
* new spout for instapaper
* new Hungarian translation
* fix keyboard shortcut on some browsers
* new spout for youtube channels
* new rss feed for selfoss releases: http://selfoss.aditu.de/feed.php
* fix bug on removing search terms
* translation for login page
* new japanese language file
* new shortcuts
* fix issues with refreshing the items list and slow ajax requests
* don't leave behind sp-container divs when refreshing the tags
* clean up orphaned items of deleted sources
* update fat free php framework to newest versoin 3.0.6
* only allow update for localhost or loggedin users
* added Facebook page feed
* fix memory bug on icon generation
* new opml export
* new norwegian translation
* set default title if no one was given by the feed

Version 2.6
* fixed OPML import for other formats
* fix deletion of sources (no longer bad request)
* disable tag click on smartphone
* shortcuts mark/unmark as read and star/unstar also available on closed articles
* fix tag list refresh

Version 2.5
* new navigation with right/left cursor
* replace &bullet; for IE compatibility
* fix re-initialize entry events on screen width change
* allow optional userdefined user.css
* some smaller css tweaks
* new parameter use_system_font for using Arial instead of Open Sans
* new italian language file
* fix duplicate article fetching on uids with more than 255 characters
* add integrated json api
* add error handling for feeds with wrong link
* new swedish translation

Version 2.4
* prevent error on icons parsing error
* new homepage parameter
* new button for open an articles source
* no error message if no unread item is available and mark all as read was pressed
* improve logger
* readability is now available as spout instead as global parameter
* new share buttons for google+, twitter and facebook
* mysql use longtext for articles content
* improved detection of mobile devices
* allow more tags in articles content
* show unread items per tag in taglist
* show list of sources for filtering
* use more eye catching unread stats in main navigation
* show source title in selfoss RSS feed
* load sources for update by last update time
* Opening feed search focuses the search input field
* Scroll blockquotes and pre on overflow (especially good for mobile devices)
* new option for automatically mark items as read
* new share buttons for email and pocket
* new shortcut r for reloading the current list
* new internationalization (language files for German, English, French, Turkish, Dutch, Czech, Russian, Latvian, traditional and simplified Chinese included)
* make article id generation more reliable
* fix some font issues
* fix JavaScript error in login screen
* autofocus username on login
* add open in new window button for mobile view
* allow choosing tags in article list
* concurent multiples updates makes no longer duplicates entries
* Reload items on mark as read
* anonymizer support
* selfoss rss feed support tag filtering (?tag=)
* fix tag render bug

Version 2.3
* new shortcut library jquery hotkeys
* new shortcut for mark as read and switch to next in one step
* prevent error on png conversion
* items will be saved in mysql databases also no icon is available
* support of PostgreSQL Database
* now updates by command line are possible
* default charset on mysql is utf8
* new readability support
* link to opml import added
* Use IfMoudule to avoid errors in Etags settings (thanks to vincebusam)
* Allow tag filtering to not include partial matches (thanks to WalterWeight and bsweeney)

Version 2.2
* update fat free php Framework to 3.0.5
* new opml import page

Version 2.1
* security bugfix

Version 2.0
* support of tags
* new user interface
* new interface for mobile devices
* mongodb database interface temporarily removed
* libs and third party plugins updated
* new spout for mmo-spy.de and golem.de with full text

Version 1.3
* search will now also search in the source title (for filtering by source)
* data/icons renamed in data/favicons for preventing mod_rewrite problems on apache
* improved scrolling for very long entries (thanks untitaker)
* Using more restrictive styles on entry content (thanks untitaker)
* redirect to base url on login/logout (thanks untitaker)
* improved base url handling

Version 1.2
* new json API for external software
* support for Android selfoss app
* improved heise spout
* some smaller bugfixes (e.g. increased session timeout)

Version 1.1
* hash password (you can set the salt in the config.ini and you can generate a password with following URL: http://your_selfoss_url.com/password)
* remove unused CSS
* minify JavaScript and CSS and collect them all in one all.js and all.css file
* activate caching and compression in .htaccess (if supported by current apache installation)
* code optimization and smaller bugfixes



CREDITS
-------

Very special thanks to all contributors of pull requests here on github. Your improvements are awesome!!!

Special thanks to the great programmers of this libraries which will be used in selfoss:

* FatFree PHP Framework: http://fatfree.sourceforge.net/
* SimplePie: http://simplepie.org/
* jQuery: http://jquery.com/
* jQuery UI: http://jqueryui.com/
* WideImage: http://wideimage.sourceforge.net/
* htmLawed: http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/
* PHP Universal Feed Generator: http://www.ajaxray.com/blog/2008/03/08/php-universal-feed-generator-supports-rss-10-rss-20-and-atom/
* twitteroauth: https://github.com/abraham/twitteroauth
* floIcon: http://www.phpclasses.org/package/3906-PHP-Read-and-write-images-from-ICO-files.html
* jQuery hotkeys: https://github.com/tzuryby/jquery.hotkeys
* jsmin: https://github.com/rgrove/jsmin-php/blob/master/jsmin.php
* cssmin: http://code.google.com/p/cssmin/
* Spectrum Colorpicker: https://github.com/bgrins/spectrum
* jQuery custom content scroller: http://manos.malihu.gr/jquery-custom-content-scroller/

Icon Source: http://blog.artcore-illustrations.de/aicons/
