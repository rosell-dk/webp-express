<?php

use \WebPExpress\WebPRealizer;

// Protect against directly accessing webp-on-demand.php
// Only protect on Apache. We know for sure that the method is not reliable on nginx. We have not tested on litespeed yet, so we dare not.
if (stripos($_SERVER["SERVER_SOFTWARE"], 'apache') !== false && stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') === false) {
    if (strpos($_SERVER['REQUEST_URI'], 'webp-realizer.php') !== false) {
        WebPOnDemand::exitWithError(
            'It seems you are visiting this file (plugins/webp-express/wod/webp-realizer.php) directly. We do not allow this.'
        );
        exit;
    }
}

define('WOD_DIR', __DIR__);

function webpexpress_autoloader($class) {
    if (strpos($class, 'WebPExpress\\') === 0) {
        require_once WOD_DIR . '/../lib/classes/' . substr($class, 12) . '.php';
    }
}
spl_autoload_register('webpexpress_autoloader');

WebPRealizer::processRequest();
