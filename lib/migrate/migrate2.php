<?php

namespace WebPExpress;

use \WebPExpress\Messenger;
use \WebPExpress\Option;
use \WebPExpress\Paths;
use \WebPExpress\TestRun;

/* helper. Remove dir recursively. No warnings - fails silently
   Set $removeTheDirItself to false if you want to empty the dir
*/
function webpexpress_migrate2_rrmdir($dir, $removeTheDirItself = true) {
    if (@is_dir($dir)) {
        $objects = @scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                $file = $dir . "/" . $object;
                if (@is_dir($file)) {
                    webpexpress_migrate2_rrmdir($file);
                } else {
                    @unlink($file);
                }
            }
        }
        if ($removeTheDirItself) {
            @rmdir($dir);
        }
    }
}

$testResult = TestRun::getConverterStatus();
if ($testResult) {
    $workingConverters = $testResult['workingConverters'];
    if (in_array('imagick', $workingConverters)) {
       webpexpress_migrate2_rrmdir(Paths::getCacheDirAbs(), false);
       Messenger::addMessage(
           'info',
           'WebP Express has emptied the image cache. In previous versions, the imagick converter ' .
              'was generating images in poor quality. This has been fixed. As your system meets the ' .
              'requirements of the imagick converter, it might be that you have been using that. So ' .
              'to be absolutely sure you do not have inferior conversions in the cache dir, it has been emptied.'
       );
    }
    if (in_array('gmagick', $workingConverters)) {
        Messenger::addMessage(
            'info',
            'Good news! WebP Express is now able to use the gmagick extension for conversion - ' .
               'and your server meets the requirements!'
        );
    }
    if (in_array('cwebp', $workingConverters)) {
        Messenger::addMessage(
            'info',
            'WebP Express added several options for the cwebp conversion method. ' .
                '<a href="' . Paths::getSettingsUrl() . '">Go to the settings page to check it out</a>.'
        );
    }
}
Messenger::addMessage(
    'info',
    'WebP Express can now be configured to cache the webp images. You might want to ' .
        '<a href="' . Paths::getSettingsUrl() . '">do that</a>.'
);


Option::updateOption('webp-express-migration-version', '2');
