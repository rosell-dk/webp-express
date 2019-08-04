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
            return $filename;
        }

        $destination = Convert::getDestination($filename);
        if (@file_exists($destination)) {
            if (!@unlink($destination)) {
                error_log('WebP Express failed deleting webp:' . $destination);
            }            
        }
        return $filename;
    }
}
