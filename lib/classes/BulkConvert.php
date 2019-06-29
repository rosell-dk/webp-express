<?php

namespace WebPExpress;

use \WebPExpress\ConvertHelperIndependent;
use \WebPExpress\Paths;
use \WebPExpress\PathHelper;

class BulkConvert
{

    public static function getUploadFolder($destinationFolder)
    {
        switch ($destinationFolder) {
            case 'mingled':
                return Paths::getUploadDirAbs();
            case 'separate':
                return Paths::getCacheDirAbs() . '/doc-root/' . Paths::getUploadDirRel();
        }
    }

    public static function getList($config)
    {
        //$cacheDir = self::getUploadFolder($config['destination-folder']);


        /*
        isUploadDirMovedOutOfWPContentDir
        isUploadDirMovedOutOfAbsPath
        isPluginDirMovedOutOfAbsPath
        isPluginDirMovedOutOfWpContent
        isWPContentDirMovedOutOfAbsPath */


        $listOptions = [
            //'root' => Paths::getUploadDirAbs(),
            //'cache-root' => self::getUploadFolder($config['destination-folder']),
            'ext' => $config['destination-extension'],
            'destination-folder' => $config['destination-folder'],  /* hm, "destination-folder" is a bad name... */
            'webExpressContentDirAbs' => Paths::getWebPExpressContentDirAbs(),
            'uploadDirAbs' => Paths::getUploadDirAbs(),
            'filter' => [
                'only-converted' => false,
                'only-unconverted' => true,
                'image-types' => $config['image-types'],
            ]
        ];

        $groups = [];

        $groups[] = [
            'groupName' => 'wp-content',
            'root' => Paths::getContentDirAbs(),
        ];

        if (Paths::isUploadDirMovedOutOfWPContentDir()) {
            $groups[] = [
                'groupName' => 'uploads',
                'root' => Paths::getUploadDirAbs(),
            ];
        }

        if (Paths::isPluginDirMovedOutOfWpContent()) {
            $groups[] = [
                'groupName' => 'plugins',
                'root' => Paths::getPluginDirAbs(),
            ];
        }

        foreach ($groups as $i => &$group) {
            $listOptions['root'] = $group['root'];
            /*
            No use, because if uploads is in wp-content, the cache root will be different for the files in uploads (if mingled)
            $group['cache-root'] = ConvertHelperIndependent::getDestinationFolder(
                $group['root'],
                $listOptions['destination-folder'],
                $listOptions['ext'],
                $listOptions['webExpressContentDirAbs'],
                $listOptions['uploadDirAbs']
            );*/
            $group['files'] = self::getListRecursively('.', $listOptions);
            //'cache-root' => ConvertHelperIndependent::getDestinationFolder()
        }



        return $groups;
        //self::moveRecursively($toDir, $fromDir, $srcDir, $fromExt, $toExt);
    }

    /**
     * $filter: all | converted | not-converted. "not-converted" for example returns paths to images that has not been converted
     */
    public static function getListRecursively($relDir, &$listOptions)
    {
        $dir = $listOptions['root'] . '/' . $relDir;

        // Canonicalize because dir might contain "/./", which causes file_exists to fail (#222)
        $dir = PathHelper::canonicalize($dir);

        if (!@file_exists($dir) || !@is_dir($dir)) {
            return [];
        }

        $fileIterator = new \FilesystemIterator($dir);

        $results = [];
        $filter = &$listOptions['filter'];

        while ($fileIterator->valid()) {
            $filename = $fileIterator->getFilename();

            if (($filename != ".") && ($filename != "..")) {

                if (@is_dir($dir . "/" . $filename)) {
                    $results = array_merge($results, self::getListRecursively($relDir . "/" . $filename, $listOptions));
                } else {
                    // its a file - check if its a jpeg or png

                    if (!isset($filter['_regexPattern'])) {
                        $imageTypes = $filter['image-types'];
                        $fileExtensions = [];
                        if ($imageTypes & 1) {
                          $fileExtensions[] = 'jpe?g';
                        }
                        if ($imageTypes & 2) {
                          $fileExtensions[] = 'png';
                        }
                        $filter['_regexPattern'] = '#\.(' . implode('|', $fileExtensions) . ')$#';
                    }

                    if (preg_match($filter['_regexPattern'], $filename)) {
                        $addThis = true;

                        if (($filter['only-converted']) || ($filter['only-unconverted'])) {
                            //$cacheDir = $listOptions['cache-root'] . '/' . $relDir;
                            $destination = ConvertHelperIndependent::getDestination(
                                $dir . "/" . $filename,
                                $listOptions['destination-folder'],
                                $listOptions['ext'],
                                $listOptions['webExpressContentDirAbs'],
                                $listOptions['uploadDirAbs']
                            );
                            $webpExists = @file_exists($destination);

                            // Check if corresponding webp exists
                            /*
                            if ($listOptions['ext'] == 'append') {
                                $webpExists = @file_exists($cacheDir . "/" . $filename . '.webp');
                            } else {
                                $webpExists = @file_exists(preg_replace("/\.(jpe?g|png)\.webp$/", '.webp', $filename));
                            }*/

                            if (!$webpExists && ($filter['only-converted'])) {
                                $addThis = false;
                            }
                            if ($webpExists && ($filter['only-unconverted'])) {
                                $addThis = false;
                            }
                        } else {
                            $addThis = true;
                        }

                        if ($addThis) {
                            $results[] = substr($relDir . "/", 2) . $filename;      // (we cut the leading "./" off with substr)
                        }
                    }
                }
            }
            $fileIterator->next();
        }
        return $results;
    }

/*
    public static function convertFile($source)
    {
        $config = Config::loadConfigAndFix();
        $options = Config::generateWodOptionsFromConfigObj($config);

        $destination = ConvertHelperIndependent::getDestination(
            $source,
            $options['destination-folder'],
            $options['destination-extension'],
            Paths::getWebPExpressContentDirAbs(),
            Paths::getUploadDirAbs()
        );
        $result = ConvertHelperIndependent::convert($source, $destination, $options);

        //$result['destination'] = $destination;
        if ($result['success']) {
            $result['filesize-original'] = @filesize($source);
            $result['filesize-webp'] = @filesize($destination);
        }
        return $result;
    }
*/

    public static function processAjaxListUnconvertedFiles()
    {
        if (!check_ajax_referer('webpexpress-ajax-list-unconverted-files-nonce', 'nonce', false)) {
            wp_send_json_error('Invalid security nonce (it has probably expired - try refreshing)');
            wp_die();
        }

        $config = Config::loadConfigAndFix();
        $arr = self::getList($config);
        echo json_encode($arr, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
        wp_die();
    }

}
