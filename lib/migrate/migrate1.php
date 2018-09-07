<?php

namespace WebPExpress;

include_once __DIR__ . '/../classes/Config.php';
use \WebPExpress\Config;

include_once __DIR__ . '/../classes/Messenger.php';
use \WebPExpress\Messenger;

//Messenger::addMessage('info', 'migration:' .  get_option('webp-express-migration-version', 'not set'));

// On successful migration:
// update_option('webp-express-migration-version', '1', true);

function webp_express_migrate1_createFolders()
{
    if (!Paths::createContentDirIfMissing()) {
        Messenger::printMessage(
            'error',
            'For migration to 0.5.0, WebP Express needs to create a directory "webp-express" under your wp-content folder, but does not have permission to do so.<br>' .
                'Please create the folder manually, or change the file permissions of your wp-content folder.'
        );
        return false;
    } else {
        if (!Paths::createConfigDirIfMissing()) {
            Messenger::printMessage(
                'error',
                'For migration to 0.5.0, WebP Express needs to create a directory "webp-express/config" under your wp-content folder, but does not have permission to do so.<br>' .
                    'Please create the folder manually, or change the file permissions.'
            );
            return false;
        }


        if (!Paths::createCacheDirIfMissing()) {
            Messenger::printMessage(
                'error',
                'For migration to 0.5.0, WebP Express needs to create a directory "webp-express/webp-images" under your wp-content folder, but does not have permission to do so.<br>' .
                    'Please create the folder manually, or change the file permissions.'
            );
            return false;
        }
    }
    return true;
}

function webp_express_migrate1_createDummyConfigFiles()
{
    // TODO...
}

function webpexpress_migrate1_migrateOptions()
{
    $converters = json_decode(get_option('webp_express_converters', '[]'), true);
    foreach ($converters as &$converter) {
        unset ($converter['id']);
    }

    $options = [
        'image-types' => intval(get_option('webp_express_image_types_to_convert', 1)),
        'max-quality' => intval(get_option('webp_express_max_quality', 80)),
        'fail' => get_option('webp_express_failure_response', 'original'),
        'converters' => $converters,
        'forward-query-string' => true
    ];

    // TODO: Save
    //Messenger::addMessage('info', 'Options: <pre>' .  print_r($options, true) . '</pre>');

    //saveConfigurationFile
    //return $options;
}

if (webp_express_migrate1_createFolders()) {
    if (webp_express_migrate1_createDummyConfigFiles()) {
        webpexpress_migrate1_migrateOptions();
    }
}


//echo 'migrate 05';
/*
$optionsToDelete = [
    'webp_express_max_quality',
    'webp_express_image_types_to_convert',
    'webp_express_failure_response',
    'webp_express_converters',
    'webp-express-inserted-rules-ok',
    'webp-express-configured',
    'webp-express-pending-messages',
    'webp-express-just-activated',
    'webp-express-message-pending',
    'webp-express-failed-inserting-rules',
    'webp-express-deactivate'
];
foreach ($optionsToDelete as $i => $optionName) {
    delete_option($optionName);
}
update_option('webp-express-version', '0.5', true);
*/
/*
$converters_including_deactivated = json_decode(get_option('webp_express_converters', []), true);
$converters = [];
foreach ($converters_including_deactivated as $converter) {
    if (isset($converter['deactivated'])) continue;

    // Search for options containing "-2".
    // If they exists, we must duplicate the converter.
    $shouldDuplicate = false;
    if (isset($converter['options'])) {
        foreach ($converter['options'] as $converterOption => $converterValue) {
            if (substr($converterOption, -2) == '-2') {
                $shouldDuplicate = true;
            }
        }
    };

    if ($shouldDuplicate) {
        // Duplicate converter
        $converter2 = $converter;
        foreach ($converter['options'] as $converterOption => $converterValue) {
            if (substr($converterOption, -2) == '-2') {
                unset ($converter['options'][$converterOption]);
                unset ($converter2['options'][$converterOption]);
                $one = substr($converterOption, 0, -2);
                $converter2['options'][$one] = $converterValue;
                //$converter[$converterOption] = null;
            }
        }
        $converters[] = $converter;
        $converters[] = $converter2;
    } else {
        $converters[] = $converter;
    }
}
*/
/*
class Migrate05
{

    public static
}
*/
