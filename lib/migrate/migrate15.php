<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Messenger;
use \WebPExpress\Option;

function webpexpress_migrate15() {

    // Update migrate version right away to minimize risk of running the update twice in a multithreaded environment

    $configMigrateSuccess = Config::checkAndMigrateConfigIfNeeded();
    if ($configMigrateSuccess) {
        $config = Config::loadConfigAndFix(false);    // false means we do not need the check if quality detection is supported
        if (($config['enable-redirection-to-webp-realizer']) || ($config['enable-redirection-to-converter'])) {

            // We need to regenerate .htaccess files if web-realizer or webp-on-demand is active,
            // so they get the new ConfigHash
            wp_schedule_single_event(time() + 1, 'webp_express_task_regenerate_config_and_htaccess');
        }
        Option::updateOption('webp-express-migration-version', '15');
    } else {
        DismissableGlobalMessages::addDismissableMessage('0.25.10/failed-renaming-config-file');
    }
}

webpexpress_migrate15();
