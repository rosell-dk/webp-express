<?php

//echo 'display errors:' . ini_get('display_errors');
//exit;

//require 'webp-on-demand-1.inc';
require '../vendor/rosell-dk/webp-convert/build/webp-on-demand-1.inc';
//require '../vendor/autoload.php';

use \WebPConvert\WebPConvert;

$options = [];

$contentDirAbs = $_SERVER['DOCUMENT_ROOT'] . '/' . $_GET['wp-content'] . '/webp-express';
$configFilename = $contentDirAbs . '/config/wod-options.json';
$handle = @fopen($configFilename, "r");
$json = fread($handle, filesize($configFilename));
fclose($handle);

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

if (isset($_GET['source'])) {
    $source = $_GET['source'];
} elseif (isset($_GET['xsource'])) {
    $source = substr($_GET['xsource'], 1);
}

// Calculate destination
$docRoot = rtrim($_SERVER["DOCUMENT_ROOT"], '/');
$imageRoot = $contentDirAbs . '/webp-images';

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


WebPConvert::convertAndServe($source, $destination, $options);
