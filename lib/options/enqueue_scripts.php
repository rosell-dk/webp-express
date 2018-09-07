<?php

include_once __DIR__ . '/../classes/Paths.php';
use \WebPExpress\Paths;

wp_register_script('sortable', plugins_url('js/sortable.min.js', __FILE__), [], '1.9.0');
wp_enqueue_script('sortable');

wp_register_script(
    'webp-express-options-page',
    plugins_url('js/webp-express-options-page.js', __FILE__),
    ['sortable'],
    '0.2.0'
);
wp_enqueue_script('webp-express-options-page');

wp_add_inline_script('webp-express-options-page', 'window.webpExpressPaths = ' . json_encode(Paths::getUrlsAndPathsForTheJavascript()) . ';');

wp_register_style(
    'webp-express-options-page-css',
    plugins_url('css/webp-express-options-page.css', __FILE__),
    null,
    '0.2.0'
);
wp_enqueue_style('webp-express-options-page-css');

add_thickbox();
