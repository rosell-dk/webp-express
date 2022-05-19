<?php

/**
 * ImageMimeTypeGuesser - Detect / guess mime type of an image
 *
 * @link https://github.com/rosell-dk/image-mime-type-guesser
 * @license MIT
 */

namespace ImageMimeTypeGuesser;

class MimeMap
{

    /**
     * Map image file extension to mime type
     *
     *
     * @param  string  $filePath  The filename (or path), ie "image.jpg"
     * @return string|false|null  mimetype (if file extension could be mapped to an image type),
     *    false (if file extension could be mapped to a type known not to be an image type)
     *    or null (if file extension could not be mapped to any mime type, using our little list)
     */
    public static function filenameToMime($filePath)
    {
        $result = preg_match('#\\.([^.]*)$#', $filePath, $matches);
        if ($result !== 1) {
            return null;
        }
        $fileExtension = $matches[1];
        return self::extToMime($fileExtension);
    }

    /**
     * Map image file extension to mime type
     *
     *
     * @param  string  $fileExtension  The file extension (ie "jpg")
     * @return string|false|null  mimetype (if file extension could be mapped to an image type),
     *    false (if file extension could be mapped to a type known not to be an image type)
     *    or null (if file extension could not be mapped to any mime type, using our little list)
     */
    public static function extToMime($fileExtension)
    {

        $fileExtension = strtolower($fileExtension);
        
        // Trivial image mime types
        if (in_array($fileExtension, ['apng', 'avif', 'bmp', 'gif', 'jpeg', 'png', 'tiff', 'webp'])) {
            return 'image/' . $fileExtension;
        }

        // Common extensions that are definitely not images
        if (in_array($fileExtension, ['txt', 'doc', 'zip', 'gz', 'exe'])) {
            return false;
        }

        // Non-trivial image mime types
        switch ($fileExtension) {
            case 'ico':
            case 'cur':
                return 'image/x-icon';      // or perhaps 'vnd.microsoft.icon' ?

            case 'jpg':
                return 'image/jpeg';

            case 'svg':
                return 'image/svg+xml';

            case 'tif':
                return 'image/tiff';
        }

        // We do not know this extension, return null
        return null;
    }
}
