# Exec with fallback

[![Latest Stable Version](http://poser.pugx.org/rosell-dk/exec-with-fallback/v)](https://packagist.org/packages/rosell-dk/exec-with-fallback)
[![Build Status](https://github.com/rosell-dk/exec-with-fallback/actions/workflows/php.yml/badge.svg)](https://github.com/rosell-dk/exec-with-fallback/actions/workflows/php.yml)
[![Software License](http://poser.pugx.org/rosell-dk/exec-with-fallback/license)](https://github.com/rosell-dk/exec-with-fallback/blob/master/LICENSE)
[![PHP Version Require](http://poser.pugx.org/rosell-dk/exec-with-fallback/require/php)](https://packagist.org/packages/rosell-dk/exec-with-fallback)
[![Daily Downloads](http://poser.pugx.org/rosell-dk/exec-with-fallback/d/daily)](https://packagist.org/packages/rosell-dk/exec-with-fallback)

Some shared hosts may have disabled *exec()*, but leaved *proc_open()*, *passthru()*, *popen()* or *shell_exec()* open. In case you want to easily fall back to emulating *exec()* with one of these, you have come to the right library.

This library can be useful if you a writing code that is meant to run on a broad spectrum of systems, as it makes your exec() call succeed on more of these systems.

## Usage:
Simply swap out your current *exec()* calls with *ExecWithFallback::exec()*. The signatures are exactly the same and errors are handled the same way.

```php
use ExecWithFallback\ExecWithFallback;
$result = ExecWithFallback::exec('echo "hi"', $output, $result_code);
// $output (array) now holds the output
// $result_code (int) now holds the result code
// $return (string | false) is now false in case of failure or the last line of the output
```

If you have `function_exists('exec')` in your code, you can change it to `ExecWithFallback::anyAvailable()`

## Installing
`composer require rosell-dk/exec-with-fallback`

## Implementation
*ExecWithFallback::exec()* first checks if *exec()* is available and calls it, if it is. In case *exec* is unavailable (deactivated on server), or exec() returns false, it moves on to checking if *passthru()* is available and so on. The order is as follows:
- exec()
- passthru()
- popen()
- proc_open()
- shell_exec()

In case all functions are unavailable, *exec()* is called. This ensures that the error handling will be exactly as usual. In case none succeeded, but at least one failed by returning false, false is returned. Again to mimic *exec()* behavior.

PS: As *shell_exec()* does not support *$result_code*, it will only be used when $result_code isn't supplied. *system()* is not implemented, as it cannot return the last line of output and there is no way to detect if your code relies on that.

If you for some reason want to run a specific exec() emulation, you can use the corresponding class directly, ie *ProcOpen::exec()*.

## Is it worth it?
Well, often these functions are often all enabled or all disabled. So on the majority of systems, it will not make a difference. But on the other hand: This library is easily installed, very lightweight and very well tested.

**easily installed**\
Install with composer (`composer require rosell-dk/exec-with-fallback`) and substitute your *exec()* calls.

**lightweight**\
The library is extremely lightweight. In case *exec()* is available, it is called immediately and only the main file is autoloaded. In case all are unavailable, it only costs a little loop, amounting to five *function_exists()* calls, and again, only the main file is autoloaded. In case *exec()* is unavailable, but one of the others are available, only that implementation is autoloaded, besides the main file.

**well tested**\
I made sure that the function behaves exactly like *exec()*, and wrote a lot of test cases. It is tested on ubuntu, windows, mac (all in several versions). It is tested in PHP 7.0, 7.1, 7.2, 7.3, 7.4 and 8.0. And it is tested in different combinations of disabled functions.

**going to be maintained**\
I'm going to use this library in [webp-convert](https://github.com/rosell-dk/webp-convert), which is used in many projects. So it is going to be widely used. While I don't expect much need for maintenance for this project, it is going to be there, if needed.

## Do you like what I do?
Perhaps you want to support my work, so I can continue doing it :)

- [Become a backer or sponsor on Patreon](https://www.patreon.com/rosell).
- [Buy me a Coffee](https://ko-fi.com/rosell)
