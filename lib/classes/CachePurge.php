<?php

namespace WebPExpress;

use \WebPExpress\Convert;
use \WebPExpress\FileHelper;
use \WebPExpress\DismissableMessages;
use \WebPExpress\Paths;

// TODO! Needs to be updated to work with the new "destination-structure" setting

class CachePurge
{

    /**
     *  - Removes cache dir
     *  - Removes all files with ".webp" extension in upload dir (if set to mingled)
     */
    public static function purge($config, $onlyPng)
    {
        DismissableMessages::dismissMessage('0.14.0/suggest-wipe-because-lossless');

        $filter = [
            'only-png' => $onlyPng,
            'only-with-corresponding-original' => false
        ];

        $numDeleted = 0;
        $numFailed = 0;

        list($numDeleted, $numFailed) = self::purgeWebPFilesInDir(Paths::getCacheDirAbs(), $filter, $config);
        FileHelper::removeEmptySubFolders(Paths::getCacheDirAbs());

        if ($config['destination-folder'] == 'mingled') {
            list($d, $f) = self::purgeWebPFilesInDir(Paths::getUploadDirAbs(), $filter, $config);

            $numDeleted += $d;
            $numFailed += $f;
        }

        // Now, purge dummy files too
        $dir = Paths::getBiggerThanSourceDirAbs();
        self::purgeWebPFilesInDir($dir, $filter, $config);
        FileHelper::removeEmptySubFolders($dir);

        return [
            'delete-count' => $numDeleted,
            'fail-count' => $numFailed
        ];

        //$successInRemovingCacheDir = FileHelper::rrmdir(Paths::getCacheDirAbs());

    }


    /**
     *  Purge webp files in a dir
     *  Warning: the "only-png" option only works for mingled mode.
     *           (when not mingled, you can simply delete the whole cache dir instead)
     *
     *  @param $filter.
     *            only-png:   If true, it will only be deleted if extension is .png.webp or a corresponding png exists.
     *
     *  @return [num files deleted, num files failed to delete]
     */
    private static function purgeWebPFilesInDir($dir, &$filter, &$config)
    {
        if (!@file_exists($dir) || !@is_dir($dir)) {
            return [0, 0];
        }

        $numFilesDeleted = 0;
        $numFilesFailedDeleting = 0;

        $fileIterator = new \FilesystemIterator($dir);
        while ($fileIterator->valid()) {
            $filename = $fileIterator->getFilename();

            if (($filename != ".") && ($filename != "..")) {

                if (@is_dir($dir . "/" . $filename)) {
                    list($r1, $r2) = self::purgeWebPFilesInDir($dir . "/" . $filename, $filter, $config);
                    $numFilesDeleted += $r1;
                    $numFilesFailedDeleting += $r2;
                } else {

                    // its a file
                    // Run through filters, which each may set "skipThis" to true

                    $skipThis = false;

                    // filter: It must be a webp
                    if (!$skipThis && !preg_match('#\.webp$#', $filename)) {
                        $skipThis = true;
                    }

                    // filter: only with corresponding original
                    $source = '';
                    if (!$skipThis && $filter['only-with-corresponding-original']) {
                        $source = Convert::findSource($dir . "/" . $filename, $config);
                        if ($source === false) {
                            $skipThis = true;
                        }
                    }

                    // filter: only png
                    if (!$skipThis && $filter['only-png']) {

                        // turn logic around - we skip deletion, unless we deem it a png
                        $skipThis = true;

                        // If extension is "png.webp", its a png
                        if (preg_match('#\.png\.webp$#', $filename)) {
                            // its a png
                            $skipThis = false;
                        } else {
                            if (preg_match('#\.jpe?g\.webp$#', $filename)) {
                                // It is a jpeg, no need to investigate further.
                            } else {

                                if (!$filter['only-with-corresponding-original']) {
                                    $source = Convert::findSource($dir . "/" . $filename, $config);
                                }
                                if ($source === false) {
                                    // We could not find corresponding source.
                                    // Should we delete?
                                    // No, I guess we need more evidence, so we skip
                                    // In the future, we could detect mime
                                } else {
                                    if (preg_match('#\.png$#', $source)) {
                                        // its a png
                                        $skipThis = false;
                                    }
                                }
                            }

                        }

                    }

                    if (!$skipThis) {
                        if (@unlink($dir . "/" . $filename)) {
                            $numFilesDeleted++;
                        } else {
                            $numFilesFailedDeleting++;
                        }
                    }
                }
            }
            $fileIterator->next();
        }
        return [$numFilesDeleted, $numFilesFailedDeleting];
    }

    public static function processAjaxPurgeCache()
    {

        if (!check_ajax_referer('webpexpress-ajax-purge-cache-nonce', 'nonce', false)) {
            wp_send_json_error('The security nonce has expired. You need to reload the settings page (press F5) and try again)');
            wp_die();
        }

        $onlyPng = (sanitize_text_field($_POST['only-png']) == 'true');

        $config = Config::loadConfigAndFix();
        $result = self::purge($config, $onlyPng);

        echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
        wp_die();
    }
}
