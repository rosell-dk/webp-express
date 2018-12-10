<?php

namespace WebPExpress;


include_once __DIR__ . '/../classes/FileHelper.php';
use \WebPExpress\FileHelper;

include_once __DIR__ . '/../classes/Paths.php';
use \WebPExpress\Paths;

include_once __DIR__ . '/../classes/PathHelper.php';
use \WebPExpress\PathHelper;

include_once __DIR__ . '/../classes/Messenger.php';
use \WebPExpress\Messenger;


if ( ! function_exists('webp_express_glob_recursive'))
{
    // Does not support flag GLOB_BRACE

    function webp_express_glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir)
        {
            $files = array_merge($files, webp_express_glob_recursive($dir.'/'.basename($pattern), $flags));
        }

        return $files;
    }
}

function webpexpress_migrate3() {

    $dirs = glob(PathHelper::canonicalize(Paths::getCacheDirAbs()) . '/doc-root*');

    $movedAtLeastOneFile = false;
    $failedMovingAtLeastOneFile = false;
    $atLeastOneFileMustBeMoved = false;
    $failedRemovingAtLeastOneDir = false;

    foreach ($dirs as $dir) {
        if (preg_match('/\/doc-root$/i', $dir)) {
            // do not process the "doc-root" dir
            continue;
        }

        $files = webp_express_glob_recursive($dir . '/*.webp');
        foreach ($files as $file) {
            $atLeastOneFileMustBeMoved = true;
            $newName = preg_replace('/\/doc-root(.*)$/', '/doc-root/$1', $file);
            $dirName = FileHelper::dirName($newName);
            if (!file_exists($dirName)) {
                mkdir($dirName, 0775, true);
            }
            if (@rename($file, $newName)) {
                $movedAtLeastOneFile = true;
            } else {
                $failedMovingAtLeastOneFile = true;
            }
        }

        if (!FileHelper::rrmdir($dir)) {
            $failedRemovingAtLeastOneDir = true;
        }
    }


    if ($atLeastOneFileMustBeMoved) {
        if ($movedAtLeastOneFile && !$failedMovingAtLeastOneFile && !$failedRemovingAtLeastOneDir) {
            /*
            Messenger::printMessage(
                'info',
                'Successfully fixed cache directory structure. Nothing to worry about.'
            );*/
        } else {
            if ($failedRemovingAtLeastOneDir) {
                Messenger::printMessage(
                    'warning',
                    'A minor bug caused the cache directory structure to be wrong on your system ' .
                    '(<a href="https://github.com/rosell-dk/webp-express/issues/96" target="_blank">issue #96</a>). ' .
                    'The bug has been fixed, but unfortunately the file permissions does not allow WebP Convert to clean up the file structure. ' .
                    'To clean up manually, delete all folders in your wp-content/webp-express/webp-images folder beginning with "doc-root" (but not the "doc-root" folder itself)'
                );
            }
        }
    }

    // PSST: When creating new migration files, remember to update WEBPEXPRESS_MIGRATION_VERSION in admin.php
    update_option('webp-express-migration-version', '3');

}

webpexpress_migrate3();
