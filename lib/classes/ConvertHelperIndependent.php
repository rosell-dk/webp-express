<?php

/*
This class is made to be independent of other classes, and must be kept like that.
It is used by webp-on-demand.php, which does not register an auto loader. It is also used for bulk conversion.
*/
namespace WebPExpress;

use \WebPConvert\WebPConvert;
use \WebPConvert\Loggers\BufferLogger;
use \WebPExpress\FileHelper;


class ConvertHelperIndependent
{

    public static function storeMingledOrNot($source, $destinationFolder, $uploadDirAbs)
    {
        if ($destinationFolder != 'mingled') {
            return false;
        }

        // Option is set for mingled, but this does not neccessarily means we should store "mingled".
        // - because the mingled option only applies to upload folder, the rest is stored in separate cache folder
        // So, return true, if $source is located in upload folder
        return (strpos($source, $uploadDirAbs) === 0);

    }

    /*
    public static function getDestinationFolder($sourceDir, $destinationFolder, $destinationExt, $webExpressContentDirAbs, $uploadDirAbs)
    {
        if (self::storeMingledOrNot($sourceDir, $destinationFolder, $uploadDirAbs)) {
            return $sourceDir;
        } else {

            $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
            $imageRoot = $webExpressContentDirAbs . '/webp-images';

            // Check if source dir is residing inside document root.
            // (it is, if path starts with document root + '/')
            if (substr($sourceDir, 0, strlen($docRoot) + 1) === $docRoot . '/') {

                // We store relative to document root.
                // "Eat" the left part off the source parameter which contains the document root.
                // and also eat the slash (+1)
                $sourceDirRel = substr($sourceDir, strlen($docRoot) + 1);
                return $imageRoot . '/doc-root/' . $sourceDirRel;
            } else {
                // Source file is residing outside document root.
                // we must add complete path to structure
                return $imageRoot . '/abs' . $sourceDir;
            }
        }
    }*/

    public static function getDestination($source, $destinationFolder, $destinationExt, $webExpressContentDirAbs, $uploadDirAbs)
    {
        if (self::storeMingledOrNot($source, $destinationFolder, $uploadDirAbs)) {
            if ($destinationExt == 'append') {
                return $source . '.webp';
            } else {
                return preg_replace('/\\.(jpe?g|png)$/', '', $source) . '.webp';
            }
        } else {

            $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
            $imageRoot = $webExpressContentDirAbs . '/webp-images';

            // Check if source is residing inside document root.
            // (it is, if path starts with document root + '/')
            if ( FileHelper::sourceIsInsideDocRoot($source, $docRoot) ) {

                // We store relative to document root.
                // "Eat" the left part off the source parameter which contains the document root.
                // and also eat the slash (+1)
                $sourceRel = substr($source, strlen($docRoot) + 1);
                return $imageRoot . '/doc-root/' . $sourceRel . '.webp';
            } else {
                // Source file is residing outside document root.
                // we must add complete path to structure
                return $imageRoot . '/abs' . $source . '.webp';
            }
        }
    }


    public static function convert($source, $destination, $options) {
        include_once __DIR__ . '/../../vendor/autoload.php';

        $success = false;
        $msg = '';
        $logger = new BufferLogger();
        try {
            $success = WebPConvert::convert($source, $destination, $options, $logger);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }

        return [
            'success' => $success,
            'msg' => $msg,
            'log' => $logger->getHtml(),
        ];

    }

}
