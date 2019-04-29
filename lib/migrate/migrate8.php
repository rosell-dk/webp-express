<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Messenger;
use \WebPExpress\Option;
use \WebPExpress\Paths;

function webpexpress_migrate8() {

    $config = Config::loadConfigAndFix(false);  // false, because we do not need to test if quality detection is working
    $converters = $config['converters'];
    if (is_array($converters)) {

        $firstActiveAndWorking;
        foreach ($converters as $converter) {
            if (isset($converter['deactivated']) && $converter['deactivated']) {
                continue;
            }
            if (isset($converter['working']) && !$converter['working']) {
                continue;
            }
            $firstActiveAndWorking = $converter;
            break;
        }
        if (isset($firstActiveAndWorking)) {
            if (isset($firstActiveAndWorking['converter']) && $firstActiveAndWorking['converter'] == 'gd') {

                // First working converter is Gd.
                if (isset($firstActiveAndWorking['options']) && $firstActiveAndWorking['options']['skip-pngs'] === false) {
                    // And it is set up to convert PNG's

                    Messenger::addMessage(
                        'info',
                        'Service notice from WebP Express:<br>' .
                            'You have been using <i>Gd</i> to convert PNGs. ' .
                            'However, due to a bug, in some cases transparency was lost in the webp. ' .
                            'It is recommended that you delete and reconvert all PNGs. ' .
                            'There are new buttons for doing just that on the ' .
                            '<a href="' . Paths::getSettingsUrl() . '">settings screen</a> (look below the conversion methods).'
                    );

                } else {

                    Messenger::addMessage(
                        'info',
                        'Service notice from WebP Express:<br>' .
                        'You have configured <i>Gd</i> to skip converting PNGs. ' .
                            'However, the <i>Gd</i> conversion method has been fixed and is doing ok now!'
                    );


                }
            }
        }
    }

    if (WEBPEXPRESS_MIGRATION_VERSION == '8') {
        Messenger::addMessage(
            'info',
            'New in WebP Express 0.13.0:' .
                '<ul style="list-style-type:disc; list-style-position: inside">' .
                '<li>Bulk Conversion</li>' .
                '<li>New option to automatically convert images upon upload</li>' .
                '<li>Better support for Windows servers</li>' .
                '<li>- <a href="https://github.com/rosell-dk/webp-express/milestone/16?closed=1" target="_blank">and more</a></li>' .
                '</ul>'
        );

    }

    Option::updateOption('webp-express-migration-version', '8');

    // Find out if Gd is the first active and working converter.
    // We check wod options, because it has already filtered out the disabled converters.
    /*
    $options = Config::loadWodOptions();
    if ($options !== false) {
        $converters = $options['converters'];
        if (is_array($converters) && count($converters) > 0) {

            if ($converters[0]['converter'] == 'gd') {
                if (isset($converters[0]['options']) && ($converters[0]['options']['skip-pngs'] === true)) {
                    //
                }
            }
        }
    }

    if ($config['operation-mode'] != 'no-conversion') {
        Config::getConverterByName('gd')
    }*/


}

webpexpress_migrate8();
