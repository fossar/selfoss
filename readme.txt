selfoss
http://selfoss.aditu.de
tobias.zeising@aditu.de
Version 1.2
License: GPLv3
Icon Source: http://blog.artcore-illustrations.de/aicons/


------------
INSTALLATION
------------

1. upload all files of this folder (IMPORTANT: also upload the invisible .htaccess files)
2. make the directories data/cache, data/icons, data/logs, data/thumbnails and public/ writeable
3. insert database access data in config.ini
4. create cronjob for updating feeds and point it to http://<selfoss url>/update via wget or curl.


------
UPDATE
------

1. backup your database and your "data" folder
2. (IMPORTANT: don't delete the "data" folder) delete all old files and folders excluding the folder "data"
3. upload all new files and folders excluding the data folder (IMPORTANT: also upload the invisible .htaccess files)
4. Clean your browser cache
5. insert your current database connection and your individual configuration in config.ini. Important: we change the config.ini and add new options in newer versions. You have to update the config.ini too.


---------
CHANGELOG
---------

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


-------
CREDITS
-------

Special thanks to the great programmers of this libraries which will be used in selfoss:

* FatFree PHP Framework: http://fatfree.sourceforge.net/
* Elastic CSS Framework: http://elasticss.com/
* HTML5 Boilerplate.com: http://html5boilerplate.com/
* SimplePie: http://simplepie.org/
* jQuery: http://jquery.com/
* WideImage: http://wideimage.sourceforge.net/
* iScroll: http://cubiq.org/iscroll
* htmLawed: http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/
* PHP Universal Feed Generator: http://www.ajaxray.com/blog/2008/03/08/php-universal-feed-generator-supports-rss-10-rss-20-and-atom/
* twitteroauth: https://github.com/abraham/twitteroauth
* floIcon: http://www.phpclasses.org/package/3906-PHP-Read-and-write-images-from-ICO-files.html
* modernizr: http://www.modernizr.com/
* keyboard shortcuts: http://www.openjs.com/scripts/events/keyboard_shortcuts/
* jsmin: https://github.com/rgrove/jsmin-php/blob/master/jsmin.php
* cssmin: http://code.google.com/p/cssmin/

Libraries used for the Android App:

* phonegap: http://www.phonegap.com
* jQuery Mobile: http://jquerymobile.com/