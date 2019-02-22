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
            'Successfully migrated WebP Express options for 0.12'
        );

        if (!$config['alter-html']['enabled']) {
            if ($config['operation-mode'] == 'varied-responses') {
                Messenger::addMessage(
                    'info',
                    'In WebP Express 0.12, the <i>Alter HTML</i> option is no longer in beta. ' .
                        'You should consider to <a href="' . Paths::getSettingsUrl() . '">activate it</a>. It works great in <i>Varied Image Responses</i> mode too'
                );
            } else {
                Messenger::addMessage(
                    'info',
                    'In WebP Express 0.12, Alter HTML is no longer in beta. ' .
                        'Now would be a good time to <a href="' . Paths::getSettingsUrl() . '">activate it</a> it!'
                );
            }
        }

        // Display announcement. But only show while it is fresh news (we don't want this to show when one is upgrading from 0.11 to 0.14 or something)
        // - the next release with a migration in it will not show the announcement
        if (WEBPEXPRESS_MIGRATION_VERSION == 7) {
            Messenger::addMessage(
                'info',
                '<b>Announcement:</b> WebP Express now works in multisite setups too.'
            );
        }

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
