Change Log
==========

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/)
and this project adheres to [Semantic Versioning](https://semver.org).

[v0.5]
------

### Added
- `scopeIdProvider` property on RateLimiterComponent allows you to override the
  default scope ID used for rate allowance storage.


[v0.4]
------

### Changed
- Form default AllowanceCacheStorage cache key as an array; leave serialization
  up to the `buildKey()` method of the cache instance.


[v0.3]
------

### Changed
- Require Yii 2.0.14 or higher
- Throw `InvalidArgumentException` instead of `InvalidParamException` to improve
  forward-compatibility with Yii
- Reference `::class` instead of `::className()` to improve
  forward-compatibility with Yii

[v0.2]
------

### Changed
- Require Yii 2.0.13 or higher and PHP 5.6 or higher
- Extend `BaseObject` instead of `Object` to improve forward-compatibility with
  PHP and Yii

[v0.1]
------

### Added
- Initial implementation
