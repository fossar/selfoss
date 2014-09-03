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
* integrate fancybox for images
* improved twitter integration
* show authors of feeds
* wallbag support added
* update third party libraries
* allow ajax based update of feeds
* fix utf8 feed title bug
* fix #405: extra spaces on login screen
* fix #518: sorting of sources


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
* PHP Universal Feed Generator: https://github.com/ajaxray/FeedWriter
* twitteroauth: https://github.com/abraham/twitteroauth
* floIcon: http://www.phpclasses.org/package/3906-PHP-Read-and-write-images-from-ICO-files.html
* jQuery hotkeys: https://github.com/tzuryby/jquery.hotkeys
* jsmin: https://github.com/rgrove/jsmin-php
* cssmin: http://code.google.com/p/cssmin/
* Spectrum Colorpicker: https://github.com/bgrins/spectrum
* jQuery custom content scroller: http://manos.malihu.gr/jquery-custom-content-scroller/
* twitter oauth library: https://github.com/abraham/twitteroauth

Icon Source: http://blog.artcore-illustrations.de/aicons/
