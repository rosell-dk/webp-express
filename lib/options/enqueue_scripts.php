<?php

include_once __DIR__ . '/../classes/Paths.php';
use \WebPExpress\Paths;

include_once __DIR__ . '/../classes/Config.php';
use \WebPExpress\Config;

$version = '0.10.0';

function webp_express_add_inline_script($id, $script, $position) {
    if (function_exists('wp_add_inline_script')) {
        // wp_add_inline_script is available from Wordpress 4.5
        wp_add_inline_script($id, $script, $position);
    } else {
        echo '<script>' . $script . '</script>';
    }
}
wp_register_script('sortable', plugins_url('js/sortable.min.js', __FILE__), [], '1.9.0');
wp_enqueue_script('sortable');

wp_register_script('daspopup', plugins_url('js/das-popup.js', __FILE__), [], $version);
wp_enqueue_script('daspopup');

$config = Config::getConfigForOptionsPage();

if (!(isset($config['operation-mode']) &&  $config['operation-mode'] == 'just-redirect')) {

    // Remove empty options arrays.
    // These cause trouble in json because they are encoded as [] rather than {}

    foreach ($config['converters'] as &$converter) {
        if (isset($converter['options']) && (count(array_keys($converter['options'])) == 0)) {
            unset($converter['options']);
        }
    }

    // Converters
    wp_register_script('converters', plugins_url('js/converters.js', __FILE__), ['sortable','daspopup'], $version);
    webp_express_add_inline_script('converters', 'window.webpExpressPaths = ' . json_encode(Paths::getUrlsAndPathsForTheJavascript()) . ';', 'before');
    webp_express_add_inline_script('converters', 'window.converters = ' . json_encode($config['converters']) . ';', 'before');
    wp_enqueue_script('converters');


    // Whitelist
    wp_register_script('whitelist', plugins_url('js/whitelist.js', __FILE__), ['daspopup'], $version);
    webp_express_add_inline_script('whitelist', 'window.whitelist = ' . json_encode($config['web-service']['whitelist']) . ';', 'before');
    wp_enqueue_script('whitelist');

}

//wp_register_script('api_keys', plugins_url('js/api-keys.js', __FILE__), ['daspopup'], '0.7.0-dev8');
//wp_enqueue_script('api_keys');

wp_register_script( 'page', plugins_url('js/page.js', __FILE__), [], $version);
wp_enqueue_script('page');


// Register styles
wp_register_style('webp-express-options-page-css', plugins_url('css/webp-express-options-page.css', __FILE__), null, $version);
wp_enqueue_style('webp-express-options-page-css');

wp_register_style('das-popup-css', plugins_url('css/das-popup.css', __FILE__), null, $version);
wp_enqueue_style('das-popup-css');

add_thickbox();
