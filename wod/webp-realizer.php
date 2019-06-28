<?php
namespace WebPExpress;

use \WebPConvert\WebPConvert;
use \WebPConvert\Serve\ServeConvertedWebP;
use \WebPExpress\ConvertHelperIndependent;
use \WebPExpress\Sanitize;
use \WebPExpress\SanityCheck;
use \WebPExpress\SanityException;
use \WebPExpress\ValidateException;
use \WebPExpress\Validate;

class WebPRealizer
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

            // Use realpath to expand symbolic links and check if it exists
            $docRoot = realpath($docRoot);
            if ($docRoot === false) {
                throw new SanityException('Cannot find document root');
            }
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
            $configFilename = SanityCheck::absPathExistsAndIsFileInDocRoot($webExpressContentDirAbs . '/config/wod-options.json');


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
            if (!isset($wodOptions['enable-redirection-to-webp-realizer']) || ($wodOptions['enable-redirection-to-webp-realizer'] === false)) {
                throw new ValidateException('Redirection to webp realizer is not enabled');
            }


            // Check destination (the image that was requested, but has not been converted yet)
            // ------------------------------------------------------------------------------------
            $checking = 'destination path';

            // Check if it is in an environment variable
            $destRel = self::getEnvPassedInRewriteRule('DESTINATIONREL');
            if ($destRel !== false) {
                $destination = SanityCheck::absPath($docRoot . '/' . $destRel);
            } else {
                // Check querystring (relative path)
                if (isset($_GET['xdestination-rel'])) {
                    $xdestRel = SanityCheck::noControlChars($_GET['xdestination-rel']);
                    $destRel = SanityCheck::pathWithoutDirectoryTraversal(substr($xdestRel, 1));
                    $destination = SanityCheck::absPath($docRoot . '/' . $destRel);
                } else {

                    // Then querystring (full path)
                    // - But only on Nginx (our Apache .htaccess rules never passes absolute url)
                    if (
                        (stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') !== false) &&
                        (isset($_GET['destination']) || isset($_GET['xdestination']))
                    ) {
                        if (isset($_GET['destination'])) {
                            $destination = SanityCheck::absPathIsInDocRoot($_GET['destination']);
                        } else {
                            $xdest = SanityCheck::noControlChars($_GET['xdestination']);
                            $destination = SanityCheck::absPathIsInDocRoot(substr($xdest, 1));
                        }
                    } else {
                        // Last resort is to use $_SERVER['REQUEST_URI'], well knowing that it does not give the
                        // correct result in all setups (ie "folder method 1")
                        $destRel = SanityCheck::pathWithoutDirectoryTraversal(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
                        $destination = SanityCheck::absPath($docRoot . $destRel);
                    }
                }
            }

            $destination = SanityCheck::pregMatch('#\.webp$#', $destination, 'Does not end with .webp');
            $destination = SanityCheck::absPathIsInDocRoot($destination);


            // Validate source path
            // --------------------------------------------
            $checking = 'source path';
            $source = ConvertHelperIndependent::findSource(
                $destination,
                $wodOptions['destination-folder'],
                $wodOptions['destination-extension'],
                $webExpressContentDirAbs
            );

            if ($source === false) {
                header('X-WebP-Express-Error: webp-realizer.php could not find an existing jpg/png that corresponds to the webp requested', true);

                $protocol = isset($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : 'HTTP/1.0';
                header($protocol . " 404 Not Found");
                die();
                //echo 'destination requested:<br><i>' . $destination . '</i>';
            }
            $source = SanityCheck::absPathExistsAndIsFileInDocRoot($source);

        } catch (SanityException $e) {
            self::exitWithError('Sanity check failed for ' . $checking . ': '. $e->getMessage());
        } catch (ValidateException $e) {
            self::exitWithError('Validation failed for ' . $checking . ': '. $e->getMessage());
        }


        // Done with sanitizing, lets get to work!
        // ---------------------------------------
        $serveOptions['add-vary-header'] = false;
        $serveOptions['fail'] = '404';
        $serveOptions['fail-when-fail-fails'] = '404';
        $serveOptions['serve-image']['headers']['vary-accept'] = false;

        ConvertHelperIndependent::serveConverted(
            $source,
            $destination,
            $serveOptions,
            $webExpressContentDirAbs . '/log',
            'Conversion triggered with the conversion script (wod/webp-realizer.php)'
        );
    }
}

// Protect against directly accessing webp-on-demand.php
// Only protect on Apache. We know for sure that the method is not reliable on nginx. We have not tested on litespeed yet, so we dare not.
if (stripos($_SERVER["SERVER_SOFTWARE"], 'apache') !== false && stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') === false) {
    if (strpos($_SERVER['REQUEST_URI'], 'webp-realizer.php') !== false) {
        WebPRealizer::exitWithError('It seems you are visiting this file (plugins/webp-express/wod/webp-realizer.php) directly. We do not allow this.');
        exit;
    }
}

WebPRealizer::process();
