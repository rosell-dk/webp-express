<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Messenger;

function webpexpress_migrate7() {

    $config = Config::loadConfigAndFix();
    if ($config['operation-mode'] == 'just-redirect') {
        $config['operation-mode'] = 'no-conversion';
    }

    if (Config::saveConfigurationFileAndWodOptions($config)) {

        Messenger::addMessage(
            'info',
            'Successfully migrated webp express options for 0.12+'
        );

        // PSST: When creating new migration files, remember to update WEBPEXPRESS_MIGRATION_VERSION in admin.php
        Option::updateOption('webp-express-migration-version', '7');

    } else {
        Messenger::addMessage(
            'error',
            'Failed migrating webp express options to 0.12+. Probably you need to grant write permissions in your wp-content folder.'
        );
    }

}

webpexpress_migrate7();
