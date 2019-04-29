<?php

namespace WebPExpress;

use \WebPExpress\FileHelper;
use \WebPExpress\Option;
use \WebPExpress\Paths;

/**
 *
 */

class PluginUninstall
{
    // The hook was registred in AdminInit
    public static function uninstall() {

        $optionsToDelete = [
            'webp-express-messages-pending',
            'webp-express-action-pending',
            'webp-express-state',
            'webp-express-version',
            'webp-express-activation-error',
            'webp-express-migration-version'
        ];
        foreach ($optionsToDelete as $i => $optionName) {
            Option::deleteOption($optionName);
        }

        // remove content dir (config plus images plus htaccess-tests)
        FileHelper::rrmdir(Paths::getWebPExpressContentDirAbs());
    }
}
