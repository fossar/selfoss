# selfoss 2.20-SNAPSHOT

selfoss is a multipurpose RSS reader and feed aggregation web application. It allows you to easily follow updates from different web sites, social networks and other platforms, all in single place. It is written in PHP, allowing you to run it basically anywhere.

For more information visit our [web site](https://selfoss.aditu.de).


## Status

selfoss is currently maintained by Jan Tojnar in his free time. Due to the [limited capacity](https://github.com/jtojnar/jtojnar), maintenance is prioritized over new features. Pull requests are welcome, see the [Contributing](CONTRIBUTING.md) guide.


## Download

* [Stable releases](https://github.com/fossar/selfoss/releases) – if you just want to use selfoss.
* [Development builds](https://cloudsmith.io/~fossar/repos/selfoss-git/packages/) ([latest](https://cloudsmith.io/~fossar/repos/selfoss-git/packages/?q=version%3Alatest)) – if you want to try unreleased features or bug fixes, or help testing them. Hosted by [Cloudsmith](https://cloudsmith.com)
* [Git-tracked source code](https://github.com/fossar/selfoss) – if you want to join selfoss development. Some [assembly](#development) required.


## Installation

1. Upload all files of this directory (IMPORTANT: also upload the invisible `.htaccess` files).
2. Make the directories `data/cache`, `data/favicons`, `data/logs`, `data/thumbnails` and `data/sqlite` writeable.
3. Insert database access data in `config.ini` (see below). You do not need to change anything if you want to use SQLite.
4. You do not need to create the database tables, they will be created automatically (ensure that your database user is allowed to create triggers).
5. Create cronjob or systemd timer for updating feeds and point it to https://yourselfossurl.com/update via wget or curl. You can also execute the `cliupdate.php` from command line.

If you obtained selfoss using Git, some more steps will be required. See the [development](#development) section.

For further questions or on any problem use our support forum: https://forum.selfoss.aditu.de/


## Configuration

No configuration is needed to use selfoss but you can customize the settings as follows:

1. Rename `config-example.ini` to `config.ini`.
2. Edit `config.ini` and delete any lines you do not wish to override.
3. See <https://selfoss.aditu.de/> for examples.


## Update

1. Backup your database and your `data/` directory.
2. (IMPORTANT: do NOT delete the `data/` directory) delete all old files and directories excluding the directory `data/` and the file `config.ini`
3. Upload all new files and directories excluding the `data/` directory (IMPORTANT: also upload the invisible `.htaccess` files).
4. Consult the [NEWS file](NEWS.md) to learn about backwards incompatible changes.
5. Clean your browser cache.
6. Insert your current database connection and your individual configuration in `config.ini`. Important: we change the `config.ini` and add new options in newer versions. You have to update the `config.ini` too.
7. The database will be updated automatically (ensure that your database user is allowed to create triggers).

If you obtained selfoss using Git, some more steps might be required. See the [development](#development) section.

For further questions or on any problem use our support forum: https://selfoss.aditu.de/forum


## Support

* [Issue tracker](https://github.com/fossar/selfoss/issues) for reporting problems and requesting new features
* [Forum](https://forum.selfoss.aditu.de/) for general questions about usage
* [Chat on Gitter](https://gitter.im/fossar/selfoss) (or [`#selfoss:matrix.org` mirror](https://matrix.to/#/#selfoss:matrix.org)) for discussing selfoss development or just about anything


## OPML import

Selfoss supports importing OPML files. Find the OPML export in the old application, it is usually located somewhere in settings. Then visit the page https://yourselfossurl.com/opml and upload it there.


## Third-party Apps

We recommend [Reader For Selfoss](https://f-droid.org/packages/bou.amine.apps.readerforselfossv2.android) for Android devices.


## Development

Selfoss uses [composer](https://getcomposer.org/) and [npm](https://www.npmjs.com/get-npm) for installing external libraries. When you clone the repository you have to issue `composer install` to retrieve the external sources.

For the client side, you will also need JavaScript dependencies installed by calling `npm install` in the `client/` directory. You can use `npm run install-dependencies` as a shortcut for installing both sets of dependencies.

We use [Parcel](https://parceljs.org/) (installed by the command above) to build the client side of selfoss. Every time anything in `client/` directory changes, you will need to run `npm run build` for the client to be built and installed into the `public` directory. When developing, you can also use `npm run dev`; it will watch for asset changes, rebuild the bundles as needed, and reload selfoss automatically. Upon switching between `npm run dev` and `npm run build`, you may need to delete `client/.cache`.

If you want to create a package with all the dependencies bundled, you can run `npm run dist` command to produce a zipball.

Every patch is expected to adhere to our coding style, which is checked automatically by CI. You can install the checkers locally using `npm run install-dependencies`, and then run the checks using `npm run check` before submitting a pull request. There is also `npm run fix`, that will attempt to fix the formatting.

## Credits

selfoss was created by [Tobias Zeising](tobias.zeising@aditu.de), and the source code is licensed under the GNU General Public licence version 3, or (at your option) any later version.

Some parts of the source code can be licensed under version 3 only, we are [currently trying to resolve it](https://github.com/fossar/selfoss/issues/1218).

The package with bundled dependencies might be distributed under version 3 only.

Very special thanks to all contributors of pull requests here on [GitHub](https://github.com/fossar/selfoss), as well as translators on [Weblate](https://hosted.weblate.org/projects/selfoss/translations/). Your improvements are awesome!

Special thanks to the great programmers of these libraries used by selfoss:

* [FatFree PHP Framework](https://fatfreeframework.com/)
* [SimplePie](http://simplepie.org/)
* [WideImage](http://wideimage.sourceforge.net/)
* [htmLawed](http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/)
* [PHP Universal Feed Generator](https://github.com/ajaxray/FeedWriter)
* [Elphin IcoFileLoader](https://github.com/lordelph/icofileloader)
* [Graby](https://github.com/j0k3r/graby)
* [FullTextRSS filters](http://help.fivefilters.org/customer/portal/articles/223153-site-patterns)
* [yet-another-react-lightbox](https://github.com/igordanchenko/yet-another-react-lightbox)

Icon made by http://blackbooze.com/

Package repository hosting is graciously provided by [Cloudsmith](https://cloudsmith.com). Cloudsmith is the only fully hosted, cloud-native, universal package management solution, that enables your organization to create, store and share packages in any format, to any place, with total confidence.
