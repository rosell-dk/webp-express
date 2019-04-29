<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Messenger;
use \WebPExpress\Option;

function webpexpress_migrate4() {
    $config = Config::loadConfig();

    if ($config !== false) {
        if (isset($config['cache-control'])) {
            switch ($config['cache-control']) {
                case 'no-header':
                    break;
                case 'custom':
                    break;
                default:
                    $config['cache-control-max-age'] = $config['cache-control'];
                    $config['cache-control'] = 'set';
                    $config['cache-control-public'] = true;
                    Config::saveConfigurationFile($config);
            }
        }

        if (isset($config['fail']) && ($config['fail'] != 'original')) {
            $config['operation-mode'] = 'tweaked';
            if (Config::saveConfigurationFile($config)) {
                Messenger::addMessage(
                    'info',
                    'WebP Express 0.10 introduces <i>operation modes</i>. Your configuration <i>almost</i> fits the mode called ' .
                        '<i>Standard</i>, however as you have set the <i>Response on failure</i> option to something other than ' .
                        '<i>Original</i>, your setup has been put into <i>Tweaked</i> mode. ' .
                        '<a href="' . Paths::getSettingsUrl() . '">You might want to go and change that</a>.'
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

        if (!isset($config['redirect-to-existing-in-htaccess'])) {
            Messenger::addMessage(
                'info',
                'In WebP Express 0.10, the "Redirect directly to converted image when available" option is no longer in beta. ' .
                    'You might want to <a href="' . Paths::getSettingsUrl() . '">go and activate it</a>.'
            );
        }

    }

    // PSST: When creating new migration files, remember to update WEBPEXPRESS_MIGRATION_VERSION in admin.php
    Option::updateOption('webp-express-migration-version', '4');

}

webpexpress_migrate4();
