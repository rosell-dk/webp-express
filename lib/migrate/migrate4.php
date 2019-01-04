<?php

namespace WebPExpress;


include_once __DIR__ . '/../classes/Config.php';
use \WebPExpress\Config;

include_once __DIR__ . '/../classes/Messenger.php';
use \WebPExpress\Messenger;


function webpexpress_migrate4() {
    $config = Config::loadConfig();

    if ($config !== false) {
        if (isset($config['fail']) && ($config['fail'] != 'original')) {
            $config['operation-mode'] = 'tweaked';
            if (Config::saveConfigurationFile($config)) {
                Messenger::addMessage(
                    'info',
                    'WebP Express 0.10 introduces <i>operation modes</i>. Your configuration <i>almost</i> fits the mode called ' .
                        '<i>Standard</i>, however as you have set the <i>Response on failure</i> option to something other than' .
                        '<i>Original</i>, your setup has been put into <i>Tweaked</i> mode. ' .
                        '<a href="' . Paths::getSettingsUrl() . '">You might want to change that</a>.'
                );
            }
        }

        if (isset($config['redirect-to-existing-in-htaccess']) && ($config['redirect-to-existing-in-htaccess'])) {
            Messenger::addMessage(
                'info',
                'In WebP Express 0.10, the <i>.htaccess</i> rules has been altered a bit: The Cache-Control header is now set when ' .
                    'redirecting directly to an existing webp image.<br>' .
                    'You might want to <a href="' . Paths::getSettingsUrl() . '">go to the options page</a> and re-save settings in order to regenerate the <i>.htaccess</i> rules.'
            );
        }

    }

    // PSST: When creating new migration files, remember to update WEBPEXPRESS_MIGRATION_VERSION in admin.php
    update_option('webp-express-migration-version', '4');

}

webpexpress_migrate4();
