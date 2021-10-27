<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Messenger;
use \WebPExpress\Option;

function webpexpress_migrate14() {

    // Update migrate version right away to minimize risk of running the update twice in a multithreaded environment
    Option::updateOption('webp-express-migration-version', '14');

    // Regenerate .htaccess files in case "Redirect to converter" is enabled in order to fix the rules.
    // See #520
    $checkIfQualityDetectionIsWorking = false;
    $config = Config::loadConfigAndFix($checkIfQualityDetectionIsWorking);
    if ($config['enable-redirection-to-converter']) {
        $forceHtaccessRegeneration = true;
        $result = Config::saveConfigurationAndHTAccess($config, $forceHtaccessRegeneration);
    }

}

webpexpress_migrate14();
