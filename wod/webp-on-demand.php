<?php
// https://www.askapache.com/htaccess/crazy-advanced-mod_rewrite-tutorial/#Decoding_Mod_Rewrite_Variables

ini_set('display_errors', 1);
error_reporting(E_ALL);

//echo 'display errors:' . ini_get('display_errors');
//exit;

//require 'webp-on-demand-1.inc';
//require '../vendor/autoload.php';

//print_r($_GET); exit;

use \WebPConvert\WebPConvert;
use \WebPConvert\ServeExistingOrHandOver;

function loadConfig($configFilename) {
    if (!file_exists($configFilename)) {
        header('X-WebP-Express-Error: Configuration file not found!', true);
        echo 'Configuration file not found!';
        //WebPConvert::convertAndServe($source, $destination, []);
        exit;
    }

    // TODO: Handle read error / json error
    $handle = @fopen($configFilename, "r");
    $json = fread($handle, filesize($configFilename));
    fclose($handle);
    return json_decode($json, true);
}

function getSource($allowInQS, $allowInHeader) {
    //echo '<pre>' . print_r($_SERVER, true) . '</pre>'; exit;

    // First check if it is in an environment variable - thats the safest way
    foreach ($_SERVER as $key => $item) {
        if (substr($key, -14) == 'REDIRECT_REQFN') {
            return $item;
        }
    }

    if ($allowInHeader) {
        if (isset($_SERVER['HTTP_REQFN'])) {
            return $_SERVER['HTTP_REQFN'];
        }
    }

    if ($allowInQS) {
        if (isset($_GET['source'])) {
            return $_GET['source'];     // No url decoding needed as $_GET is already decoded
        } elseif (isset($_GET['xsource'])) {
            return substr($_GET['xsource'], 1);
        }
    }

    // Last resort is to use $_SERVER['REQUEST_URI'], well knowing that it does not give the
    // correct result in all setups (ie "folder method 1")
    $requestUriNoQS = explode('?', $_SERVER['REQUEST_URI'])[0];
    $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
    $source = $docRoot . urldecode($requestUriNoQS);
    if (file_exists($source)) {
        return $source;
    }

    header('X-WebP-Express-Error: None of the available methods for locating source file works', true);
    echo 'None of the available methods for locating source file works!';
    if (!$allowInHeader) {
        echo '<br>Have you tried allowing source to be passed as a request header?';
    }
    if (!$allowInQS) {
        echo '<br>Have you tried allowing source to be passed in querystring?';
    }
    exit;
}

$docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
$wpContentDirRel = (isset($_GET['wp-content']) ? $_GET['wp-content'] : 'wp-content');
$webExpressContentDirRel = $wpContentDirRel . '/webp-express';
$webExpressContentDirAbs = $docRoot . '/' . $webExpressContentDirRel;
$configFilename = $webExpressContentDirAbs . '/config/wod-options.json';

$options = loadConfig($configFilename);

$allowInQS = !(isset($options['do-not-pass-source-in-query-string']) && $options['do-not-pass-source-in-query-string']);
$allowInHeader = true;  // todo: implement setting
$source = getSource($allowInQS, $allowInHeader);
//$source = getSource(false, false);

if (!file_exists($source)) {
    header('X-WebP-Express-Error: Source file not found!', true);
    echo 'Source file not found!';
    exit;
}

//echo $source; exit;



// Calculate $destination
// ----------------------
$mingled = (isset($options['destination-folder']) && ($options['destination-folder'] == 'mingled'));
$storeMingled = false;
if ($mingled) {
    // Test if source folder is writable.
    // We will only store "mingled", if it is.
    $sourceFolder = preg_replace('/\\/[^\\/]*$/', '', $source);
    if (@is_writable($sourceFolder) && @is_executable($sourceFolder)) {
        $storeMingled = true;
    } else {
        header('X-WebP-Express-Notice: Cannot save file in same directory as source, falling back to separate folder', true);
        if (isset($_GET['debug'])) {
            echo 'Notice: Cannot save file in same directory as source, falling back to separate folder<br><br>';
        }
    }
}
if ($storeMingled) {
    if (isset($options['destination-extension']) && ($options['destination-extension'] == 'append')) {
        $destination = $source . '.webp';
    } else {
        $destination = preg_replace('/\\.(jpe?g|png)$/', '', $source) . '.webp';
    }
} else {

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
}





//echo $destination; exit;


//echo '<pre>' . print_r($options, true) . '</pre>';
//exit;

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

function aboutToServeImageCallBack($servingWhat, $whyServingThis, $obj) {
    return false;   // do not serve!
}

$options['require-for-conversion'] = 'webp-on-demand-2.inc';
//$options['require-for-conversion'] = '../../../autoload.php';

include_once '../vendor/rosell-dk/webp-convert/build/webp-on-demand-1.inc';

if (isset($options['success-response']) && ($options['success-response'] == 'original')) {

    /*
    We want to convert, but serve the original. This is a bit unusual and requires a little tweaking

    First, we use the "decideWhatToServe" method of WebPConvert to find out if we should convert or not

    If result is "destination", it means there is a useful webp image at the destination (no reason to convert)
    If result is "source", it means that source is lighter than existing webp image (no reason to convert)
    If result is "fresh-conversion", it means we should convert
    */
    $server = new \WebPConvert\Serve\ServeExistingOrHandOver($source, $destination, $options);
    $server->decideWhatToServe();

    if ($server->whatToServe == 'fresh-conversion') {
        // Conversion time.
        // To prevent the serving, we use the callback
        $options['aboutToServeImageCallBack'] = 'aboutToServeImageCallBack';
        WebPConvert::convertAndServe($source, $destination, $options);

        // remove the callback, we are going for another round
        unset($options['aboutToServeImageCallBack']);
        unset($options['require-for-conversion']);
    }

    // Serve time
    $options['serve-original'] = true;      // Serve original
    $options['add-vary-header'] = false;

    WebPConvert::convertAndServe($source, $destination, $options);

} else {
    WebPConvert::convertAndServe($source, $destination, $options);
}

//echo "<pre>source: $source \ndestination: $destination \n\noptions:" . print_r($options, true) . '</pre>'; exit;
