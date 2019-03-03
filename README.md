selfoss
=======

Copyright ⓒ 2015 Tobias Zeising, tobias.zeising@aditu.de  
https://selfoss.aditu.de  
Licensed under the GPLv3 license  
Version 2.19-SNAPSHOT

DOWNLOAD
--------

* [Stable releases](https://github.com/SSilence/selfoss/releases) – if you just want to use selfoss.
* [Development builds](https://bintray.com/fossar/selfoss/selfoss-git) ([latest](https://bintray.com/fossar/selfoss/selfoss-git/_latestVersion#files)) – if you want to try unreleased features or bug fixes, or help testing them.
* [Git-tracked source code](https://github.com/SSilence/selfoss) – if you want to join selfoss development. Some [assembly](#development) required.

INSTALLATION
------------

1. Upload all files of this folder (IMPORTANT: also upload the invisible .htaccess files)
2. Make the directories data/cache, data/favicons, data/logs, data/thumbnails and data/sqlite writeable
3. Insert database access data in config.ini (see below -- you don't have to change anything if you want to use sqlite)
3. You don't have to install the database, it will be created automatically (ensure that your database has enought rights for creating triggers)
4. Create cronjob for updating feeds and point it to https://yourselfossurl.com/update via wget or curl. You can also execute the cliupdate.php from commandline.

If you obtained selfoss using Git, some more steps will be required. See the [development](#development) section.

For further questions or on any problem use our support forum: https://selfoss.aditu.de/forum/

CONFIGURATION
-------------

1. Copy defaults.ini to config.ini
2. Edit config.ini and delete any lines you do not wish to override
3. Do not delete the [globals] line
4. See https://selfoss.aditu.de/ for examples


UPDATE
------

1. Backup your database and your "data" folder
2. (IMPORTANT: don't delete the "data" folder) delete all old files and folders excluding the folder "data" and the file config.ini
3. Upload all new files and folders excluding the data folder (IMPORTANT: also upload the invisible .htaccess files)
4. Consult the [NEWS file](NEWS.md) to learn about backwards incompatible changes.
5. Rename your folder /data/icons into /data/favicons
6. Clean your browser cache
7. Insert your current database connection and your individual configuration in config.ini. Important: we change the config.ini and add new options in newer versions. You have to update the config.ini too.
8. The database will be updated automatically (ensure that your database has enought rights for creating triggers)

If you obtained selfoss using Git, some more steps might be required. See the [development](#development) section.

For further questions or on any problem use our support forum: https://selfoss.aditu.de/forum


SUPPORT
-------

* [Issue tracker](https://github.com/SSilence/selfoss/issues) for reporting problems and requesting new features
* [Forum](https://selfoss.aditu.de/forum/) for general questions about usage
* [Chat](https://gitter.im/fossar/selfoss) for discussing selfoss development


OPML Import
-----------

Selfoss supports importing OPML files. Find the OPML export in the old application, it is usually located somewhere in settings. Then visit the page https://yourselfossurl.com/opml and upload it there.


APPS
----

Two third party apps are available for Android: [Selfoss](https://play.google.com/store/apps/details?id=fr.ydelouis.selfoss) and [Reader For Selfoss](https://play.google.com/store/apps/details?id=apps.amine.bou.readerforselfoss).


DEVELOPMENT
-----------

Selfoss uses [composer](https://getcomposer.org/) and [npm](https://www.npmjs.com/get-npm) for installing external libraries. When you clone the repository you have to issue `composer install` to retrieve the external sources.

For the client side, you will also need JavaScript dependencies installed by calling `npm install` in the `assets` directory. You can use `npm run install-dependencies` as a shortcut for installing both sets of dependencies.

If you want to create a package with all the dependencies bundled, you can run `npm run dist` command to produce a zipball.

Every patch is expected to adhere to our coding style, which is checked automatically by Travis. You can install the checkers locally either with your package manager or by calling `utils/install-phars.sh`, and then run the checks using `npm run check` before submitting a pull request.

CREDITS
-------

Very special thanks to all contributors of pull requests here on github. Your improvements are awesome!!!

Special thanks to the great programmers of this libraries which will be used in selfoss:

* [FatFree PHP Framework](https://fatfreeframework.com/)
* [SimplePie](http://simplepie.org/)
* [jQuery](https://jquery.com/)
* [WideImage](http://wideimage.sourceforge.net/)
* [htmLawed](http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/)
* [PHP Universal Feed Generator](https://github.com/ajaxray/FeedWriter)
* [twitteroauth](https://github.com/abraham/twitteroauth)
* [Elphin IcoFileLoader](https://github.com/lordelph/icofileloader)
* [jQuery hotkeys](https://github.com/tzuryby/jquery.hotkeys)
* [Spectrum Colorpicker](https://github.com/bgrins/spectrum)
* [jQuery custom content scroller](http://manos.malihu.gr/jquery-custom-content-scroller/)
* [twitter oauth library](https://github.com/abraham/twitteroauth)
* [FullTextRSS](http://help.fivefilters.org/customer/portal/articles/223153-site-patterns)
* [Graby](https://github.com/j0k3r/graby)

Icon comes from http://www.artcoreillustrations.com/
