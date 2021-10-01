<?php

namespace WebPExpress;
use \WebPConvert\WebPConvert;
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

  /*      require_once __DIR__ . "/../../vendor/autoload.php";
//        print_r(WebPConvert::getConverterOptionDefinitions('png', false, true));
        echo '<pre>' .
            print_r(
                json_encode(
                    WebPConvert::getConverterOptionDefinitions('png', false, true),
                    JSON_PRETTY_PRINT
                ),
                true
            ) . '</pre>';*/
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

        echo '<scr' . 'ipt src="' . $baseUrl . '/wcfm-options.js?9"></scr' . 'ipt>';
        //echo '<scr' . 'ipt type="module" src="' . $baseUrl . '/vendor.js?1"></scr' . 'ipt>';

        // TODO: Use generated name (ie index.bc30fc12.js) and make a script in npm for automatically
        // updating this file when copying
        echo '<scr' . 'ipt type="module" src="' . $baseUrl . '/wcfm.js?4"></scr' . 'ipt>';

        echo '<link rel="stylesheet" href="' . $baseUrl . '/style.css?2">';
    }

}
