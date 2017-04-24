# selfoss news
## 2.19 – unreleased
**This version requires PHP 5.6 or newer.**

### New features
- Thumbnails can be disabled ([#897](https://github.com/SSilence/selfoss/pull/897))
- Reddit spout replaced fragile imgur heuristics with previews provided by the JSON API ([#1033](https://github.com/SSilence/selfoss/pull/1033))
- Support for **using selfoss offline** was added. Note that this is only available in [secure contexts](https://developer.mozilla.org/en-US/docs/Web/Security/Secure_Contexts), that is, over HTTPS. ([#1014](https://github.com/SSilence/selfoss/issues/1014))

### Bug fixes
- Reddit spout allows wider range of URLs, including absolute URLs and searches ([#1033](https://github.com/SSilence/selfoss/pull/1033))
- Improved compatibility with PHP 7.2 ([#1049](https://github.com/SSilence/selfoss/issues/1049))
- `logger_level=NONE` is now handled correctly ([#1077](https://github.com/SSilence/selfoss/issues/1077))
- URLs containing special characters like commas in query string are now handled correctly ([#1082](https://github.com/SSilence/selfoss/pull/1082))

### API changes
- `tags` attribute is now consistently array of strings, numbers are numbers and booleans are booleans. **This might break third-party clients that have not updated yet.** ([#948](https://github.com/SSilence/selfoss/pull/948))

### Customization changes
- `selfoss.shares.register` changed its signature: it no longer takes a boolean argument, and the callback is expected to open a window itself, instead of returning a URL. For example, if you previously had
  ```javascript
  selfoss.shares.register('moo', 'm', true, function(url, title) {
      return 'http://moo.foobar/share?u=' + encodeURIComponent(url) + '&t=' + encodeURIComponent(title);
  });
  ```

  in your `user.js` file, you will need to change it to

  ```javascript
  selfoss.shares.register('moo', 'm', function(url, title) {
      window.open('http://moo.foobar/share?u=' + encodeURIComponent(url) + '&t=' + encodeURIComponent(title));
  });
  ```

  ([#1017](https://github.com/SSilence/selfoss/pull/1017))

### Other changes
- Removed broken instapaper scraping from Reddit spout ([#1033](https://github.com/SSilence/selfoss/pull/1033))
- RSS feed will be fetched more reliably ([#1052](https://github.com/SSilence/selfoss/pull/1052))
- More of user interface is now translatable ([#1054](https://github.com/SSilence/selfoss/pull/1054))
- Open Sans font is no longer bundled, resulting in smaller installations. Additionally, `use_system_font` option was removed. The typeface is still set as the default font family, so if you want to use it, install it to your devices. If you want to use a different typeface, add `body { font-family: 'Foo Face'; }` to your `user.css`. ([#1072](https://github.com/SSilence/selfoss/pull/1072))
- The file name of exported sources now includes a timestamp ([#1078](https://github.com/SSilence/selfoss/pull/1078))


## 2.18 – 2018-03-05
### New features
- Full-text RSS spout is now able to extract content from PDFs ([#897](https://github.com/SSilence/selfoss/pull/897))
- URL is no longer cleaned when changing spout ([#906](https://github.com/SSilence/selfoss/pull/906))
- It is possible to set tag or source to be opened after user logs in ([#927](https://github.com/SSilence/selfoss/pull/927))
- Displaying multiple images from tweets with galleries is supported ([#934](https://github.com/SSilence/selfoss/pull/934))
- Quoted tweets are supported ([#934](https://github.com/SSilence/selfoss/pull/934))
- Logging destination can be changed ([#1004](https://github.com/SSilence/selfoss/pull/1004))

### Bug fixes
- Fixed Full-text RSS spout ([#897](https://github.com/SSilence/selfoss/pull/897))
- It is now unlikely that the client browser gets outdated JS or CSS ([#907](https://github.com/SSilence/selfoss/pull/907)) On Lighttpd, you might need to [update your configuration](https://github.com/SSilence/selfoss/wiki/Lighttpd-configuration#upgrading-from-selfoss-217-or-lower).
- Fixed back button not working correctly on small screens ([#906](https://github.com/SSilence/selfoss/pull/906))
- When using PostgreSQL, vacuuming is left to the database ([#906](https://github.com/SSilence/selfoss/pull/906))
- Items from different spouts but with the same uid will not be ignored anymore ([#906](https://github.com/SSilence/selfoss/pull/906))
- GitHub spout was modified to correctly escape the data ([#906](https://github.com/SSilence/selfoss/pull/906))
- YouTube spout was changed to allow wider range of URLs ([#915](https://github.com/SSilence/selfoss/pull/915))
- The items without a date will no longer be added again after clean-up ([#914](https://github.com/SSilence/selfoss/pull/914))
- Changed favicon fetcher for RSS spout to be more resilient ([#920](https://github.com/SSilence/selfoss/pull/920))
- Tweets are no longer truncated ([#934](https://github.com/SSilence/selfoss/pull/934))
- Using arrow keys in photo galleries will no longer change opened item ([#942](https://github.com/SSilence/selfoss/pull/942))
- Facebook spout is finally working again ([#936](https://github.com/SSilence/selfoss/pull/936))
- PSR-4 autoloading is now used, fixing the compatibility with custom spouts. **If you use custom spouts**, please make sure to [check compliance](https://github.com/SSilence/selfoss/pull/959). ([#959](https://github.com/SSilence/selfoss/pull/959))

### Other changes
- Fixed compatibility with PHP 7.2 ([#1005](https://github.com/SSilence/selfoss/pull/1005))
- Improved translations ([#932](https://github.com/SSilence/selfoss/pull/932), [#981](https://github.com/SSilence/selfoss/pull/981), [#985](https://github.com/SSilence/selfoss/pull/985), [#1003](https://github.com/SSilence/selfoss/pull/1003))
- Changed library for handling ico files ([#926](https://github.com/SSilence/selfoss/pull/926))
- Upgraded FancyBox, the gallery looks much slicker now ([#942](https://github.com/SSilence/selfoss/pull/942))
- **For developers**: JavaScript libraries now have to be obtained using NPM ([#942](https://github.com/SSilence/selfoss/pull/942))
- Login is now done using AJAX, a step towards progressive web app ([#931](https://github.com/SSilence/selfoss/pull/931))
- Guzzle is used for HTTP requests making them more reliable ([#936](https://github.com/SSilence/selfoss/pull/936))
- Ironed out some inconsistencies in database schema ([#955](https://github.com/SSilence/selfoss/pull/955))
- **For developers**: JavaScript client code is now checked using eslint ([#951](https://github.com/SSilence/selfoss/pull/951))
- Increased resolution of the favicon ([#961](https://github.com/SSilence/selfoss/pull/961))
- Added warning when autoloader is missing ([#957](https://github.com/SSilence/selfoss/pull/957))
- Removed redundant alt attribute from favicons ([#978](https://github.com/SSilence/selfoss/pull/978))
- Favicons are now easier to click on mobile ([#992](https://github.com/SSilence/selfoss/pull/992))
- Tables that do not fit into columns will now show a scrollbar ([#1001](https://github.com/SSilence/selfoss/pull/1001))
- **For developers**: Coding style and other code requirements can be easily checked using `grunt check`. ([#943](https://github.com/SSilence/selfoss/pull/943))
- Warning will be logged when icon/thumbnail directories are not writeable ([#1009](https://github.com/SSilence/selfoss/pull/1009))
- Removed readability spout and sharing ([#1012](https://github.com/SSilence/selfoss/pull/1012))


## 2.17 – 2017-03-17
### New features
- Spout title can be fetched automatically ([#851](https://github.com/SSilence/selfoss/pull/851))
- selfoss is now navigable ([#869](https://github.com/SSilence/selfoss/pull/869))
- Refreshing the sources using the button no longer blocks the user interface ([#846](https://github.com/SSilence/selfoss/pull/846))
- State of the items is synced periodically ([#846](https://github.com/SSilence/selfoss/pull/846))
- Added option for sharing with Wallabag 2 ([#887](https://github.com/SSilence/selfoss/pull/887))

### Notable changes
- Composer is used for dependency management, if you downloaded selfoss from git repository you will need to use composer for installing dependencies. ([#845](https://github.com/SSilence/selfoss/pull/845))
- Simplified detecting selfoss root URL which should fix some cookie problems ([#889](https://github.com/SSilence/selfoss/pull/889))
- Made the `db_port` configuration key optional ([#843](https://github.com/SSilence/selfoss/pull/843))
- Migrated to `.htaccess` to Apache 2.4 syntax ([#833](https://github.com/SSilence/selfoss/pull/833))

### Bug fixes
- Fixed YouTube spout ([#842](https://github.com/SSilence/selfoss/pull/842))
- DeviantArt, Reddit, Golem and Twitter spouts changed to use HTTPS ([#835](https://github.com/SSilence/selfoss/pull/835))
- Fixed reddit spout redirects ([#835](https://github.com/SSilence/selfoss/pull/835))
- Fixed Wordpress emoji size on HTTPS sites ([#835](https://github.com/SSilence/selfoss/pull/835))
- Fixed twitter links when tweet contains `<` ([#852](https://github.com/SSilence/selfoss/pull/852))
- Fixed encoding problems caused by camo ([#826](https://github.com/SSilence/selfoss/pull/826))
- Fixed “$HTTP_RAW_POST_DATA is deprecated” error when updating a single source ([#841](https://github.com/SSilence/selfoss/pull/841))
- Fixed twitter spout error reporting ([#847](https://github.com/SSilence/selfoss/pull/847))
- Improved error reporting for reddit spout ([#860](https://github.com/SSilence/selfoss/pull/860))
- Removed the need for MySQL 5.6; MySQL 5.5.3 or greater is now required again ([#863](https://github.com/SSilence/selfoss/pull/863))
- Made RSS feed generated by selfoss valid ([#862](https://github.com/SSilence/selfoss/pull/862))
- Fixed [#774](https://github.com/SSilence/selfoss/pull/774) “Incorrectly calculated offset for loading new items” ([#869](https://github.com/SSilence/selfoss/pull/869))
- Fixed code listings overflowing to different column ([#889](https://github.com/SSilence/selfoss/pull/889))
