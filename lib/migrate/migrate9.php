<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\ConvertersHelper;
use \WebPExpress\DismissableMessages;
use \WebPExpress\Messenger;
use \WebPExpress\Option;
use \WebPExpress\Paths;

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


        foreach ($converters as $i => $converter) {
            if (!isset($converter['converter'])) {
                continue;
            }
            if ($converter['converter'] == 'gmagickbinary') {
                $converters[$i]['converter'] = 'graphicsmagick';
            }
            if ($converter['converter'] == 'imagickbinary') {
                $converters[$i]['converter'] = 'imagemagick';
            }
        }

        // Change specific converter options
        foreach ($converters as &$converter) {
            if (!isset($converter['converter'])) {
                continue;
            }
            if (!isset($converter['options'])) {
                // #273
                $converter['options'] = [];
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

        $firstActiveAndWorkingConverterId = ConvertersHelper::getFirstWorkingAndActiveConverterId($config);

        // If it aint cwebp, move vips to the top!
        if ($firstActiveAndWorkingConverterId != 'cwebp') {
            $vips = webpexpress_migrate9_moveConverterToTop($config, 'vips');
        }

/*
        if ($config['image-types'] == 1) {
            Messenger::addStickyMessage(
                'info',
                'WebP Express 0.14 handles PNG to WebP conversions quite well. Perhaps it is time to enable PNGs? ' .
                    'Go to the <a href="' . Paths::getSettingsUrl() . '">options</a> page to change the "Image types to work on" option.',
                2,
                'Got it!'
            );
        }*/

        if ($config['image-types'] == 1) {
            DismissableMessages::addDismissableMessage('0.14.0/suggest-enable-pngs');
        }
        DismissableMessages::addDismissableMessage('0.14.0/suggest-wipe-because-lossless');
        DismissableMessages::addDismissableMessage('0.14.0/say-hello-to-vips');

        /*
        $convertersSupportingEncodingAuto = ['cwebp', 'vips', 'imagick', 'imagemagick', 'gmagick', 'graphicsmagick'];

        if (in_array($firstActiveAndWorkingConverterId, $convertersSupportingEncodingAuto)) {
            Messenger::addStickyMessage(
                'info',
                'WebP Express 0.14 has new options for the conversions. Especially, it can now produce lossless webps, and ' .
                    'it can automatically try both lossy and lossless and select the smallest. You can play around with the ' .
                    'new options when your click "test" next to a converter. Once satisfied, dont forget to ' .
                    'wipe your existing converted files (there is a "Delete converted files" button for that on the ' .
                    '<a href="' . Paths::getSettingsUrl() . '">options page</a>)',
                1,
                'Got it!'
            );
        } else {
            //error_log('working converters: ' . print_r(ConvertersHelper::getWorkingConverterIds($config), true));
            $workingIds = ConvertersHelper::getWorkingConverterIds($config);

            if ($firstActiveAndWorkingConverterId == 'gd') {
                foreach ($workingIds as $workingId) {
                    if (in_array($workingId, $convertersSupportingEncodingAuto)) {
                        Messenger::addStickyMessage(
                            'info',
                            'WebP Express 0.14 has new options for the conversions. Especially, it can now produce lossless webps, and ' .
                                'it can automatically try both lossy and lossless and select the smallest. You can play around with the ' .
                                'new options when your click "test" next to a converter. Once satisfied, dont forget to ' .
                                'wipe your existing converted files (there is a "Delete converted files" button for that on the ' .
                                '<a href="' . Paths::getSettingsUrl() . '">options page</a>). ' .
                                '<br><br>Btw: The "gd" conversion method that you are using does not support lossless encoding ' .
                                '(in fact Gd only supports very few conversion options), but fortunately, you have the ' .
                                '"' . $workingId . '" conversion method working, so you can simply start using that instead.',
                            1,
                            'Got it!'
                        );
                        break;
                    }
                }

            }
            //
        }
        */
    }

    // #235
    $config['cache-control-custom'] = preg_replace('#max-age:#', 'max-age=', $config['cache-control-custom']);

    // #272
    if ($config['fail'] == 'report-as-image') {
        $config['fail'] = 'report';
    }

    // Force htaccess ?
    $forceHtaccessRegeneration = $config['redirect-to-existing-in-htaccess'];

    // Save both configs and perhaps also htaccess
    $result = Config::saveConfigurationAndHTAccess($config, $forceHtaccessRegeneration);

    if ($result['saved-both-config']) {
        Messenger::addMessage(
            'info',
            'Successfully migrated <i>WebP Express</i> options for 0.14. '
        );
        Option::updateOption('webp-express-migration-version', '9');

    } else {
        Messenger::addMessage(
            'error',
            'Failed migrating webp express options to 0.14+. Probably you need to grant write permissions in your wp-content folder.'
        );
    }

}

webpexpress_migrate9();
