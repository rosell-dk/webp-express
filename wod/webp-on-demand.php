<?php

namespace WebPExpress;

error_reporting(E_ALL);
ini_set('display_errors', 1);

use \WebPConvert\WebPConvert;
use \WebPConvert\Serve\ServeConvertedWebP;
use \WebPExpress\ConvertHelperIndependent;
use \WebPExpress\Sanitize;
use \WebPExpress\SanityCheck;
use \WebPExpress\SanityException;
use \WebPExpress\ValidateException;
use \WebPExpress\Validate;

class WebPOnDempand
{

    private static $docRoot;

    private static function exitWithError($msg) {
        header('X-WebP-Express-Error: ' . $msg, true);
        echo $msg;
        exit;
    }

    /**
     *  Get environment variable set with mod_rewrite module
     *  Return false if the environment variable isn't found
     */
    private static function getEnvPassedInRewriteRule($envName) {
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

    public static function process() {

        include_once __DIR__ . "/../lib/classes/ConvertHelperIndependent.php";
        include_once __DIR__ . '/../lib/classes/Sanitize.php';
        include_once __DIR__ . '/../lib/classes/SanityCheck.php';
        include_once __DIR__ . '/../lib/classes/SanityException.php';
        include_once __DIR__ . '/../lib/classes/Validate.php';
        include_once __DIR__ . '/../lib/classes/ValidateException.php';

        // Check input
        // --------------
        try {

            // Check DOCUMENT_ROOT
            // ----------------------
            $checking = 'DOCUMENT_ROOT';
            $docRoot = SanityCheck::absPath($_SERVER["DOCUMENT_ROOT"]);
            $docRoot = rtrim($docRoot, '/');
            $docRoot = SanityCheck::absPathExistsAndIsDir($docRoot);

            // Check wp-content
            // ----------------------

            // Passed in env variable?
            $wpContentDirRel = self::getEnvPassedInRewriteRule('WPCONTENT');
            if ($wpContentDirRel === false) {

                // Passed in QS?
                if (isset($_GET['wp-content'])) {
                    $wpContentDirRel = SanityCheck::pathWithoutDirectoryTraversal($_GET['wp-content']);
                } else {
                    // In case above fails, fall back to standard location
                    $wpContentDirRel = 'wp-content';
                }
            }

            // Check WebP Express content dir
            // ---------------------------------
            $checking = 'WebP Express content dir';
            $webExpressContentDirAbs = SanityCheck::absPathExistsAndIsDir($docRoot . '/' . $wpContentDirRel . '/webp-express');


            // Check config file name
            // ---------------------------------
            $checking = 'config file';
            $configFilename = SanityCheck::absPathExistsAndIsFile($webExpressContentDirAbs . '/config/wod-options.json');


            // Check config file
            // --------------------
            $configLoadResult = file_get_contents($configFilename);
            if ($configLoadResult === false) {
                throw new ValidateException('Cannot open config file');
            }
            $json = SanityCheck::isJSONObject($configLoadResult);

            $options = json_decode($json, true);
            $wodOptions = $options['wod'];
            $serveOptions = $options['webp-convert'];
            $convertOptions = &$serveOptions['convert'];
            //echo '<pre>' . print_r($wodOptions, true) . '</pre>'; exit;


            // Validate that WebPExpress was configured to redirect to this conversion script
            // ------------------------------------------------------------------------------
            $checking = 'settings';
            if (!isset($wodOptions['enable-redirection-to-converter']) || ($wodOptions['enable-redirection-to-converter'] === false)) {
                throw new ValidateException('Redirection to conversion script is not enabled');
            }


            // Check source (the image to be converted)
            // --------------------------------------------
            $checking = 'source';

            // Check if it is in an environment variable
            $source = self::getEnvPassedInRewriteRule('REQFN');
            if ($source !== false) {
                $checking = 'source (passed through env)';
                $source = SanityCheck::absPathExistsAndIsFile($source);
            } else {
                // Check if it is in header (but only if .htaccess was configured to send in header)
                if (isset($wodOptions['base-htaccess-on-these-capability-tests'])) {
                    $capTests = $wodOptions['base-htaccess-on-these-capability-tests'];
                    $passThroughHeaderDefinitelyUnavailable = ($capTests['passThroughHeaderWorking'] === false);
                    $passThrougEnvVarDefinitelyAvailable =($capTests['passThroughEnvWorking'] === true);
                    // This determines if .htaccess was configured to send in querystring
                    $headerMagicAddedInHtaccess = ((!$passThrougEnvVarDefinitelyAvailable) && (!$passThroughHeaderDefinitelyUnavailable));
                } else {
                    $headerMagicAddedInHtaccess = true;  // pretend its true
                }

                if ($headerMagicAddedInHtaccess && (isset($_SERVER['HTTP_REQFN']))) {
                    $checking = 'source (passed through request header)';
                    $source = SanityCheck::absPathExistsAndIsFile($_SERVER['HTTP_REQFN']);
                } else {
                    // Check querystring (relative path)
                    $srcRel = '';
                    if (isset($_GET['xsource-rel'])) {
                        $checking = 'source (passed as relative path, through querystring)';
                        $xsrcRel = SanityCheck::noControlChars($_GET['xsource-rel']);
                        $srcRel = SanityCheck::pathWithoutDirectoryTraversal(substr($xsrcRel, 1));
                        $source = SanityCheck::absPathExistsAndIsFile($docRoot . '/' . $srcRel);
                    } else {
                        // Then querystring (full path)
                        // - But only on Nginx (our Apache .htaccess rules never passes absolute url)
                        if (
                            (stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') !== false) &&
                            (isset($_GET['source']) || isset($_GET['xsource']))
                        ) {
                            $checking = 'source (passed as absolute path on nginx)';
                            if (isset($_GET['source'])) {
                                $source = SanityCheck::absPathExistsAndIsFile($_GET['source']);
                            } else {
                                $xsrc = SanityCheck::noControlChars($_GET['xsource']);
                                $source = SanityCheck::absPathExistsAndIsFile(substr($xsrc, 1));
                            }
                        } else {
                            // Last resort is to use $_SERVER['REQUEST_URI'], well knowing that it does not give the
                            // correct result in all setups (ie "folder method 1")
                            $checking = 'source (retrieved by the request_uri server var)';
                            $srcRel = SanityCheck::pathWithoutDirectoryTraversal(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
                            $source = SanityCheck::absPathExistsAndIsFile($docRoot . $srcRel);
                        }
                    }
                }
            }

            // Make sure it is in doc root
            $source = SanityCheck::absPathIsInDocRoot($source);

            // Check destination path
            // --------------------------------------------
            $checking = 'destination path';
            $destination = ConvertHelperIndependent::getDestination(
                $source,
                $wodOptions['destination-folder'],
                $wodOptions['destination-extension'],
                $webExpressContentDirAbs,
                $docRoot . '/' . $wodOptions['paths']['uploadDirRel']
            );
            //echo 'dest:' . $destination; exit;
            $destination = SanityCheck::absPathIsInDocRoot($destination);
            $destination = SanityCheck::pregMatch('#\.webp$#', $destination, 'Does not end with .webp');

        } catch (SanityException $e) {
            self::exitWithError('Sanity check failed for ' . $checking . ': '. $e->getMessage());
        } catch (ValidateException $e) {
            self::exitWithError('Validation failed for ' . $checking . ': '. $e->getMessage());
        }

        // Done with sanitizing, lets get to work!
        // ---------------------------------------
        if (isset($wodOptions['success-response']) && ($wodOptions['success-response'] == 'original')) {
            $serveOptions['serve-original'] = true;
            $serveOptions['serve-image']['headers']['vary-accept'] = false;
        } else {
            $serveOptions['serve-image']['headers']['vary-accept'] = true;
        }
//echo $source . '<br>' . $destination; exit;

        ConvertHelperIndependent::serveConverted(
            $source,
            $destination,
            $serveOptions,
            $webExpressContentDirAbs . '/log',
            'Conversion triggered with the conversion script (wod/webp-on-demand.php)'
        );
    }
}

// Protect against directly accessing webp-on-demand.php
// Only protect on Apache. We know for sure that the method is not reliable on nginx. We have not tested on litespeed yet, so we dare not.
if (stripos($_SERVER["SERVER_SOFTWARE"], 'apache') !== false && stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') === false) {
    if (strpos($_SERVER['REQUEST_URI'], 'webp-on-demand.php') !== false) {
        WebPOnDempand::exitWithError('It seems you are visiting this file (plugins/webp-express/wod/webp-on-demand.php) directly. We do not allow this.');
        exit;
    }
}

WebPOnDempand::process();
