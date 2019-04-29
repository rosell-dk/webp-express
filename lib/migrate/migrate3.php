<?php

namespace WebPExpress;

use \WebPExpress\FileHelper;
use \WebPExpress\Messenger;
use \WebPExpress\Option;
use \WebPExpress\Paths;
use \WebPExpress\PathHelper;

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
            Messenger::addMessage(
                'info',
                'Successfully fixed cache directory structure. Dont know what its all about? Never mind, all is okay.'
            );
        } else {
            if ($failedRemovingAtLeastOneDir) {
                Messenger::addMessage(
                    'warning',
                    'A minor bug caused the cache directory structure to be wrong on your system ' .
                    '(<a href="https://github.com/rosell-dk/webp-express/issues/96" target="_blank">issue #96</a>). ' .
                    'The bug has been fixed, but unfortunately the file permissions does not allow WebP Convert to clean up the file structure. ' .
                    'To clean up manually, delete all folders in your wp-content/webp-express/webp-images folder beginning with "doc-root" (but not the "doc-root" folder itself)'
                );
            }
        }
    }

    // Show "Whats new" message.
    // We test the version, because we do not want a whole lot of "whats new" messages
    // to show when updating many versions in one go. Just the recent, please.
    if (WEBPEXPRESS_MIGRATION_VERSION == '3') {
        Messenger::addMessage(
            'info',
            '<i>New in WebP Express 0.8.0:</i>' .
            '<ul style="list-style-type:disc;margin-left:20px">' .
            '<li>New conversion method, which calls imagick binary directly</li>' .
            '<li>Made sure not to trigger LFI warning i Wordfence (to activate, click the force .htaccess button)</li>' .
            "<li>Imagick can now be configured to set quality to auto on systems where the auto option isn't generally available</li>" .
            '<li><a href="https://github.com/rosell-dk/webp-express/issues?q=is%3Aclosed+milestone%3A0.8.0">and more...</a></li>' .
            '</ul>' .
            '</ul>' .
            '<br><i>Roadmap / wishlist:</i>' .
            '<ul style="list-style-type:disc;margin-left:20px">' .
            '<li>Rule in .htaccess to serve already converted images immediately (optional)</li>' .
            '<li>Better NGINX support (print rules that needs to be manually inserted in nginx.conf)</li>' .
            '<li>Diagnose button</li>' .
            '<li>A file explorer for viewing converted images, reconverting them, and seeing them side by side with the original</li>' .
            '<li>IIS support, WAMP support, Multisite support</li>' .
            '<li><a href="https://github.com/rosell-dk/webp-express/issues">and more...</a></li>' .
            '</ul>' .
            '<b>Please help me making this happen faster / happen at all by donating even a small sum. ' .
            '<a href="https://ko-fi.com/rosell" target="_blank" >Buy me a coffee</a>, ' .
            'or support me on <a href="http://www.patreon.com/rosell" target="_blank" >patreon.com</a>' .
            '</b>'
        );
    }


    // PSST: When creating new migration files, remember to update WEBPEXPRESS_MIGRATION_VERSION in admin.php
    Option::updateOption('webp-express-migration-version', '3');

}

webpexpress_migrate3();
