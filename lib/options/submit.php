<?php

include_once __DIR__ . '/../classes/Config.php';
use \WebPExpress\Config;

// https://premium.wpmudev.org/blog/handling-form-submissions/
// checkout https://codex.wordpress.org/Function_Reference/sanitize_meta

$config = [
    'fail' => sanitize_text_field($_POST['fail']),
    'max-quality' => sanitize_text_field($_POST['max-quality']),
    'image-types' => sanitize_text_field($_POST['image-types']),
    'converters' => json_decode(wp_unslash($_POST['converters']), true), // holy moly! - https://stackoverflow.com/questions/2496455/why-are-post-variables-getting-escaped-in-php
    'forward-query-string' => true
];

// remove id's
foreach ($config['converters'] as &$converter) {
    unset ($converter['id']);
}

// TODO: Use Config::saveConfigurationAndHTAccess instead (which must call HTACcess::saveRules($config)
Config::saveConfigurationAndHTAccessFilesWithMessages($config, 'submit');

wp_redirect( $_SERVER['HTTP_REFERER']);

exit();
