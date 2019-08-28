<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Messenger;

function webpexpress_migrate11() {

    $config = Config::loadConfigAndFix(false);  // false, because we do not need to test if quality detection is working

    // Save both configs.
    // The reason we do it is that we need to update wod-options.json, so the scripts can access the newly available
    // "enable-redirection-to-converter" and "enable-redirection-to-webp-realizer" options

    $forceHtaccessRegeneration = true;
    $result = Config::saveConfigurationAndHTAccess($config, $forceHtaccessRegeneration);

    if ($result['saved-both-config']) {
        Messenger::addMessage(
            'info',
            'Successfully migrated <i>WebP Express</i> options for 0.14.23.'
        );
        Option::updateOption('webp-express-migration-version', '11');

    } else {
        Messenger::addMessage(
            'error',
            'Failed migrating webp express options to 0.14.23. Probably you need to grant write permissions in your wp-content folder.'
        );
    }

}

webpexpress_migrate11();
