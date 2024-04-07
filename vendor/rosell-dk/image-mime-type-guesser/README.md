# image-mime-type-guesser

[![Latest Stable Version](https://img.shields.io/packagist/v/rosell-dk/image-mime-type-guesser.svg?style=flat-square)](https://packagist.org/packages/rosell-dk/image-mime-type-guesser)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg?style=flat-square)](https://php.net)
[![Build Status](https://img.shields.io/github/actions/workflow/status/rosell-dk/image-mime-type-guesser/ci.yml?branch=master&logo=GitHub&style=flat-square)](https://github.com/rosell-dk/image-mime-type-guesser/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/endpoint?url=https://little-b.it/image-mime-type-guesser/code-coverage/coverage-badge.json)](http://little-b.it/image-mime-type-guesser/code-coverage/coverage/index.html)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](https://github.com/rosell-dk/image-mime-type-guesser/blob/master/LICENSE)
[![Monthly Downloads](http://poser.pugx.org/rosell-dk/image-mime-type-guesser/d/monthly)](https://packagist.org/packages/rosell-dk/image-mime-type-guesser)
[![Dependents](http://poser.pugx.org/rosell-dk/image-mime-type-guesser/dependents)](https://packagist.org/packages/rosell-dk/image-mime-type-guesser/dependents?order_by=downloads)


*Detect / guess mime type of an image*

Do you need to determine if a file is an image?<br>
And perhaps you also want to know the mime type of the image?<br>

&ndash; You come to the right library.

Ok, actually the library cannot offer mime type detection for images which works *on all platforms*, but it can try a whole stack of methods and optionally fall back to guess from the file extension.

The stack of detect methods are currently (and in that order):
-  [This signature sniffer](https://github.com/rosell-dk/image-mime-type-sniffer) *Does not require any extensions. Recognizes popular image formats only*
-  [`finfo`](https://www.php.net/manual/en/class.finfo.php) *Requires fileinfo extension to be enabled. (PHP 5 >= 5.3.0, PHP 7, PHP 8, PECL fileinfo >= 0.1.0)*
-  [`exif_imagetype`](https://www.php.net/manual/en/function.exif-imagetype.php) *Requires that PHP is compiled with exif (PHP 4 >= 4.3.0, PHP 5, PHP 7, PHP 8)*
-  [`mime_content_type`](https://www.php.net/manual/en/function.mime-content-type.php) *Requires fileinfo. (PHP 4 >= 4.3.0, PHP 5, PHP 7, PHP 8)*

Note that all these methods except the signature sniffer relies on the mime type mapping on the server (the `mime.types` file in Apache). If the server doesn't know about a certain mime type, it will not be detected. This does however not mean that the methods relies on the file extension. A png file renamed to "png.jpeg" will be correctly identified as *image/png*.

Besides the detection methods, the library also comes with a method for mapping file extension to mime type. It is rather limited, though.

## Installation

Install with composer:
```
composer require rosell-dk/image-mime-type-guesser
```

## Usage

To detect the mime type of a file, use `ImageMimeTypeGuesser::detect($filePath)`. It returns the mime-type, if the file is recognized as an image. *false* is returned if it is not recognized as an image. *null* is returned if the mime type could not be determined (ie due to none of the methods being available).

Example:
```php
use ImageMimeTypeGuesser\ImageMimeTypeGuesser;
$result = ImageMimeTypeGuesser::detect($filePath);
if (is_null($result)) {
    // the mime type could not be determined
} elseif ($result === false) {
    // it is NOT an image (not a mime type that the server knows about anyway)
    // This happens when:
    // a) The mime type is identified as something that is not an image (ie text)
    // b) The mime type isn't identified (ie if the image type is not known by the server)
} else {
    // it is an image, and we know its mime type!
    $mimeType = $result;
}
```

For convenience, you can use *detectIsIn* method to test if a detection is in a list of mimetypes.

```php
if (ImageMimeTypeGuesser::detectIsIn($filePath, ['image/jpeg','image/png'])) {
    // The file is a jpeg or a png
}
```

The `detect` method does not resort to mapping from file extension. In most cases you do not want to do that. In some cases it can be insecure to do that. For example, if you want to prevent a user from uploading executable files, you probably do not want to allow her to upload executable files with innocent looking file extenions, such as "evil-exe.jpg".

In some cases, though, you simply want a best guess, and in that case, falling back to mapping from file extension makes sense. In that case, you can use the *guess* method instead of the *detect* method. Or you can use *lenientGuess*. Lenient guess is even more slacky and will turn to mapping not only when dectect return *null*, but even when it returns *false*.

*Warning*: Beware that guessing from file extension is unsuited when your aim is to protect the server from harmful uploads.

*Notice*: Only a limited set of image extensions is recognized by the extension to mimetype mapper - namely the following: { apng, avif, bmp, gif, ico, jpg, jpeg, png, tif, tiff, webp, svg }. If you need some other specifically, feel free to add a PR, or ask me to do it by creating an issue.


Example:
```php
$result = ImageMimeTypeGuesser::guess($filePath);
if ($result !== false) {
    // It appears to be an image
    // BEWARE: This is only a guess, as we resort to mapping from file extension,
    //         when the file cannot be properly detected.
    // DO NOT USE THIS GUESS FOR PROTECTING YOUR SERVER
    $mimeType = $result;
} else {
    // It does not appear to be an image
}
```

The guess functions also have convenience methods for testing against a list of mime types. They are called `ImageMimeTypeGuesser::guessIsIn` and `ImageMimeTypeGuesser::lenientGuessIsIn`.

Example:
```php
if (ImageMimeTypeGuesser::guessIsIn($filePath, ['image/jpeg','image/png'])) {
    // The file appears to be a jpeg or a png
}
```

## Alternatives

Other sniffers:
- https://github.com/Intervention/mimesniffer
- https://github.com/zjsxwc/mime-type-sniffer
- https://github.com/Tinram/File-Identifier

## Do you like what I do?
Perhaps you want to support my work, so I can continue doing it :)

- [Become a backer or sponsor on Patreon](https://www.patreon.com/rosell).
- [Buy me a Coffee](https://ko-fi.com/rosell)
