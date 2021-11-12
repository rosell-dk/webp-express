<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use \WebPExpress\Paths;
use \WebPExpress\Config;

// Note: $ver is added to querystring. However, when it is is critical that no-one gets the cached version,
// a change of filename is neccessary, as there is a plugin that strips version strings out there...!
// If only one file update is critical: change the name of the file
// If several files are critical: rename the folder (ie "js2")
$ver = '3';  // note: Minimum 1.
$jsDir = 'js';

if (!function_exists('webp_express_add_inline_script')) {
    function webp_express_add_inline_script($id, $script, $position) {
        if (function_exists('wp_add_inline_script')) {
            // wp_add_inline_script is available from Wordpress 4.5
            wp_add_inline_script($id, $script, $position);
        } else {
            echo '<script>' . $script . '</script>';
        }
    }
}

wp_register_script('sortable', plugins_url($jsDir . '/sortable.min.js', __FILE__), [], '1.9.0');
wp_enqueue_script('sortable');

wp_register_script('daspopup', plugins_url($jsDir . '/das-popup.js', __FILE__), [], $ver);
wp_enqueue_script('daspopup');

wp_register_script('escapehtml', plugins_url($jsDir . '/escapeHTML.js', __FILE__), [], $ver);
wp_enqueue_script('escapehtml');

$config = Config::getConfigForOptionsPage();

// selftest
wp_register_script('webpexpress_selftest', plugins_url($jsDir . '/self-test.js', __FILE__), ['escapehtml'], $ver);
wp_enqueue_script('webpexpress_selftest');

// Add converter, bulk convert and whitelist script, EXCEPT for "no conversion" mode
if (!(isset($config['operation-mode']) && ($config['operation-mode'] == 'no-conversion'))) {

    // Remove empty options arrays.
    // These cause trouble in json because they are encoded as [] rather than {}

    foreach ($config['converters'] as &$converter) {
        if (isset($converter['options']) && (count(array_keys($converter['options'])) == 0)) {
            unset($converter['options']);
        }
    }

    // Converters
    // ----------
    wp_register_script('converters', plugins_url($jsDir . '/converters.js', __FILE__), ['sortable', 'daspopup', 'escapehtml'], $ver);

    // PS: no escaping/sanitizing needed as json_encode always produces something safe
    webp_express_add_inline_script(
        'converters',
        'window.webpExpressPaths = ' . json_encode(Paths::getUrlsAndPathsForTheJavascript()) . ';',
        'before'
    );

    // PS: no escaping/sanitizing needed as json_encode always produces something safe
    webp_express_add_inline_script(
        'converters',
        'window.converters = ' . json_encode($config['converters']) . ';',
        'before'
    );
    wp_enqueue_script('converters');

    // Whitelist
    // ---------
    wp_register_script('whitelist', plugins_url($jsDir . '/whitelist.js', __FILE__), ['daspopup', 'escapehtml'], $ver);

    // PS: no escaping/sanitizing needed as json_encode always produces something safe
    webp_express_add_inline_script('whitelist', 'window.whitelist = ' . json_encode($config['web-service']['whitelist']) . ';', 'before');
    wp_enqueue_script('whitelist');

    // bulk convert
    wp_register_script('bulkconvert', plugins_url($jsDir . '/bulk-convert.js', __FILE__), ['escapehtml'], $ver);
    wp_enqueue_script('bulkconvert');

    // test convert
    wp_register_script('testconvert', plugins_url($jsDir . '/test-convert.js', __FILE__), ['escapehtml'], $ver);
    $canDisplayWebp = (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false ));

    /*
    AlterHTMLHelper::getWebPUrlInImageRoot(
        Paths::getPluginUrl() . '/webp-express',    // source url
        $baseId,
        Paths::getPluginUrl(),                    // base url
        Paths::getPluginDirAbs()                    // base dir
    );

    getRelUrlPath()*/

    webp_express_add_inline_script('testconvert', 'window.canDisplayWebp = ' . ($canDisplayWebp ? 'true' : 'false') . ';', 'before');
    wp_enqueue_script('testconvert');

    wp_register_script('image-comparison-slider', plugins_url($jsDir . '/image-comparison-slider.js', __FILE__), [], $ver);
    wp_enqueue_script('image-comparison-slider');


    // purge cache
    wp_register_script('purgecache', plugins_url($jsDir . '/purge-cache.js', __FILE__), [], $ver);
    wp_enqueue_script('purgecache');

    // purge log
    wp_register_script('purgelog', plugins_url($jsDir . '/purge-log.js', __FILE__), [], $ver);
    wp_enqueue_script('purgelog');

}

//wp_register_script('api_keys', plugins_url($jsDir . 'api-keys.js', __FILE__), ['daspopup'], '0.7.0-dev8');
//wp_enqueue_script('api_keys');

wp_register_script( 'page', plugins_url($jsDir . '/page.js', __FILE__), [], $ver);

// TODO: Add all vars needed to this array (whitelist, converters, etc)
$javascriptVars = [
    'ajax-nonces' => [
        'convert' => wp_create_nonce('webpexpress-ajax-convert-nonce'),
        'list-unconverted-files' => wp_create_nonce('webpexpress-ajax-list-unconverted-files-nonce'),
        'purge-cache' => wp_create_nonce('webpexpress-ajax-purge-cache-nonce'),
        'purge-log' => wp_create_nonce('webpexpress-ajax-purge-log-nonce'),
        'view-log' => wp_create_nonce('webpexpress-ajax-view-log-nonce'),
        'self-test' => wp_create_nonce('webpexpress-ajax-self-test-nonce'),
    ],
    'can-use-doc-root-for-structuring' => Paths::canUseDocRootForRelPaths()
];
webp_express_add_inline_script(
    'page',
    'window.webpExpress = ' . json_encode($javascriptVars, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK),
    'before'
);
wp_enqueue_script('page');


// Register styles
wp_register_style('webp-express-options-page-css', plugins_url('css/webp-express-options-page.css', __FILE__), null, $ver);
wp_enqueue_style('webp-express-options-page-css');

wp_register_style('test-convert-css', plugins_url('css/test-convert.css', __FILE__), null, $ver);
wp_enqueue_style('test-convert-css');

wp_register_style('das-popup-css', plugins_url('css/das-popup.css', __FILE__), null, $ver);
wp_enqueue_style('das-popup-css');

add_thickbox();
