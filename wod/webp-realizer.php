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

function getDestinationRealPath($dest) {
    //echo $_SERVER["DOCUMENT_ROOT"] . '<br>' . $dest . '<br>';
    if (strpos($dest, $_SERVER["DOCUMENT_ROOT"]) === 0) {
        return realpath($_SERVER["DOCUMENT_ROOT"]) . substr($dest, strlen($_SERVER["DOCUMENT_ROOT"]));
    } else {
        return $dest;
    }
}

function getDestination($allowInQS, $allowInHeader) {
    // First check if it is in an environment variable - thats the safest way
    foreach ($_SERVER as $key => $item) {
        if (substr($key, -14) == 'REDIRECT_REQFN') {
            return getDestinationRealPath($item);
        }
    }

    if ($allowInHeader) {
        if (isset($_SERVER['HTTP_REQFN'])) {
            //echo 'dest:' . $_SERVER['HTTP_REQFN'];
            return getDestinationRealPath($_SERVER['HTTP_REQFN']);
        }
    }

    // Last resort is to use $_SERVER['REQUEST_URI'], well knowing that it does not give the
    // correct result in all setups (ie "folder method 1")
    $requestUriNoQS = explode('?', $_SERVER['REQUEST_URI'])[0];
    $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
    $dest = $docRoot . urldecode($requestUriNoQS);
    return $dest;
}

$docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
$wpContentDirRel = (isset($_GET['wp-content']) ? $_GET['wp-content'] : 'wp-content');
$webExpressContentDirRel = $wpContentDirRel . '/webp-express';
$webExpressContentDirAbs = $docRoot . '/' . $webExpressContentDirRel;
$configFilename = $webExpressContentDirAbs . '/config/wod-options.json';

$options = loadConfig($configFilename);

$allowInQS = !(isset($options['do-not-pass-source-in-query-string']) && $options['do-not-pass-source-in-query-string']);
$allowInHeader = true;  // todo: implement setting
$destination = getDestination($allowInQS, $allowInHeader);
//$destination = getDestination(false, false);

//echo $destination; exit;

// Try to find source in same folder.
// Return false on failure
function findSourceMingled() {
    global $options;
    global $destination;
    if (isset($options['destination-extension']) && ($options['destination-extension'] == 'append')) {
        $source =  preg_replace('/\\.(webp)$/', '', $destination);
    } else {
        $source =  preg_replace('/\\.webp$/', '.jpg', $destination);
        if (!@file_exists($source)) {
            $source =  preg_replace('/\\.webp$/', '.jpeg', $destination);
        }
        if (!@file_exists($source)) {
            $source =  preg_replace('/\\.webp$/', '.png', $destination);
        }
    }
    if (!@file_exists($source)) {
        return false;
    }
    return $source;
}

function findSourceSeparate() {
    global $options;
    global $destination;
    global $webExpressContentDirAbs;
    global $docRoot;

    $imageRoot = $webExpressContentDirAbs . '/webp-images';

    // Check if destination is residing inside "doc-root" folder
    if (strpos($destination, $imageRoot . '/doc-root/') === 0) {

        $imageRoot .= '/doc-root';
        // "Eat" the left part off the $destination parameter. $destination is for example:
        // "/var/www/webp-express-tests/we0/wp-content-moved/webp-express/webp-images/doc-root/wordpress/uploads-moved/2018/12/tegning5-300x265.jpg.webp"
        // We also eat the slash (+1)
        $sourceRel = substr($destination, strlen($imageRoot) + 1);

        $source = $docRoot . '/' . $sourceRel;
        $source =  preg_replace('/\\.(webp)$/', '', $source);
    } else {
        $imageRoot .= '/abs';
        $sourceRel = substr($destination, strlen($imageRoot) + 1);
        $source = $sourceRel;
        $source =  preg_replace('/\\.(webp)$/', '', $source);
    }

    if (!@file_exists($source)) {
        return false;
    }
    return $source;
}


$mingled = (isset($options['destination-folder']) && ($options['destination-folder'] == 'mingled'));

if ($mingled) {
    $source = findSourceMingled();
    if ($source === false) {
        $source = findSourceSeparate();
    }
} else {
    $source = findSourceSeparate();
}

if ($source === false) {
    header('X-WebP-Express-Error: webp-realizer.php could not find an existing jpg or png that corresponds to the webp requested', true);

    $protocol = isset($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : 'HTTP/1.0';
    header($protocol . " 404 Not Found");
    //echo '<p>webp-realizer.php could not find an existing jpg or png that corresponds to the webp requested!</p>';
    //echo 'destination requested:<br><i>' . $destination . '</i>';
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

$options['require-for-conversion'] = 'webp-on-demand-2.inc';
//$options['require-for-conversion'] = '../../../autoload.php';

$options['add-vary-header'] = false;
$options['fail'] = '404';
$options['critical-fail'] = '404';
//$options['show-report'] = true;

function aboutToServeImageCallBack($servingWhat, $whyServingThis, $obj) {
    // Redirect to same location.
    header('Location: ?fresh' , 302);
    return false;   // tell webp-convert not to serve!
}

$options['aboutToServeImageCallBack'] = 'aboutToServeImageCallBack';

include_once '../vendor/rosell-dk/webp-convert/build/webp-on-demand-1.inc';
WebPConvert::convertAndServe($source, $destination, $options);

//echo "<pre>source: $source \ndestination: $destination \n\noptions:" . print_r($options, true) . '</pre>'; exit;
