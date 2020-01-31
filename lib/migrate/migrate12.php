<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Messenger;
use \WebPExpress\Option;

function webpexpress_migrate12() {

    $config = Config::loadConfigAndFix(false);  // false, because we do not need to test if quality detection is working

/*
    if (($config['destination-extension'] == 'set') && ($config['destination-folder'] == 'mingled')) {
        DismissableMessages::addDismissableMessage('0.15.1/problems-with-mingled-set');

        Messenger::addMessage(
            'error',
            'WebP Express is experiencing technical problems with your particular setup. ' .
                'Please <a href="' . Paths::getSettingsUrl() . '">go to the settings page</a> to fix.'
        );

    }*/

    $forceHtaccessRegeneration = true;
    $result = Config::saveConfigurationAndHTAccess($config, $forceHtaccessRegeneration);

    if ($result['saved-both-config']) {
        Option::updateOption('webp-express-migration-version', '12');

    } else {
        Messenger::addMessage(
            'error',
            'Failed migrating webp express options to 0.15.1. Probably you need to grant write permissions in your wp-content folder.'
        );
    }

}

webpexpress_migrate12();
