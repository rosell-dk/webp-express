<?php

include_once __DIR__ . '/../classes/Paths.php';
use \WebPExpress\Paths;

wp_register_script('sortable', plugins_url('js/sortable.min.js', __FILE__), [], '1.9.0');
wp_enqueue_script('sortable');

wp_register_script('daspopup', plugins_url('js/das-popup.js', __FILE__), [], '0.7.0-dev5');
wp_enqueue_script('daspopup');

wp_register_script('converters', plugins_url('js/converters.js', __FILE__), ['sortable','daspopup'], '0.8.0-dev7');
wp_enqueue_script('converters');

wp_register_script('whitelist', plugins_url('js/whitelist.js', __FILE__), ['daspopup'], '0.7.0-dev15');
wp_enqueue_script('whitelist');

//wp_register_script('api_keys', plugins_url('js/api-keys.js', __FILE__), ['daspopup'], '0.7.0-dev8');
//wp_enqueue_script('api_keys');

wp_register_script( 'page', plugins_url('js/page.js', __FILE__), [], '0.7.0-dev6');
wp_enqueue_script('page');

if (function_exists('wp_add_inline_script')) {
    // wp_add_inline_script is available from Wordpress 4.5
    wp_add_inline_script('converters', 'window.webpExpressPaths = ' . json_encode(Paths::getUrlsAndPathsForTheJavascript()) . ';');
} else {
    echo '<script>window.webpExpressPaths = ' . json_encode(Paths::getUrlsAndPathsForTheJavascript()) . ';</script>';
}

// Register styles
wp_register_style('webp-express-options-page-css', plugins_url('css/webp-express-options-page.css', __FILE__), null, '0.8.0-dev5');
wp_enqueue_style('webp-express-options-page-css');

wp_register_style('das-popup-css', plugins_url('css/das-popup.css', __FILE__), null, '0.7.0-dev5');
wp_enqueue_style('das-popup-css');

add_thickbox();
