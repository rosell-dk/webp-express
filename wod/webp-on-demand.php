<?php

//echo 'display errors:' . ini_get('display_errors');
//exit;

//require 'webp-on-demand-1.inc';
require '../vendor/rosell-dk/webp-convert/build/webp-on-demand-1.inc';
//require '../vendor/autoload.php';

//print_r($_GET); exit;

use \WebPConvert\WebPConvert;

$docRoot = rtrim($_SERVER["DOCUMENT_ROOT"], '/');
$wpContentDirRel = (isset($_GET['wp-content']) ? $_GET['wp-content'] : 'wp-content');
$webExpressContentDirRel = $wpContentDirRel . '/webp-express';
$webExpressContentDirAbs = $docRoot . '/' . $webExpressContentDirRel;
$configFilename = $webExpressContentDirAbs . '/config/wod-options.json';

if (isset($_GET['source'])) {
    $source = $_GET['source'];
} elseif (isset($_GET['xsource'])) {
    $source = substr($_GET['xsource'], 1);
} else {
    $requestUriNoQS = explode('?', $_SERVER['REQUEST_URI'])[0];
    $source = $docRoot . $requestUriNoQS;
}

//echo $source; exit;

// Calculate destination
$imageRoot = $webExpressContentDirAbs . '/webp-images';

// Check if source is residing inside document root.
// (it is, if path starts with document root + '/')
if (substr($source, 0, strlen($docRoot) + 1) === $docRoot . '/') {

    // We store relative to document root.
    // "Eat" the left part off the source parameter which contains the document root.
    // and also eat the slash (+1)
    $sourceRel = substr($source, strlen($docRoot) + 1);
    $destination = $imageRoot . '/doc-root/' . $sourceRel . '.webp';
} else {
    // Source file is residing outside document root.
    // we must add complete path to structure
    $destination = $imageRoot . '/abs' . $source . '.webp';
}

// If we wanted webp images to be located in same folder, with ie ".jpg.webp" extension:
// $destination = $source . '.webp';

// If we wanted webp images to be located in same folder, with ".webp" extension:
// $destination = preg_replace('/\.(jpg|jpeg|png)$/', '.webp', $source);

//echo $destination; exit;

if (!file_exists($configFilename)) {
    header('X-WebP-Express-Error: Configuration file not found!', true);
    WebPConvert::convertAndServe($source, $destination, []);
    exit;
}




// TODO: Handle read error / json error
$handle = @fopen($configFilename, "r");
$json = fread($handle, filesize($configFilename));
fclose($handle);

$options = [];
$options = json_decode($json, true);
//echo '<pre>' . print_r($options, true) . '</pre>';
//exit;

$options['require-for-conversion'] = 'webp-on-demand-2.inc';
//$options['require-for-conversion'] = '../../../autoload.php';

foreach ($options['converters'] as &$converter) {
    if (isset($converter['converter'])) {
        $converterId = $converter['converter'];
    } else {
        $converterId = $converter;
    }
    if ($converterId == 'cwebp') {
        $converter['options']['rel-path-to-precompiled-binaries'] = '../src/Converters/Binaries';
    }
}

if ($options['forward-query-string']) {
    if (isset($_GET['debug'])) {
        $options['show-report'] = true;
    }
    if (isset($_GET['reconvert'])) {
        $options['reconvert'] = true;
    }
}

//echo '<pre>' . print_r($options, true) . '</pre>'; exit;
WebPConvert::convertAndServe($source, $destination, $options);
