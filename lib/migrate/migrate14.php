<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Messenger;
use \WebPExpress\Option;

function webpexpress_migrate14() {

    // Update migrate version right away to minimize risk of running the update twice in a multithreaded environment
    Option::updateOption('webp-express-migration-version', '14');

    $config = Config::loadConfigAndFix(false);    // false means we do not need the check if quality detection is supported
    if (($config['enable-redirection-to-converter']) || ($config['redirect-to-existing-in-htaccess'])) {

        // We need to regenerate .htaccess files in case redirection to webp is enabled. Two reasons:
        // 1: WebP On Demand rules needs fixing (#520)
        // 2: The new escape hatch (#522), which is needed for the File Manager (#521)
        wp_schedule_single_event(time() + 10, 'webp_express_task_regenerate_config_and_htaccess');
    } else {
        /*
        if (isset($config['alter-html']) && $config['alter-html']['enabled']) {
            // Schedule to regenate config, because we need to update autoloaded options in order to
            // autoload the new alter-html/prevent-using-webps-larger-than-original option
            // (hm, actually it defaults to true, so it should be neccessary...)
            wp_schedule_single_event(time() + 10, 'webp_express_task_regenerate_config');
        }*/
    }

    // Schedule bulk update dummy files
    wp_schedule_single_event(time() + 30, 'webp_express_task_bulk_update_dummy_files');

}

webpexpress_migrate14();
