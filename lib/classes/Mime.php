<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Convert;

class Mime
{

    public static function getMimeTypeOfMedia($filename)
    {
        // Try the Wordpress function. It tries exif_imagetype and getimagesize and returns false if no methods are available
        $mimeType = wp_get_image_mime($filename);
        if ($mimeType !== false) {
            return $mimeType;
        }

        // Try mime_content_type
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($filename);
            if ($mimeType !== false) {
                return $mimeType;
            }
        }

        // Try wordpress method, which simply uses the file extension and a map
        $mimeType = wp_check_filetype($filePath)['type'];
        if ($mimeType !== false) {
            return $mimeType;
        }

        // Don't say we didn't try!
        return 'unknown';
    }

    public static function isOneOfTheseImageMimeTypes($filename, $imageMimeTypes)
    {
        $detectedMimeType = self::getMimeTypeOfMedia($filename);
        return in_array($detectedMimeType, $imageMimeTypes);
    }

}
