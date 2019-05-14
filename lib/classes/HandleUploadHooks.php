<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Convert;
use \WebPExpress\Mime;

class HandleUploadHooks
{

    private static $config;

    /**
     *  Convert if:
     *  - Option has been enabled
     *  - We are not in "No conversion" mode
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
        if ($config['operation-mode'] == 'no-conversion') {
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

        if (!in_array(Mime::getMimeTypeOfMedia($filename), $allowedMimeTypes)) {
            return;
        }

        Convert::convertFile($filename, $config);

    }

    /**
     *  hook: handle_upload
     *  $filename is ie "/var/www/webp-express-tests/we0/wordpress/uploads-moved/image4-10-150x150.jpg"
     */
    public static function handleUpload($filearray, $overrides = false, $ignore = false)
    {
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
