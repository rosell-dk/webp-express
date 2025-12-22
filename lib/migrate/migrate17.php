<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Messenger;
use \WebPExpress\Option;
use \WebPExpress\Paths;

function webpexpress_migrate17() {

    // Update migrate version right away to minimize risk of running the update twice in a multithreaded environment
    Option::updateOption('webp-express-migration-version', '17');

    if (PlatformInfo::isNginx()) {
        $configMigrateSuccess = Config::checkAndMigrateConfigIfNeeded();
        if ($configMigrateSuccess) {
            $config = Config::loadConfigAndFix(false);    // false means we do not need the check if quality detection is supported
            if (($config['enable-redirection-to-webp-realizer']) || ($config['enable-redirection-to-converter'])) {
                DismissableGlobalMessages::addDismissableMessage('0.25.12/nginx-rewrites-needs-updating');
            }
        }
    }
}

webpexpress_migrate17();
