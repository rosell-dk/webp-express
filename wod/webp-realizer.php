<?php
// https://www.askapache.com/htaccess/crazy-advanced-mod_rewrite-tutorial/#Decoding_Mod_Rewrite_Variables

ini_set('display_errors', 1);
error_reporting(E_ALL);

//echo 'display errors:' . ini_get('display_errors');
//exit;

//require 'webp-on-demand-1.inc';
//require '../vendor/autoload.php';

use \WebPConvert\WebPConvert;
use \WebPConvert\ServeExistingOrHandOver;

include_once "../lib/classes/ConvertHelperIndependent.php";
use \WebPExpress\ConvertHelperIndependent;

function exitWithError($msg) {
    header('X-WebP-Express-Error: ' . $msg, true);
    echo $msg;
    exit;
}

// Protect against directly accessing webp-on-demand.php
// Only protect on Apache. We know for sure that the method is not reliable on nginx. We have not tested on litespeed yet, so we dare not.
if (stripos($_SERVER["SERVER_SOFTWARE"], 'apache') !== false && stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') === false) {
    if (strpos($_SERVER['REQUEST_URI'], 'webp-realizer.php') !== false) {
        exitWithError('It seems you are visiting this file (plugins/webp-express/wod/webp-realizer.php) directly. We do not allow this.');
        exit;
    }
}

/**
 *  Get environment variable set with mod_rewrite module
 *  Return false if the environment variable isn't found
 */
function getEnvPassedInRewriteRule($envName) {
    // Envirenment variables passed through the REWRITE module have "REWRITE_" as a prefix (in Apache, not Litespeed, if I recall correctly)
    //  Multiple iterations causes multiple REWRITE_ prefixes, and we get many environment variables set.
    // Multiple iterations causes multiple REWRITE_ prefixes, and we get many environment variables set.
    // We simply look for an environment variable that ends with what we are looking for.
    // (so make sure to make it unique)
    $len = strlen($envName);
    foreach ($_SERVER as $key => $item) {
        if (substr($key, -$len) == $envName) {
            return $item;
        }
    }
    return false;
}

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

/*
function getDestinationRealPath($dest) {
    //echo $_SERVER["DOCUMENT_ROOT"] . '<br>' . $dest . '<br>';
    if (strpos($dest, $_SERVER["DOCUMENT_ROOT"]) === 0) {
        return realpath($_SERVER["DOCUMENT_ROOT"]) . substr($dest, strlen($_SERVER["DOCUMENT_ROOT"]));
    } else {
        return $dest;
    }
}*/

function getDestination() {
    global $docRoot;

    // First check if it is in an environment variable - thats the safest way
    $destinationRel = getEnvPassedInRewriteRule('DESTINATIONREL');
    if ($destinationRel !== false) {
        return $docRoot . '/' . $destinationRel;
    }

    // Next, check querystring (full path)
    if (isset($_GET['xdestination'])) {
        return substr($_GET['xdestination'], 1);         // No url decoding needed as $_GET is already decoded
    } elseif (isset($_GET['destination'])) {
        return $_GET['destination'];
    }

    // Next, check querystring (relative path)
    $destinationRel = '';
    if (isset($_GET['xdestination-rel'])) {
        $destinationRel = substr($_GET['xdestination-rel'], 1);
    } elseif (isset($_GET['destination-rel'])) {
        $destinationRel = $_GET['destination-rel'];
    }
    if ($destinationRel != '') {
        if (isset($_GET['source-rel-filter'])) {
            if ($_GET['source-rel-filter'] == 'discard-parts-before-wp-content') {
                $parts = explode('/', $destinationRel);
                $wp_content = isset($_GET['wp-content']) ? $_GET['wp-content'] : 'wp-content';

                if (in_array($wp_content, $parts)) {
                    foreach($parts as $index => $part) {
                        if($part !== $wp_content) {
                            unset($parts[$index]);
                        } else {
                            break;
                        }
                    }
                    $destinationRel = implode('/', $parts);
                }
            }
        }
        return $docRoot . '/' . $destinationRel;
    }

    // Last resort is to use $_SERVER['REQUEST_URI'], well knowing that it does not give the
    // correct result in all setups (ie "folder method 1")
    $requestUriNoQS = explode('?', $_SERVER['REQUEST_URI'])[0];
    $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
    $dest = $docRoot . urldecode($requestUriNoQS);
    return $dest;
}

function getWpContentRel() {
    // Passed in env variable?
    $wpContentDirRel = getEnvPassedInRewriteRule('WPCONTENT');
    if ($wpContentDirRel !== false) {
        return $wpContentDirRel;
    }

    // Passed in QS?
    if (isset($_GET['wp-content'])) {
        return $_GET['wp-content'];
    }

    // In case above fails, fall back to standard location
    return 'wp-content';
}

$docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
$webExpressContentDirAbs = $docRoot . '/' . getWpContentRel() . '/webp-express';
$options = loadConfig($webExpressContentDirAbs . '/config/wod-options.json');
$wodOptions = $options['wod'];
$serveOptions = $options['webp-convert'];
$convertOptions = &$serveOptions['convert'];

$destination = getDestination();

//echo 'destination: ' . $destination; exit;

$source = ConvertHelperIndependent::findSource(
    $destination,
    $wodOptions['destination-folder'],
    $wodOptions['destination-extension'],
    $webExpressContentDirAbs
);

if ($source === false) {
    header('X-WebP-Express-Error: webp-realizer.php could not find an existing jpg or png that corresponds to the webp requested', true);

    $protocol = isset($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : 'HTTP/1.0';
    header($protocol . " 404 Not Found");
    //echo '<p>webp-realizer.php could not find an existing jpg or png that corresponds to the webp requested!</p>';
    //echo 'destination requested:<br><i>' . $destination . '</i>';
}

foreach ($convertOptions['converters'] as &$converter) {
    if (isset($converter['converter'])) {
        $converterId = $converter['converter'];
    } else {
        $converterId = $converter;
    }
    if ($converterId == 'cwebp') {
        $converter['options']['rel-path-to-precompiled-binaries'] = '../src/Converters/Binaries';
    }
}

if ($wodOptions['forward-query-string']) {
    if (isset($_GET['debug'])) {
        $serveOptions['show-report'] = true;
    }
    if (isset($_GET['reconvert'])) {
        $options['reconvert'] = true;
    }
}

$serveOptions['add-vary-header'] = false;
$serveOptions['fail'] = '404';
$serveOptions['fail-when-fail-fails'] = '404';
//$options['show-report'] = true;
$serveOptions['serve-image']['headers']['vary-accept'] = false;

/*
function aboutToServeImageCallBack($servingWhat, $whyServingThis, $obj) {
    // Redirect to same location.
    header('Location: ?fresh' , 302);
    return false;   // tell webp-convert not to serve!
}
*/

ConvertHelperIndependent::serveConverted(
    $source,
    $destination,
    $serveOptions,
    $webExpressContentDirAbs . '/log',
    'Conversion triggered with the conversion script (wod/webp-realizer.php)'
);



//echo "<pre>source: $source \ndestination: $destination \n\noptions:" . print_r($options, true) . '</pre>'; exit;
