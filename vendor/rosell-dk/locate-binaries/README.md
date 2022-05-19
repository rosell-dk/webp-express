# Locate Binaries

[![Build Status](https://github.com/rosell-dk/locate-binaries/workflows/build/badge.svg)](https://github.com/rosell-dk/locate-binaries/actions/workflows/php.yml)
[![Coverage](https://img.shields.io/endpoint?url=https://little-b.it/locate-binaries/code-coverage/coverage-badge.json)](http://little-b.it/locate-binaries/code-coverage/coverage/index.html)
[![Software License](https://img.shields.io/badge/license-MIT-418677.svg)](https://github.com/rosell-dk/locate-binary/blob/master/LICENSE)
[![Latest Stable Version](https://img.shields.io/packagist/v/rosell-dk/locate-binaries.svg)](https://packagist.org/packages/rosell-dk/locate-binaries)
[![Minimum PHP Version](https://img.shields.io/packagist/php-v/rosell-dk/locate-binaries)](https://php.net)

Just a little class for locating binaries.
You need `exec()`, `shell_exec()` or similar enabled for it to work. Otherwise, it will throw.

Works on Linux, Windows and Mac.

## Usage

To locate installed `cwebp` binaries (found on Linux with `which -a`, falling back to `whereis -b`; on Windows found using `where`):
```
$cwebBinariesFound = LocateBinaries::locateInstalledBinaries('cwebp');
```
Note that you get an array of matches - there may be several versions of a binary on a system.

The library also adds another method for locating binaries by peeking in common system paths, such as *usr/bin* and `C:\Windows\System32`
However, beware that these dirs could be subject to open_basedir restrictions which can lead to warning entries in the error log. The other method is therefore best.

Well warned, here it is the alternative, which you in some cases might want to fall back to after trying the first.
```
$imagickBinariesFound = LocateBinaries::locateInCommonSystemPaths('convert');
```

## Notes
The library uses the [exec-with-fallback](https://github.com/rosell-dk/exec-with-fallback) library in order to be able to use alternatives to exec() when exec() is disabled.

## Do you like what I do?
Perhaps you want to support my work, so I can continue doing it :)

- [Become a backer or sponsor on Patreon](https://www.patreon.com/rosell).
- [Buy me a Coffee](https://ko-fi.com/rosell)

Thanks!
