<?php

namespace WebPExpress;

include_once __DIR__ . '/../classes/Config.php';
use \WebPExpress\Config;

include_once __DIR__ . '/../classes/Paths.php';
use \WebPExpress\Paths;

include_once __DIR__ . '/../classes/Messenger.php';
use \WebPExpress\Messenger;

include_once __DIR__ . '/../classes/TestRun.php';
use \WebPExpress\TestRun;

function webpexpress_migrate3() {

    $changedSomething = false;
    $config = Config::loadConfig();
    if ($config !== false) {
        if (isset($config['converters'])) {
            foreach ($config['converters'] as &$converter) {
                if (isset($converter['converter']) && ($converter['converter'] == 'wpc') && (isset($converter['options']))) {
                    if (isset($converter['options']['secret'])) {
                        $converter['options']['api-key'] = $converter['options']['secret'];
                        unset($converter['options']['secret']);
                        $converter['options']['api-version'] = 0;
                    } else {
                        $converter['options']['api-version'] = 1;                        
                    }

                    unset($converter['options']['url-2']);
                    unset($converter['options']['secret-2']);

                    $changedSomething = true;

                }
            }
            if ($changedSomething) {
                if (Config::saveConfigurationFileAndWodOptions($config)) {
                    Messenger::addMessage(
                        'info',
                        'WebP Express successfully migrated configuration file to 0.7.0 format'
                    );
                } else {
                    Messenger::addMessage(
                        'warning',
                        'WebP Express could not migrated configuration files to 0.7.0 format, because it failed saving the files. ' .
                        'If you use the wpc converter, you should change the configuration files manually (located in wp-content/webp-express/config). ' .
                        'You should change "secret" to "api-key"'
                    );
                    return;
                }
            }
        }
    }

    // PSST: When creating new migration files, remember to update WEBPEXPRESS_MIGRATION_VERSION in admin.php

    update_option('webp-express-migration-version', '3');

}

webpexpress_migrate3();
