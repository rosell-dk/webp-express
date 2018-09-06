<?php

require 'webp-on-demand.inc';

use \WebPOnDemand\WebPOnDemand;

$options = [];

$configPath = $_GET['config-path'];
$configPathAbs = $_SERVER['DOCUMENT_ROOT'] . '/' . $_GET['config-path'];
$configFilename = $configPathAbs . '/config.json';
$handle = @fopen($configFilename, "r");
$json = fread($handle, filesize($configFilename));
fclose($handle);

$options = json_decode($json, true);
//print_r($options);

$options['require-for-conversion'] = 'webp-convert-and-serve.inc';

$source = $_GET['source'];

// Calculate destination
$applicationRoot = $_SERVER["DOCUMENT_ROOT"];
$imageRoot = $configPathAbs . '/webp-images';

if (substr($source, 0, strlen($applicationRoot)) === $applicationRoot) {
    // Source file is residing inside document root.
    // We can store relative to that.
    $sourceRel = substr($source, strlen($applicationRoot));
    $destination = $imageRoot . '/doc-root' . $sourceRel . '.webp';
} else {
    // Source file is residing outside document root.
    // we must add complete path to structure
    $destination = $imageRoot . '/abs' . $source . '.webp';
}
//$destination = $imageRoot . $source . '.webp';


//echo $source . '<br>';
//echo $destination . '<br>';
//echo $sourceRel;
WebPOnDemand::serve($source, $destination, $options);
