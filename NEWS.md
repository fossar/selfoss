# selfoss news
## 2.20 – unreleased

**This version currently requires PHP 7.4 or newer. (Might be increased later.)**

### New features
- YouTube spout now accepts handles (starting with `@` sign). ([#1412](https://github.com/fossar/selfoss/pull/1412))
- YouTube spout now includes the video description. ([#1412](https://github.com/fossar/selfoss/pull/1412))
- Mastodon share button was added. Can be enabled by adding `m` to `share` and setting `mastodon` pointing to your chosen instance. ([#1421](https://github.com/fossar/selfoss/pull/1421))
- Source filters can be negated, or limited to only title or only content. ([#1423](https://github.com/fossar/selfoss/pull/1423))
- Sources can be filtered based on item’s author, URL or categories. ([#1423](https://github.com/fossar/selfoss/pull/1423), [#1424](https://github.com/fossar/selfoss/pull/1424))
- Source filter expression is now validated whenever a source is modified. ([#1423](https://github.com/fossar/selfoss/pull/1423))
- Garbage collection can be completely disabled by setting `items_lifetime=0`.
- Tamil (`ta`) translation was added.

### Bug fixes
- Configuration parser was changed to *raw* method, which relaxes the requirement to quote option values containing special characters in `config.ini`. ([#1371](https://github.com/fossar/selfoss/issues/1371))
- Fix “Mark all as read” button not hiding marked articles in unread view, not updating the unread counts in menu properly, and not closing menu on mobile. ([#1388](https://github.com/fossar/selfoss/issues/1388)
- Re-added “Next” button on smartphones. ([#1406](https://github.com/fossar/selfoss/issues/1406)
- Fix compressed SVG (svgz) support. ([#1418](https://github.com/fossar/selfoss/pulls/1418)
- Fix article links containing HTML-special characters. ([#1407](https://github.com/fossar/selfoss/issues/1407))
- Reduce the chance of “Update all sources” button timing out. ([#1428](https://github.com/fossar/selfoss/pulls/1428), [#1430](https://github.com/fossar/selfoss/pulls/1430))
- Fix a log-in loop in client. ([#1429](https://github.com/fossar/selfoss/pulls/1429))
- Fix errors in Firefox’s private browsing mode.
- Fix exporting OPML when there are tags that look like numbers ([#1439](https://github.com/fossar/selfoss/pull/1439))
- Fix incorrect handling of tags in MySQL backend, which could result in OPML export being broken ([#1439](https://github.com/fossar/selfoss/pull/1439))
- Fix sharing to Wallabag 2. ([#1465](https://github.com/fossar/selfoss/pull/1465))
- Fix DB migration with SQLite that has double-quote string literals disabled (like on FreeBSD). ([#1489](https://github.com/fossar/selfoss/pull/1489))

### Customization changes
- Custom spouts must explicitly pass `null` to `Item::__construct()` when they do not need the `extraData` argument. ([#1415](https://github.com/fossar/selfoss/pull/1415))
- Custom spout parameter declarations should now use constants from `Parameter` interface. ([#1409](https://github.com/fossar/selfoss/pull/1409))
- Custom spouts are expected to pass `HtmlString` object to items’ title and content. ([#1368](https://github.com/fossar/selfoss/pull/1368))
- Spouts can fetch item contents lazily by passing a function as `content` to `Item`. ([#1413](https://github.com/fossar/selfoss/pull/1413))
- Spouts’ `name`, `description` and `params` properties now require a type hint. ([#1425](https://github.com/fossar/selfoss/pull/1425))

### Other changes
- `tidy` PHP extension is now required if you want to use “Content extractor” spout. ([#1392](https://github.com/fossar/selfoss/pull/1392))
- Password hashing helper page will delegate the hashing to server again. ([#1401](https://github.com/fossar/selfoss/pull/1401))
- Content Extraction spout will no longer try to extract content we have already extracted. ([#1413](https://github.com/fossar/selfoss/pull/1413))
- Source filters are stricter, they need to start and end with a `/`. ([#1423](https://github.com/fossar/selfoss/pull/1423))
- OPML importer has been merged into the React client. ([#1442](https://github.com/fossar/selfoss/pull/1442))
- Web requests will send `Accept-Encoding` header. ([#1482](https://github.com/fossar/selfoss/pull/1482))
- Authentication system has been rewritten to allow more methods in the future. ([#1491](https://github.com/fossar/selfoss/pull/1491))
- Authentication will now also log user out when the credentials in the config change. ([#1491](https://github.com/fossar/selfoss/pull/1491))
- Requests from loopback IP address now give full access to all operations, not just update. Additionally, IPv6 loopback address is recognized and proxies are ignored. ([#1491](https://github.com/fossar/selfoss/pull/1491))

#### For developers
- Back-end source code is now checked using [PHPStan](https://phpstan.org/). ([#1409](https://github.com/fossar/selfoss/pull/1409))
- [Prettier](https://prettier.io/) is now used for code formatting. ([#1493](https://github.com/fossar/selfoss/pull/1493))
- Several `npm run` scripts were renamed for consistency: `analyse:server` → `check:server:phpstan`, `cs:server` → `check:server:cs`, `lint:server` → `check:server:lint`. ([#1494](https://github.com/fossar/selfoss/pull/1494))


## 2.19 – 2022-10-12
**This version requires PHP ~~5.6~~ 7.2 (see known regressions section) or newer. It is also the last version to support PHP 7.**

### Known regressions
- Values in `config.ini` containing special characters need to be quoted. Will be fixed by <https://github.com/fossar/selfoss/commit/ba9339372a7bc0678c6c1f74336406ab1bbb4ecb>.
- Updating sources that already contain items will fail on PHP < 7.2.0. Will be fixed by <https://github.com/fossar/selfoss/commit/d6e9bc8b01a7d58630772f6dc9938e88a28be706>.
- Updating RSS sources without a valid date fails. Will be fixed by [#1385](https://github.com/fossar/selfoss/pull/1385).

### New features
- Thumbnails can be disabled ([#897](https://github.com/fossar/selfoss/pull/897))
- Reddit spout replaced fragile imgur heuristics with previews provided by the JSON API ([#1033](https://github.com/fossar/selfoss/pull/1033))
- Experimental support for **using selfoss offline** was added. Note that this is only available in [secure contexts](https://developer.mozilla.org/en-US/docs/Web/Security/Secure_Contexts), that is, over HTTPS, and can be very buggy. ([#1014](https://github.com/fossar/selfoss/issues/1014))
- Long articles that do not fit on a single screen will no longer be arranged into columns, allowing for smoother reading experience ([#1081](https://github.com/fossar/selfoss/pull/1081))
- Diaspora share button was added, you can enable it with `d`. ([#1121](https://github.com/fossar/selfoss/pull/1121))
- “Copy to clipboard” share button was added, you can enable it with `c`. ([#1142](https://github.com/fossar/selfoss/pull/1142))
- [Native sharer](https://developer.mozilla.org/en-US/docs/Web/API/Navigator/share) is available in secure contexts in browsers that support it. You can enable it by adding `a` to `share` key in your config. ([#1035](https://github.com/fossar/selfoss/pull/1035))
- Data directory can be configured ([#1043](https://github.com/fossar/selfoss/pull/1043))
- New spout for searching Twitter (e.g. following hashtags) was added. ([#1213](https://github.com/fossar/selfoss/pull/1213))
- Added option `reading_speed_wpm` for showing estimated reading time, set it to the *number of words you can read in a minute*. ([#1232](https://github.com/fossar/selfoss/pull/1232))
- Added option `db_socket` for connecting to MySQL database through UNIX domain. ([#1284](https://github.com/fossar/selfoss/pull/1284))
- Search query is now part of URL. ([#1216](https://github.com/fossar/selfoss/pull/1216))
- A page that will pre-fill a form for adding a source with URL has been added. You can find it on `https://yourselfossurl.com/manage/sources/add?url=some-feed-url`. ([#1310](https://github.com/fossar/selfoss/pull/1310), [#254](https://github.com/fossar/selfoss/issues/254))
- Search will be carried out using regular expressions when the search query is wrapped in forward slashes, e.g. `/regex/`. The expression syntax is database specific. ([#1205](https://github.com/fossar/selfoss/pull/1205))
- YouTube spout now supports following playlists. ([#1260](https://github.com/fossar/selfoss/pull/1260))
- Confirmation is now required when leaving the setting page with unsaved source changes. ([#1300](https://github.com/fossar/selfoss/pull/1300))
- Add link from settings page to individual sources and vice versa. ([#1329](https://github.com/fossar/selfoss/pull/1329), [#1340](https://github.com/fossar/selfoss/pull/1340))
- Tag colour can be now changed using keyboard. ([#1335](https://github.com/fossar/selfoss/pull/1335))
- YouTube spout now supports all YouTube URLs that provide feeds. ([#1273](https://github.com/fossar/selfoss/issues/1273))
- Add `open_in_background_tab` option to try to make <kbd>v</kbd> shortcut open articles in a background tab ([does not work in Chromium-based browsers](https://crbug.com/431335)). ([#1354](https://github.com/fossar/selfoss/pull/1354))
- GitHub sources now include author. ([#1367](https://github.com/fossar/selfoss/pull/1367))
- Twitter sources now indicate author using the author field rather than including in the title. ([#1367](https://github.com/fossar/selfoss/pull/1367))
- Translations into several new languages were added:
  - English (United Kingdom): `en-GB`
  - French (Canada): `fr-CA`
  - Galician: `gl`
  - Hebrew: `he`
  - Indonesian: `id`
  - Portuguese (European): `pt`

### Bug fixes
- Reddit spout allows wider range of URLs, including absolute URLs and searches ([#1033](https://github.com/fossar/selfoss/pull/1033))
- Improved compatibility with newer versions of PHP ([#1049](https://github.com/fossar/selfoss/issues/1049), [#1157](https://github.com/fossar/selfoss/issues/1157), [#1236](https://github.com/fossar/selfoss/issues/1236), [#1294](https://github.com/fossar/selfoss/issues/1294))
- `logger_level=NONE` is now handled correctly ([#1077](https://github.com/fossar/selfoss/issues/1077))
- URLs containing special characters like commas in query string are now handled correctly ([#1082](https://github.com/fossar/selfoss/pull/1082))
- Set 60 second timeout to spout HTTP requests to prevent a single feed blocking other updates ([#1104](https://github.com/fossar/selfoss/issues/1104))
- Significantly improved accessibility ([#1133](https://github.com/fossar/selfoss/pull/1133), [#1134](https://github.com/SSilence/selfoss/pull/1134), [#1141](https://github.com/SSilence/selfoss/pull/1141) and [#1345](https://github.com/SSilence/selfoss/pull/1345))
- Fixed marking more than 1000 items as read at the same time ([#1182](https://github.com/fossar/selfoss/issues/1182))
- Fixed loading full text on pages containing ampersands in URLs ([#1188](https://github.com/fossar/selfoss/pull/1188))
- Fixed missing styling in article contents ([#1221](https://github.com/fossar/selfoss/pull/1221))
- Golem, Lightreading and Heise spouts now use Graby for extracting article contents instead of our own defunct extraction rules. ([#1245](https://github.com/fossar/selfoss/pull/1245))
- The tag colour picker now pre-selects the current colour instead of a placeholder colour. ([#1269](https://github.com/fossar/selfoss/pull/1269))
- OPML import now correctly handles valid files. ([#1366](https://github.com/fossar/selfoss/pull/1366))
- OPML import will prefer `title` attribute over text for feed names. ([#1366](https://github.com/fossar/selfoss/pull/1366))
- OPML import is now able to read files when the browser sends an incorrect MIME type. ([#1366](https://github.com/fossar/selfoss/pull/1366))

### API changes
- `tags` attribute is now consistently array of strings, numbers are numbers and booleans are booleans. **This might break third-party clients that have not updated yet.** ([#948](https://github.com/fossar/selfoss/pull/948))
- API is now versioned separately from selfoss and follows [semantic versioning](https://semver.org/) ([#1137](https://github.com/fossar/selfoss/pull/1137))
- *API 2.21.0*: `/mark` now accepts list of item IDs encoded as JSON. Requests using `application/x-www-form-urlencoded` are deprecated. ([#1182](https://github.com/fossar/selfoss/pull/1182))
- Dates returned as part of items now strictly follow ISO8601 format. ([#1246](https://github.com/fossar/selfoss/pull/1246))
- The following are deprecated and will be removed in next selfoss version:
  - Passing credentials in query string, use cookies instead. ([#1360](https://github.com/fossar/selfoss/pull/1360))
  - `GET /login` endpoint, use `POST /login`. ([#1360](https://github.com/fossar/selfoss/pull/1360))
  - `GET /logout` was deprecated in favour of newly introduced (*API 4.1.0*) `DELETE /api/session/current`. ([#1360](https://github.com/fossar/selfoss/pull/1360))
  - `POST /source/delete/:id` in favour of `DELETE /source/:id`. ([#1360](https://github.com/fossar/selfoss/pull/1360))
- *API 6.0.0*: Makes the `author` field `null` when an item author is not known ([#1367](https://github.com/fossar/selfoss/pull/1367))

### Customization changes
- `selfoss.shares.register` was removed. Instead you should set `selfoss.customSharers` to an object of *sharer* objects. The `action` callback is now expected to open a window on its own, instead of returning a URL. A label and a HTML code of an icon (you can use a `<img>` tag, inline `<svg>`, emoji, etc.) are now expected.

  To demonstrate, if you previously had

  ```javascript
  selfoss.shares.register('moo', 'm', true, function(url, title) {
      return 'http://moo.foobar/share?u=' + encodeURIComponent(url) + '&t=' + encodeURIComponent(title);
  });
  ```

  in your `user.js` file, you will need to change it to

  ```javascript
  selfoss.customSharers = {
      'm': {
          label: 'Share using Moo',
          icon: '🚛',
          action: ({url, title}) => {
              window.open(`http://moo.foobar/share?u=${encodeURIComponent(url)}&t=${encodeURIComponent(title)}`);
          },
      },
  };
  ```

  ([#1017](https://github.com/fossar/selfoss/pull/1017), [#1035](https://github.com/SSilence/selfoss/pull/1035), [#1359](https://github.com/SSilence/selfoss/pull/1359))
- Custom FullTextRss filter were moved to `fulltextrss` directory in data directory ([#1043](https://github.com/fossar/selfoss/pull/1043))
- Spouts can now implement `getSourceIcon()` instead of `getIcon()` when icon is associated with the feed, not individual icons. ([#1190](https://github.com/fossar/selfoss/pull/1190))
- Some language files have been renamed to use correct [IETF language tag](https://en.wikipedia.org/wiki/IETF_language_tag) and you might need to change the `language` key in your `config.ini`:
  * Simplified Chinese `zh-CN`
  * Traditional Chinese `zh-TW`
  * Norwegian Bokmål `nb`
  * Swedish `sv`
- Wallabag sharer now targets Wallabag 2 by default. This is potentially breaking change but hopefully, no one uses Wallabag 1 any more. ([#1261](https://github.com/fossar/selfoss/pull/1261))
- `defaults.ini` file is no longer used, it is only provided for convenience under a new name `config-example.ini` ([#1261](https://github.com/fossar/selfoss/pull/1261), [#1267](https://github.com/fossar/selfoss/pull/1267))
- `spout` classes no longer need to implement `Iterator`, instead they should return `Iterator` of newly introduced `Item` objects from `getItems()` method. The types of properties of items have also been revisited. ([#1341](https://github.com/fossar/selfoss/pull/1341), [#1342](https://github.com/fossar/selfoss/pull/1342))

### Other changes
- Amine and others have rewritten the **Android app** from scratch, you will want to install the [new one from F-Droid](https://f-droid.org/packages/bou.amine.apps.readerforselfossv2.android) to keep receiving updates.
- The front-end has been modernized using React framework, this will greatly simplify future development. ([#1216](https://github.com/fossar/selfoss/pull/1216))
- The front-end routing no longer relies on hash fragment, resulting in nicer URLs. ([#1299](https://github.com/fossar/selfoss/pull/1299))
- Prevent sending referrer headers when opening links and sharing for improved privacy. ([#1301](https://github.com/fossar/selfoss/pull/1301))
- Removed broken instapaper scraping from Reddit spout ([#1033](https://github.com/fossar/selfoss/pull/1033))
- RSS feed will be fetched more reliably ([#1052](https://github.com/fossar/selfoss/pull/1052))
- Guzzle is now used for Twitter as well, allowing users to [install certificates](https://github.com/fossar/selfoss/issues/1099#issuecomment-477112598) on outdated hosts easily. ([#1102](https://github.com/SSilence/selfoss/pull/1102))
- More of user interface is now translatable ([#1054](https://github.com/fossar/selfoss/pull/1054))
- Open Sans font is no longer bundled, resulting in smaller installations. Additionally, `use_system_font` option was removed. The typeface is still set as the default font family, so if you want to use it, install it to your devices. If you want to use a different typeface, add `body { font-family: 'Foo Face'; }` to your `user.css`. ([#1072](https://github.com/fossar/selfoss/pull/1072))
- The file name of exported sources now includes a timestamp ([#1078](https://github.com/fossar/selfoss/pull/1078))
- Developers, we no longer use Grunt. Build the package using `npm run dist` and check the code using `npm run check`; see the `scripts` section in top-level `package.json`. ([#1093](https://github.com/fossar/selfoss/pull/1093))
- Developers, we are now building the styles and client-side code statically, using [Parcel](https://parceljs.org/). If you update any such assets, you will need to run `npm run build` for the changes to be reflected. You can also use `npm run dev` to watch for asset changes. ([#1137](https://github.com/fossar/selfoss/pull/1137))
- Developers, CSS files are now checked using [stylelint](https://stylelint.io/) and formatted using [Prettier](https://prettier.io/). You can use `npm run lint:styles` and `npm run fix:styles` respectively in the `assets` directory to run those tools. ([#1153](https://github.com/fossar/selfoss/pull/1153))
- Google+ and del.icio.us share button were removed ([#1121](https://github.com/fossar/selfoss/pull/1121))
- Windows 8 tiles are no longer supported. ([#1137](https://github.com/fossar/selfoss/pull/1137))
- [Strong password hashes](https://www.php.net/manual/en/function.password-hash.php) are now supported. ([#844](https://github.com/fossar/selfoss/pull/844), [#1137](https://github.com/SSilence/selfoss/pull/1137))
- RSS spout now prefers the feed logo to website favicon. ([#1152](https://github.com/fossar/selfoss/pull/1152))
- RSS spout now tries to use favicon from the feed domain when there is no logo or home page favicon. ([#1152](https://github.com/fossar/selfoss/pull/1152))
- Setting `DEBUG` to `1` in `src/common.php` no longer logs HTTP bodies, only headers. Set it to `2` if you need the bodies as well. ([#1152](https://github.com/fossar/selfoss/pull/1152))
- PHP startup errors are now logged, instead of having F3 crash with Error 500 ([#1195](https://github.com/fossar/selfoss/pull/1195))
- In order to support offline mode, we moved much of the UI to the browser. ([#1150](https://github.com/fossar/selfoss/pull/1150), [#1184](https://github.com/fossar/selfoss/pull/1184), [#1215](https://github.com/fossar/selfoss/pull/1215), [#1216](https://github.com/fossar/selfoss/pull/1216))
- We carried out a significant internal refactoring ([#1164](https://github.com/fossar/selfoss/pull/1164), [#1190](https://github.com/fossar/selfoss/pull/1190))
- Removed Instapaper spout since it has been broken since its acquisition. Sources using it were migrated to “RSS Feed (with content extraction)”. ([#1245](https://github.com/fossar/selfoss/pull/1245))
- Placeholders are now used for images before they are loaded to avoid content jumping around ([#1204](https://github.com/fossar/selfoss/pull/1204))
- Search button is now always on the screen, avoiding the need to scroll to top to be able to use it. ([#1231](https://github.com/fossar/selfoss/issues/1231))
- Button for opening articles, tags, sources and filters in the sidebar, as well as the source and tag links in articles are now real links, allowing to open them in a new tab by middle-clicking them. ([#1216](https://github.com/fossar/selfoss/issues/1216), [#695](https://github.com/fossar/selfoss/issues/695))
- The way `config.ini` is parsed has changed. If you use any of the following characters `?{}|&~!()^"`, e.g. for database password, you will need to quote the config value like `db_password="life0fD4ng3r!"`. This is a consequence of replacing F3 framework with [PHP’s built-in INI parser](https://www.php.net/manual/en/function.parse-ini-file.php). ([#1261](https://github.com/fossar/selfoss/pull/1261))
- Removed `anonymizer` configuration option. ([#1358](https://github.com/fossar/selfoss/pull/1358))


## 2.18 – 2018-03-05
### New features
- Full-text RSS spout is now able to extract content from PDFs ([#897](https://github.com/fossar/selfoss/pull/897))
- URL is no longer cleaned when changing spout ([#906](https://github.com/fossar/selfoss/pull/906))
- It is possible to set tag or source to be opened after user logs in ([#927](https://github.com/fossar/selfoss/pull/927))
- Displaying multiple images from tweets with galleries is supported ([#934](https://github.com/fossar/selfoss/pull/934))
- Quoted tweets are supported ([#934](https://github.com/fossar/selfoss/pull/934))
- Logging destination can be changed ([#1004](https://github.com/fossar/selfoss/pull/1004))

### Bug fixes
- Fixed Full-text RSS spout ([#897](https://github.com/fossar/selfoss/pull/897))
- It is now unlikely that the client browser gets outdated JS or CSS ([#907](https://github.com/fossar/selfoss/pull/907)) On Lighttpd, you might need to [update your configuration](https://github.com/SSilence/selfoss/wiki/Lighttpd-configuration#upgrading-from-selfoss-217-or-lower).
- Fixed back button not working correctly on small screens ([#906](https://github.com/fossar/selfoss/pull/906))
- When using PostgreSQL, vacuuming is left to the database ([#906](https://github.com/fossar/selfoss/pull/906))
- Items from different spouts but with the same uid will not be ignored anymore ([#906](https://github.com/fossar/selfoss/pull/906))
- GitHub spout was modified to correctly escape the data ([#906](https://github.com/fossar/selfoss/pull/906))
- YouTube spout was changed to allow wider range of URLs ([#915](https://github.com/fossar/selfoss/pull/915))
- The items without a date will no longer be added again after clean-up ([#914](https://github.com/fossar/selfoss/pull/914))
- Changed favicon fetcher for RSS spout to be more resilient ([#920](https://github.com/fossar/selfoss/pull/920))
- Tweets are no longer truncated ([#934](https://github.com/fossar/selfoss/pull/934))
- Using arrow keys in photo galleries will no longer change opened item ([#942](https://github.com/fossar/selfoss/pull/942))
- Facebook spout is finally working again ([#936](https://github.com/fossar/selfoss/pull/936))
- PSR-4 autoloading is now used, fixing the compatibility with custom spouts. **If you use custom spouts**, please make sure to [check compliance](https://github.com/fossar/selfoss/pull/959). ([#959](https://github.com/SSilence/selfoss/pull/959))

### Other changes
- Fixed compatibility with PHP 7.2 ([#1005](https://github.com/fossar/selfoss/pull/1005))
- Improved translations ([#932](https://github.com/fossar/selfoss/pull/932), [#981](https://github.com/SSilence/selfoss/pull/981), [#985](https://github.com/SSilence/selfoss/pull/985), [#1003](https://github.com/SSilence/selfoss/pull/1003))
- Changed library for handling ico files ([#926](https://github.com/fossar/selfoss/pull/926))
- Upgraded FancyBox, the gallery looks much slicker now ([#942](https://github.com/fossar/selfoss/pull/942))
- **For developers**: JavaScript libraries now have to be obtained using NPM ([#942](https://github.com/fossar/selfoss/pull/942))
- Login is now done using AJAX, a step towards progressive web app ([#931](https://github.com/fossar/selfoss/pull/931))
- Guzzle is used for HTTP requests making them more reliable ([#936](https://github.com/fossar/selfoss/pull/936))
- Ironed out some inconsistencies in database schema ([#955](https://github.com/fossar/selfoss/pull/955))
- **For developers**: JavaScript client code is now checked using eslint ([#951](https://github.com/fossar/selfoss/pull/951))
- Increased resolution of the favicon ([#961](https://github.com/fossar/selfoss/pull/961))
- Added warning when autoloader is missing ([#957](https://github.com/fossar/selfoss/pull/957))
- Removed redundant alt attribute from favicons ([#978](https://github.com/fossar/selfoss/pull/978))
- Favicons are now easier to click on mobile ([#992](https://github.com/fossar/selfoss/pull/992))
- Tables that do not fit into columns will now show a scrollbar ([#1001](https://github.com/fossar/selfoss/pull/1001))
- **For developers**: Coding style and other code requirements can be easily checked using `grunt check`. ([#943](https://github.com/fossar/selfoss/pull/943))
- Warning will be logged when icon/thumbnail directories are not writeable ([#1009](https://github.com/fossar/selfoss/pull/1009))
- Removed readability spout and sharing ([#1012](https://github.com/fossar/selfoss/pull/1012))
- Switched icons to vector images, which will allow better theming ([#1013](https://github.com/fossar/selfoss/pull/1013))


## 2.17 – 2017-03-17
### New features
- Spout title can be fetched automatically ([#851](https://github.com/fossar/selfoss/pull/851))
- selfoss is now navigable ([#869](https://github.com/fossar/selfoss/pull/869))
- Refreshing the sources using the button no longer blocks the user interface ([#846](https://github.com/fossar/selfoss/pull/846))
- State of the items is synced periodically ([#846](https://github.com/fossar/selfoss/pull/846))
- Added option for sharing with Wallabag 2 ([#887](https://github.com/fossar/selfoss/pull/887))

### Notable changes
- Composer is used for dependency management, if you downloaded selfoss from git repository you will need to use composer for installing dependencies. ([#845](https://github.com/fossar/selfoss/pull/845))
- Simplified detecting selfoss root URL which should fix some cookie problems ([#889](https://github.com/fossar/selfoss/pull/889))
- Made the `db_port` configuration key optional ([#843](https://github.com/fossar/selfoss/pull/843))
- Migrated to `.htaccess` to Apache 2.4 syntax ([#833](https://github.com/fossar/selfoss/pull/833))

### Bug fixes
- Fixed YouTube spout ([#842](https://github.com/fossar/selfoss/pull/842))
- DeviantArt, Reddit, Golem and Twitter spouts changed to use HTTPS ([#835](https://github.com/fossar/selfoss/pull/835))
- Fixed reddit spout redirects ([#835](https://github.com/fossar/selfoss/pull/835))
- Fixed Wordpress emoji size on HTTPS sites ([#835](https://github.com/fossar/selfoss/pull/835))
- Fixed twitter links when tweet contains `<` ([#852](https://github.com/fossar/selfoss/pull/852))
- Fixed encoding problems caused by camo ([#826](https://github.com/fossar/selfoss/pull/826))
- Fixed “$HTTP_RAW_POST_DATA is deprecated” error when updating a single source ([#841](https://github.com/fossar/selfoss/pull/841))
- Fixed twitter spout error reporting ([#847](https://github.com/fossar/selfoss/pull/847))
- Improved error reporting for reddit spout ([#860](https://github.com/fossar/selfoss/pull/860))
- Removed the need for MySQL 5.6; MySQL 5.5.3 or greater is now required again ([#863](https://github.com/fossar/selfoss/pull/863))
- Made RSS feed generated by selfoss valid ([#862](https://github.com/fossar/selfoss/pull/862))
- Fixed [#774](https://github.com/fossar/selfoss/pull/774) “Incorrectly calculated offset for loading new items” ([#869](https://github.com/SSilence/selfoss/pull/869))
- Fixed code listings overflowing to different column ([#889](https://github.com/fossar/selfoss/pull/889))
