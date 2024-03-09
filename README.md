# chillerlan/psr-7

A [PSR-7](https://www.php-fig.org/psr/psr-7/)/[PSR-17](https://www.php-fig.org/psr/psr-17/) HTTP message and factory implementation.

[![PHP Version Support][php-badge]][php]
[![Packagist version][packagist-badge]][packagist]
[![License][license-badge]][license]
[![Continuous Integration][gh-action-badge]][gh-action]
[![CodeCov][coverage-badge]][coverage]
[![Codacy][codacy-badge]][codacy]
[![Packagist downloads][downloads-badge]][downloads]

[php-badge]: https://img.shields.io/packagist/php-v/chillerlan/psr-7?logo=php&color=8892BF&logoColor=fff
[php]: https://www.php.net/supported-versions.php
[packagist-badge]: https://img.shields.io/packagist/v/chillerlan/psr-7.svg?logo=packagist&logoColor=fff
[packagist]: https://packagist.org/packages/chillerlan/psr-7
[license-badge]: https://img.shields.io/github/license/chillerlan/psr-7.svg
[license]: https://github.com/chillerlan/psr-7/blob/main/LICENSE
[gh-action-badge]: https://img.shields.io/github/actions/workflow/status/chillerlan/psr-7/ci.yml?branch=main&logo=github&logoColor=fff
[gh-action]: https://github.com/chillerlan/psr-7/actions/workflows/ci.yml?query=branch%3Amain
[coverage-badge]: https://img.shields.io/codecov/c/github/chillerlan/psr-7.svg?logo=codecov&logoColor=fff
[coverage]: https://codecov.io/github/chillerlan/psr-7
[codacy-badge]: https://img.shields.io/codacy/grade/de971588f9a44f1a99e7bbd2a0737951?logo=codacy&logoColor=fff
[codacy]: https://app.codacy.com/gh/chillerlan/psr-7/dashboard
[downloads-badge]: https://img.shields.io/packagist/dt/chillerlan/psr-7.svg?logo=packagist&logoColor=fff
[downloads]: https://packagist.org/packages/chillerlan/psr-7/stats

## Overview

### Features

- [PSR-7](https://www.php-fig.org/psr/psr-7/) HTTP message implementation
- [PSR-17](https://www.php-fig.org/psr/psr-17/) HTTP factory implementation
- `MultipartStreamBuilder` based on PSR-7 `Message` objects ([RFC-2046, section 5.1](https://datatracker.ietf.org/doc/html/rfc2046#section-5.1))

### Requirements

- PHP 8.1+
  - [`ext-mbstring`](https://www.php.net/manual/book.mbstring.php)


## Documentation

The documentation of the PSR-7 interfaces can be found over at https://www.php-fig.org/psr/psr-7/.

**NOTE: This library has abandoned the paranoid "value object" "immuatbility" that is dictated by PSR-7 for it is horseshit.
The pseudo-immutability gets in the way more often (always) than it is useful (never) and creates endless overhead.
If you want your objects to be immutable for whatever reason, just fucking clone them and don't force countless libraries
to do that for you instead. If you don't like it, just use Guzzle instead (spoiler: you won't notice the difference).**

Further, this library still only implements [`psr/http-message`](https://packagist.org/packages/psr/http-message) v1.1,
as the v2.0 release (06/2023) has return types added [that conflict](https://github.com/php-fig/http-message/pull/107)
with the [`static` return type](https://wiki.php.net/rfc/static_return_type) that was introduced in PHP 8 (11/2020).


### Auto generated API documentation

The API documentation can be auto generated with [phpDocumentor](https://www.phpdoc.org/).
There is an [online version available](https://chillerlan.github.io/psr-7/) via the [gh-pages branch](https://github.com/chillerlan/psr-7/tree/gh-pages) that is [automatically deployed](https://github.com/chillerlan/psr-7/deployments) on each push to main.

Locally created docs will appear in `.build/phpdocs/`. If you'd like to create local docs, please follow these steps:

- [download phpDocumentor](https://github.com/phpDocumentor/phpDocumentor/releases) v3+ as .phar archive
- run it in the repository root directory:
	- on Windows `c:\path\to\php.exe c:\path\to\phpDocumentor.phar --config=phpdoc.xml`
	- on Linux just `php /path/to/phpDocumentor.phar --config=phpdoc.xml`
- open [index.html](./.build/phpdocs/index.html) in a browser
- profit!


## Disclaimer

Use at your own risk!
