<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Convert;

class HandleUploadHooks
{

    private static $config;


    private static function getMimeTypeOfMedia($filename)
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

    /**
     *  Convert if:
     *  - Option has been enabled
     *  - The mime type is one of the ones the user has activated (in config)
     */
    private static function convertIf($filename)
    {
        if (!isset(self::$config)) {
            self::$config = Config::loadConfigAndFix();
        }

        $config = &self::$config;

        if (!$config['convert-on-upload']) {
            return;
        }

        //$mimeType = getimagesize($filename)['mime'];

        $allowedMimeTypes = [];
        $imageTypes = $config['image-types'];
        if ($imageTypes & 1) {
            $allowedMimeTypes[] = 'image/jpeg';
            $allowedMimeTypes[] = 'image/jpg';      /* don't think "image/jpg" is neccessary, but just in case */
        }
        if ($imageTypes & 2) {
            $allowedMimeTypes[] = 'image/png';
        }

        if (!in_array(self::getMimeTypeOfMedia($filename), $allowedMimeTypes)) {
            return;
        }

        Convert::convertFile($filename, $config);

    }

    /**
     *  hook: handle_upload
     *  $filename is ie "/var/www/webp-express-tests/we0/wordpress/uploads-moved/image4-10-150x150.jpg"
     */
    public static function handleUpload($filearray, $overrides, $ignore = false)
    {

        /*\WebPExpress\Messenger::printMessage(
            'error',
            'looksy!'
        );*/
        $filename = $filearray['file'];
        self::convertIf($filename);

        return $filearray;
    }

    /**
     *  hook: image_make_intermediate_size
     *  $filename is ie "/var/www/webp-express-tests/we0/wordpress/uploads-moved/image4-10-150x150.jpg"
     */
    public static function handleMakeIntermediateSize($filename)
    {
        self::convertIf($filename);
        return $filename;
    }
}
