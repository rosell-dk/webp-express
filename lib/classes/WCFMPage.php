<?php

namespace WebPExpress;

/**
 *
 */

class WCFMPage
{

    // callback (registred in AdminUi)
    public static function display() {
        echo '<h1>WebP Express Conversion Manager</h1>';
        echo '<p>Work in progress! Currently, actions are not working, only file browser</p>';
        echo '<div id="webpconvert-filemanager">loading</div>';
        //include WEBPEXPRESS_PLUGIN_DIR . '/lib/options/page.php';
    }

    /* We add directly to head instead, to get the type="module"
    public static function enqueueScripts() {
        $ver = '0';
        wp_register_script('wcfileman', plugins_url('js/wcfm/index.js', WEBPEXPRESS_PLUGIN), [], $ver);
        wp_enqueue_script('wcfileman');
    }*/

    public static function addToHead() {
        $baseUrl = plugins_url('lib/wcfm', WEBPEXPRESS_PLUGIN);
        //$url = plugins_url('js/conversion-manager/index.9149ea80.js', WEBPEXPRESS_PLUGIN);

        $wcfmNonce = wp_create_nonce('webpexpress-wcfm-nonce');
        echo '<scr' . 'ipt>window.webpExpressWCFMNonce = "' . $wcfmNonce . '";</scr' . 'ipt>';

        echo '<scr' . 'ipt src="' . $baseUrl . '/wcfm-options.js?7"></scr' . 'ipt>';
        echo '<scr' . 'ipt type="module" src="' . $baseUrl . '/wcfm.js?3"></scr' . 'ipt>';

        echo '<link rel="stylesheet" href="' . $baseUrl . '/style.css?1">';
    }

}
