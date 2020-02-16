# selfoss 2.19-SNAPSHOT

selfoss is a multipurpose RSS reader and feed aggregation web application. It allows you to easily follow updates from different web sites, social networks and other platforms, all in single place. It is written in PHP, allowing you to run it basically anywhere.

For more information visit our [web site](https://selfoss.aditu.de).

## Download

* [Stable releases](https://github.com/SSilence/selfoss/releases) – if you just want to use selfoss.
* [Development builds](https://bintray.com/fossar/selfoss/selfoss-git) ([latest](https://bintray.com/fossar/selfoss/selfoss-git/_latestVersion#files)) – if you want to try unreleased features or bug fixes, or help testing them.
* [Git-tracked source code](https://github.com/SSilence/selfoss) – if you want to join selfoss development. Some [assembly](#development) required.


## Installation

1. Upload all files of this folder (IMPORTANT: also upload the invisible .htaccess files)
2. Make the directories data/cache, data/favicons, data/logs, data/thumbnails and data/sqlite writeable
3. Insert database access data in config.ini (see below -- you don't have to change anything if you want to use sqlite)
4. You don't have to install the database, it will be created automatically (ensure that your database has enought rights for creating triggers)
5. Create cronjob for updating feeds and point it to https://yourselfossurl.com/update via wget or curl. You can also execute the cliupdate.php from commandline.

If you obtained selfoss using Git, some more steps will be required. See the [development](#development) section.

For further questions or on any problem use our support forum: https://selfoss.aditu.de/forum/


## Configuration

1. Copy defaults.ini to config.ini
2. Edit config.ini and delete any lines you do not wish to override
3. Do not delete the [globals] line
4. See https://selfoss.aditu.de/ for examples


## Update

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


## Support

* [Issue tracker](https://github.com/SSilence/selfoss/issues) for reporting problems and requesting new features
* [Forum](https://selfoss.aditu.de/forum/) for general questions about usage
* [Chat on Gitter](https://gitter.im/fossar/selfoss) (or [`#selfoss:matrix.org` mirror](https://matrix.to/#/#selfoss:matrix.org)) for discussing selfoss development or just about anything


## OPML import

Selfoss supports importing OPML files. Find the OPML export in the old application, it is usually located somewhere in settings. Then visit the page https://yourselfossurl.com/opml and upload it there.


## Third-party Apps

We recommend [Reader For Selfoss](https://github.com/aminecmi/readerforselfoss) for Android devices.


## Development

Selfoss uses [composer](https://getcomposer.org/) and [npm](https://www.npmjs.com/get-npm) for installing external libraries. When you clone the repository you have to issue `composer install` to retrieve the external sources.

For the client side, you will also need JavaScript dependencies installed by calling `npm install` in the `assets` directory. You can use `npm run install-dependencies` as a shortcut for installing both sets of dependencies.

We use [Parcel](https://parceljs.org/) (installed by the command above) to build the client side of selfoss. Every time anything in `assets` directory changes, you will need to run `npm run build` for the client to be built and installed into the `public` directory. When developing, you can also use `npm run dev`; it will watch for asset changes, rebuild the bundles as needed, and reload selfoss automatically.

If you want to create a package with all the dependencies bundled, you can run `npm run dist` command to produce a zipball.

Every patch is expected to adhere to our coding style, which is checked automatically by Travis. You can install the checkers locally using `npm run install-dependencies`, and then run the checks using `npm run check` before submitting a pull request. There is also `npm run fix`, that will attempt to fix the formatting.

## Dockerizing selfoss

For instructions how to use Selfoss inside docker container, both for production deployment and for development, see the separate [Readme](utils/docker/Readme.md).

## Credits

selfoss was created by [Tobias Zeising](tobias.zeising@aditu.de) and it is licensed under the GPLv3 license.

Very special thanks to all contributors of pull requests here on [GitHub](https://github.com/SSilence/selfoss), as well as translators on [Weblate](https://hosted.weblate.org/projects/selfoss/translations/). Your improvements are awesome!!!

Special thanks to the great programmers of these libraries used by selfoss:

* [FatFree PHP Framework](https://fatfreeframework.com/)
* [SimplePie](http://simplepie.org/)
* [jQuery](https://jquery.com/)
* [WideImage](http://wideimage.sourceforge.net/)
* [htmLawed](http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/)
* [PHP Universal Feed Generator](https://github.com/ajaxray/FeedWriter)
* [Elphin IcoFileLoader](https://github.com/lordelph/icofileloader)
* [jQuery hotkeys](https://github.com/tzuryby/jquery.hotkeys)
* [Spectrum Colorpicker](https://github.com/bgrins/spectrum)
* [Graby](https://github.com/j0k3r/graby)
* [FullTextRSS filters](http://help.fivefilters.org/customer/portal/articles/223153-site-patterns)

Icon comes from http://www.artcoreillustrations.com/
