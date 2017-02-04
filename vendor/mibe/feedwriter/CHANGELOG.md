# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) .

## [v1.1.1] - 2016-11-19
### Changed
- Improved the documentation.
- Changed to PSR-4 autoloader in composer.json.

### Fixed
- Item::addElement did not method chain in error conditions.

## [v1.1.0] - 2016-11-08
### Added
- Support for multiple element values.
- Support for a feed description in ATOM feeds.
- Support for ATOM feeds without ```link``` elements.
- Support for a feed image in RSS 1.0 and ATOM feeds.

### Changed
- The script does now throw Exceptions instead of stopping the PHP interpreter on error conditions.
- The unique identifier for ATOM feeds / entries use the feed / entry title for generating the ID (previously the feed / entry link).
- Some URI schemes for ```Item::setId``` were wrongly allowed.
- The parameter order of the ```Feed::setImage``` method was changed.

### Fixed
- Fixed slow generation of the feed with huge amounts of feed entries (like 40k entries).
- Fixed PHP warning when ```Feed::setChannelAbout``` for RSS 1.0 feeds was not called.
- A feed element was generated twice if the element content & attribute value was ```NULL```.
- The detection of twice the same link with ```rel=alternate```, ```hreflang``` & ```type``` did not work.

### Removed
- The deprecated method ```Item::setEnclosure``` was removed. Use ```Item::addEnclosure``` instead.

## [v1.0.4] - 2016-04-17
### Changed
- The unique identifier for ATOM feed entries is now compliant to the ATOM standard.

### Fixed
- Filter more invalid XML chars.
- Fixed a PHP warning displayed if ```Feed::setTitle``` or ```Feed::setLink``` was not called.

## [v1.0.3] - 2015-11-11
### Added
- Method for removing tags which were CDATA encoded.
 
### Fixed
- Fixed error when the filtering of invalid XML chars failed.
- Fixed missing docblock documentation.

## [v1.0.2] - 2015-01-23
### Fixed
- Fixed a wrong docblock return data type.

## [v1.0.1] - 2014-09-21
### Fixed
- Filter invalid XML chars.

## v1.0 - 2014-09-14


[Unreleased]: https://github.com/mibe/FeedWriter/compare/v1.1.1...HEAD
[v1.1.1]: https://github.com/mibe/FeedWriter/compare/v1.1.0...v1.1.1
[v1.1.0]: https://github.com/mibe/FeedWriter/compare/v1.0.4...v1.1.0
[v1.0.4]: https://github.com/mibe/FeedWriter/compare/v1.0.3...v1.0.4
[v1.0.3]: https://github.com/mibe/FeedWriter/compare/v1.0.2...v1.0.3
[v1.0.2]: https://github.com/mibe/FeedWriter/compare/v1.0.1...v1.0.2
[v1.0.1]: https://github.com/mibe/FeedWriter/compare/v1.0...v1.0.1
