<?php

include_once __DIR__ . '/../classes/Paths.php';
use \WebPExpress\Paths;

wp_register_script('sortable', plugins_url('js/sortable.min.js', __FILE__), [], '1.9.0');
wp_enqueue_script('sortable');

wp_register_script(
    'webp-express-options-page',
    plugins_url('js/webp-express-options-page.js', __FILE__),
    ['sortable'],
    '0.6.0dev5'
);
wp_enqueue_script('webp-express-options-page');

if (function_exists('wp_add_inline_script')) {
    // wp_add_inline_script is available from Wordpress 4.5
    wp_add_inline_script('webp-express-options-page', 'window.webpExpressPaths = ' . json_encode(Paths::getUrlsAndPathsForTheJavascript()) . ';');
} else {
    echo '<script>window.webpExpressPaths = ' . json_encode(Paths::getUrlsAndPathsForTheJavascript()) . ';</script>';
}

wp_register_style(
    'webp-express-options-page-css',
    plugins_url('css/webp-express-options-page.css', __FILE__),
    null,
    '0.6.0dev2'
);
wp_enqueue_style('webp-express-options-page-css');

add_thickbox();
