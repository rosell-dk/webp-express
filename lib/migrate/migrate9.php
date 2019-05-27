<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Messenger;
use \WebPExpress\Option;
use \WebPExpress\Paths;

/**
 * Get first working and active converter.
 * @return  object|false
 */
function webpexpress_migrate9_getFirstWorkingAndActiveConverter($config) {

    if (!isset($config['converters'])) {
        return false;
    }
    $converters = $config['converters'];

    if (!is_array($converters)) {
        return false;
    }

    // Find first active and working.
    foreach ($converters as $c) {
        if (isset($c['deactivated']) && $c['deactivated']) {
            continue;
        }
        if (isset($c['working']) && !$c['working']) {
            continue;
        }
        return $c;
    }
    return false;
}

/**
 * Move a converter to the top
 * @return  boolean
 */
function webpexpress_migrate9_moveConverterToTop(&$config, $converterId) {

    if (!isset($config['converters'])) {
        return false;
    }

    if (!is_array($config['converters'])) {
        return false;
    }

    // find index of vips
    $indexOfVips = -1;
    $vips = null;
    foreach ($config['converters'] as $i => $c) {
        if ($c['converter'] == $converterId) {
            $indexOfVips = $i;
            $vips = $c;
            break;
        }
    }
    if ($indexOfVips > 0) {
        // remove vips found
        array_splice($config['converters'], $indexOfVips, 1);

        // Insert vips at the top
        array_unshift($config['converters'], $vips);

    }
    return false;
}

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

        $firstActiveAndWorking = webpexpress_migrate9_getFirstWorkingAndActiveConverter($config);

        // Find out if first working is cwebp
        // - because Vips is better than any other converter, except perhaps cwebp
        // (and it is ok to have a non-functional vips on the top)
        $firstWorkingIsCwebP = false;
        if (
            ($firstActiveAndWorking !== false) &&
            isset($firstActiveAndWorking['converter']) &&
            ($firstActiveAndWorking['converter'] == 'cwebp')
        ) {
                $firstWorkingIsCwebP = true;
        };

        // If it aint cwebp, move vips to the top!
        if (!$firstWorkingIsCwebP) {
            $vips = webpexpress_migrate9_moveConverterToTop($config, 'vips');
        }
    }

    // #235
    $config['cache-control-custom'] = preg_replace('#max-age:#', 'max-age=', $config['cache-control-custom']);

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
