<?php

namespace WebPExpress;

use \WebPExpress\CacheMover;
use \WebPExpress\Config;
use \WebPExpress\Messenger;
use \WebPExpress\Option;

function webpexpress_migrate5() {

    // Regenerate configuration file and wod-options.json.

    // By regenerating the config, we ensure that Config::updateAutoloadedOptions() is called,
    // By regenerating wod-options.json, we ensure that the new "paths" option is there, which is required in "mingled" mode for
    // determining if an image resides in the uploads folder or not.

    $config = Config::loadConfigAndFix(false);  // false, because we do not need to test if quality detection is working
    if ($config['operation-mode'] == 'just-convert') {
        $config['operation-mode'] = 'no-varied-responses';
    }
    if ($config['operation-mode'] == 'standard') {
        $config['operation-mode'] = 'varied-responses';
    }

    if (Config::saveConfigurationFileAndWodOptions($config)) {

        // Moving destination in v0.10 might have created bad permissions. - so lets fix the permissions
        //CacheMover::chmodFixSubDirs(CacheMover::getUploadFolder($config['destination-folder']));
        CacheMover::chmodFixSubDirs(Paths::getCacheDirAbs(), true);
        CacheMover::chmodFixSubDirs(Paths::getUploadDirAbs(), false);

        Messenger::addMessage(
            'info',
            'Successfully migrated <i>WebP Express</i> options for 0.11+'
        );

        // PSST: When creating new migration files, remember to update WEBPEXPRESS_MIGRATION_VERSION in admin.php
        Option::updateOption('webp-express-migration-version', '5');

    } else {
        Messenger::addMessage(
            'error',
            'Failed migrating WebP Express options to 0.11+. Probably you need to grant write permissions in your wp-content folder.'
        );
    }

}

webpexpress_migrate5();
