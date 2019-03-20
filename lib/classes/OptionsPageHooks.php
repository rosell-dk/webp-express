<?php

namespace WebPExpress;

/**
 *
 */

class OptionsPageHooks
{

    // callback for 'admin_post_webpexpress_settings_submit' (registred in AdminInit::addHooks)
    public static function submitHandler() {
        include WEBPEXPRESS_PLUGIN_DIR . '/lib/options/submit.php';
    }
}
