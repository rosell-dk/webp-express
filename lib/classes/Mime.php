<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Convert;

class Mime
{

    public static function getMimeTypeOfMedia($filename)
    {
        // ensure filename is not empty, as wp_get_image_mime() goes fatal if it is
        if ($filename === '') {
          return 'unknown';
        }

        // First try the Wordpress function if available (it was introduced in 4.7.1)
        if (function_exists('wp_get_image_mime')) {

            // PS: wp_get_image_mime tries exif_imagetype and getimagesize and returns false if no methods are available
            $mimeType = wp_get_image_mime($filename);
            if ($mimeType !== false) {
                return $mimeType;
            }

        }

        // Try mime_content_type
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($filename);
            if ($mimeType !== false) {
                return $mimeType;
            }
        }

        if (function_exists('wp_check_filetype')) { // introduced in 2.0.4
            // Try wordpress method, which simply uses the file extension and a map
            $mimeType = wp_check_filetype($filename)['type'];
            if ($mimeType !== false) {
                return $mimeType;
            }
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
