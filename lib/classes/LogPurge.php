<?php

namespace WebPExpress;

class LogPurge
{

    /**
     *  - Removes cache dir
     *  - Removes all files with ".webp" extension in upload dir (if set to mingled)
     */
    public static function purge()
    {
        DismissableMessages::dismissMessage('0.14.0/suggest-wipe-because-lossless');

        $filter = [
            'only-png' => $onlyPng,
            'only-with-corresponding-original' => false
        ];

        $numDeleted = 0;
        $numFailed = 0;

        $dir = Paths::getLogDirAbs();
        list($numDeleted, $numFailed) = self::purgeLogFilesInDir($dir);
        FileHelper::removeEmptySubFolders($dir);

        return [
            'delete-count' => $numDeleted,
            'fail-count' => $numFailed
        ];

        //$successInRemovingCacheDir = FileHelper::rrmdir(Paths::getCacheDirAbs());

    }


    /**
     *  Purge log files in a dir
     *
     *  @return [num files deleted, num files failed to delete]
     */
    private static function purgeLogFilesInDir($dir)
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
                    list($r1, $r2) = self::purgeLogFilesInDir($dir . "/" . $filename);
                    $numFilesDeleted += $r1;
                    $numFilesFailedDeleting += $r2;
                } else {

                    // its a file
                    // Run through filters, which each may set "skipThis" to true

                    $skipThis = false;

                    // filter: It must have ".md" extension
                    if (!$skipThis && !preg_match('#\.md$#', $filename)) {
                        $skipThis = true;
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

    public static function processAjaxPurgeLog()
    {

        if (!check_ajax_referer('webpexpress-ajax-purge-log-nonce', 'nonce', false)) {
            wp_send_json_error('The security nonce has expired. You need to reload the settings page (press F5) and try again)');
            wp_die();
        }
        $result = self::purge($config);
        echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
        wp_die();
    }
}
