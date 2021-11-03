<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Messenger;
use \WebPExpress\Option;

function webpexpress_migrate14() {

    // Update migrate version right away to minimize risk of running the update twice in a multithreaded environment
    Option::updateOption('webp-express-migration-version', '14');

    // Regenerate .htaccess files in case redirection to webp is enabled. Two reasons:
    // 1: WebP On Demand rules needs fixing (#520)
    // 2: The new escape hatch (#522), which is needed for the File Manager (#521)

    $checkIfQualityDetectionIsWorking = false;
    $config = Config::loadConfigAndFix($checkIfQualityDetectionIsWorking);
    if (($config['enable-redirection-to-converter']) || ($config['redirect-to-existing-in-htaccess'])) {
        $forceHtaccessRegeneration = true;
        $result = Config::saveConfigurationAndHTAccess($config, $forceHtaccessRegeneration);
    }

    // Schedule bulk update dummy files
    wp_schedule_single_event(time() + 10, 'webp_express_task_bulk_update_dummy_files');

}

webpexpress_migrate14();
