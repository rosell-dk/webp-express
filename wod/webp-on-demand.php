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

include_once "../lib/classes/ConvertHelperIndependent.php";
use \WebPExpress\ConvertHelperIndependent;

function exitWithError($msg) {
    header('X-WebP-Express-Error: ' . $msg, true);
    echo $msg;
    exit;
}

//echo $_SERVER["SERVER_SOFTWARE"]; exit;
//stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') !== false

// Protect against directly accessing webp-on-demand.php
// Only protect on Apache. We know for sure that the method is not reliable on nginx. We have not tested on litespeed yet, so we dare not.
if (stripos($_SERVER["SERVER_SOFTWARE"], 'apache') !== false && stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') === false) {
    if (strpos($_SERVER['REQUEST_URI'], 'webp-on-demand.php') !== false) {
        exitWithError('It seems you are visiting this file (plugins/webp-express/wod/webp-on-demand.php) directly. We do not allow this.');
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

function getSource() {
    global $options;
    global $docRoot;

    // First check if it is in an environment variable - thats the safest way
    $source = getEnvPassedInRewriteRule('REQFN');
    if ($source !== false) {
        return $source;
    }

    // Then header
    if (isset($options['base-htaccess-on-these-capability-tests'])) {
        $capTests = $options['base-htaccess-on-these-capability-tests'];
        $passThroughHeaderDefinitelyUnavailable = ($capTests['passThroughHeaderWorking'] === false);
        $passThrougEnvVarDefinitelyAvailable =($capTests['passThroughEnvWorking'] === true);
    } else {
        $passThroughHeaderDefinitelyUnavailable = false;
        $passThrougEnvVarDefinitelyAvailable = false;
    }
    if ((!$passThrougEnvVarDefinitelyAvailable) && (!$passThroughHeaderDefinitelyUnavailable)) {
        if (isset($_SERVER['HTTP_REQFN'])) {
            return $_SERVER['HTTP_REQFN'];
        }
    }

    // Then querystring (full path)
    if (isset($_GET['xsource'])) {
        return substr($_GET['xsource'], 1);         // No url decoding needed as $_GET is already decoded
    } elseif (isset($_GET['source'])) {
        return $_GET['source'];
    }

    // Then querystring (relative path)
    $srcRel = '';
    if (isset($_GET['xsource-rel'])) {
        $srcRel = substr($_GET['xsource-rel'], 1);
    } elseif (isset($_GET['source-rel'])) {
        $srcRel = $_GET['source-rel'];
    }
    if ($srcRel != '') {
        if (isset($_GET['source-rel-filter'])) {
            if ($_GET['source-rel-filter'] == 'discard-parts-before-wp-content') {
                $parts = explode('/', $srcRel);
                $wp_content = isset($_GET['wp-content']) ? $_GET['wp-content'] : 'wp-content';

                if (in_array($wp_content, $parts)) {
                    foreach($parts as $index => $part) {
                        if($part !== $wp_content) {
                            unset($parts[$index]);
                        } else {
                            break;
                        }
                    }
                    $srcRel = implode('/', $parts);
                }
            }
        }
        return $docRoot . '/' . $srcRel;
    }

    // Last resort is to use $_SERVER['REQUEST_URI'], well knowing that it does not give the
    // correct result in all setups (ie "folder method 1")
    $requestUriNoQS = explode('?', $_SERVER['REQUEST_URI'])[0];
    //$docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
    $source = $docRoot . urldecode($requestUriNoQS);
    if (file_exists($source)) {
        return $source;
    }

    // No luck whatsoever!
    exitWithError('webp-on-demand.php was not passed any filename to convert');
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

$source = getSource();
//$source = getSource(false, false);

//echo $source; exit;

if (!file_exists($source)) {
    header('X-WebP-Express-Error: Source file not found!', true);
    echo 'Source file not found!';
    exit;
}

$destination = ConvertHelperIndependent::getDestination(
    $source,
    $options['destination-folder'],
    $options['destination-extension'],
    $webExpressContentDirAbs,
    $docRoot . '/' . $options['paths']['uploadDirRel']
);

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
