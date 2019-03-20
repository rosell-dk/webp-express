<?php

namespace WebPExpress;

/**
 *
 */

class OptionsPage
{

    // callback (registred in AdminUi)
    public static function display() {
        include WEBPEXPRESS_PLUGIN_DIR . '/lib/options/page.php';
    }

    public static function enqueueScripts() {
        include WEBPEXPRESS_PLUGIN_DIR . '/lib/options/enqueue_scripts.php';
    }
}
