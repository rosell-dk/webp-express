<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Messenger;
use \WebPExpress\Option;
use \WebPExpress\Paths;

function webpexpress_migrate9() {

    $config = Config::loadConfigAndFix(false);  // false, because we do not need to test if quality detection is working
    $converters = &$config['converters'];
    if (is_array($converters)) {

        foreach ($converters as &$converter) {
            if (!isset($converter['converter'])) {
                continue;
            }
            if (!isset($converter['options'])) {
                continue;
            }
            $options = &$converter['options'];

            switch ($converter['converter']) {
                case 'gd':
                    if (isset($options['skip-pngs'])) {
                        $options['png'] = [
                            'skip' => $options['skip-pngs']
                        ];
                        unset($options['skip-pngs']);
                    }
                    break;
                case 'wpc':
                    if (isset($options['url'])) {
                        $options['api-url'] = $options['url'];
                        unset($options['url']);
                    }
                    break;
                case 'ewww':
                    if (isset($options['key'])) {
                        $options['api-key'] = $options['key'];
                        unset($options['key']);
                    }
                    if (isset($options['key-2'])) {
                        $options['api-key-2'] = $options['key-2'];
                        unset($options['key-2']);
                    }
                    break;
            }
        }
    }

    if (Config::saveConfigurationFileAndWodOptions($config)) {

        Messenger::addMessage(
            'info',
            'Successfully migrated <i>WebP Express</i> options for 0.14. '
        );

        //Option::updateOption('webp-express-migration-version', '9');

    } else {
        Messenger::addMessage(
            'error',
            'Failed migrating webp express options to 0.14+. Probably you need to grant write permissions in your wp-content folder.'
        );
    }
}

webpexpress_migrate9();
