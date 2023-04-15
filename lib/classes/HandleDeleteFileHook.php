<?php

namespace WebPExpress;
use \WebPExpress\Convert;
use \WebPExpress\Mime;
use \WebPExpress\SanityCheck;

class HandleDeleteFileHook
{

    /**
     *  hook: wp_delete_file
     */
    public static function deleteAssociatedWebP($filename)
    {
        try {
            $filename = SanityCheck::absPathExistsAndIsFileInDocRoot($filename);

            $mimeTypes = [
                'image/jpeg',
                'image/png',
            ];
            if (!Mime::isOneOfTheseImageMimeTypes($filename, $mimeTypes)) {
                return $filename;
            }

            $config = Config::loadConfigAndFix();
            $destination = Convert::getDestination($filename, $config);
            if (@file_exists($destination)) {
                if (@unlink($destination)) {
                    Convert::updateBiggerThanOriginalMark($filename, $destination, $config);
                } else {
                    error_log('WebP Express failed deleting webp:' . $destination);
                }
            }
        } catch (SanityException $e) {
            // fail silently. (maybe we should write to debug log instead?)
        }

        return $filename;
    }
}
