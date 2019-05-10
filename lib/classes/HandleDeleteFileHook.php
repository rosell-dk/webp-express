<?php

namespace WebPExpress;
use \WebPExpress\Convert;
use \WebPExpress\Mime;

class HandleDeleteFileHook
{

    /**
     *  hook: wp_delete_file
     */
    public static function deleteAssociatedWebP($filename)
    {
        $mimeTypes = [
            'image/jpeg',
            'image/png',
        ];
        if (!Mime::isOneOfTheseImageMimeTypes($filename, $mimeTypes)) {
            return;
        }

        $destination = Convert::getDestination($filename);
        error_log('deleting webp:' . $destination);
        if (!unlink($destination)) {
            error_log('failed deleting webp:' . $destination);
        }

    }
}
