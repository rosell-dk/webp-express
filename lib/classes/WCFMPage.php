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
        echo '<div id="wcfmintro">' .
          '<h1>WebP Express Conversion Browser</h1>' .
          '<p>' .
          'Note: To convert manually, you still need to use Bulk Convert on the settings page ' .
          '(or you can use WP CLI)' .
          '</p>' .
          '</div>';

        echo '<div id="webpconvert-filemanager" style="position:relative; min-height:400px">loading</div>';
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

        echo '<scr' . 'ipt src="' . $baseUrl . '/wcfm-options.js?11"></scr' . 'ipt>';
        //echo '<scr' . 'ipt type="module" src="' . $baseUrl . '/vendor.js?1"></scr' . 'ipt>';

        // TODO:  make a script in npm for automatically updating the filenames below when copying
        echo '<scr' . 'ipt type="module" src="' . $baseUrl . '/index.f8d1bd25.js"></scr' . 'ipt>';
        echo '<link rel="stylesheet" href="' . $baseUrl . '/index.ab43bb2c.css">';
    }

}
