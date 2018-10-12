<?php

include_once __DIR__ . '/../classes/Paths.php';
use \WebPExpress\Paths;

wp_register_script('sortable', plugins_url('js/sortable.min.js', __FILE__), [], '1.9.0');
wp_enqueue_script('sortable');

wp_register_script('converters', plugins_url('js/converters.js', __FILE__), ['sortable'], '0.7.0-dev4');
wp_enqueue_script('converters');

wp_register_script( 'whitelist', plugins_url('js/whitelist.js', __FILE__), [], '0.7.0-dev3');
wp_enqueue_script('whitelist');

if (function_exists('wp_add_inline_script')) {
    // wp_add_inline_script is available from Wordpress 4.5
    wp_add_inline_script('converters', 'window.webpExpressPaths = ' . json_encode(Paths::getUrlsAndPathsForTheJavascript()) . ';');
} else {
    echo '<script>window.webpExpressPaths = ' . json_encode(Paths::getUrlsAndPathsForTheJavascript()) . ';</script>';
}

wp_register_style(
    'webp-express-options-page-css',
    plugins_url('css/webp-express-options-page.css', __FILE__),
    null,
    '0.6.0'
);
wp_enqueue_style('webp-express-options-page-css');

add_thickbox();
