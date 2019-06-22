<?php
namespace WebPExpress;

use \WebPConvert\WebPConvert;
use \WebPConvert\Serve\ServeConvertedWebP;
use \WebPExpress\ConvertHelperIndependent;
use \WebPExpress\Sanitize;
use \WebPExpress\ValidateException;

class WebPRealizer
{

    private static $docRoot;

    public static function exitWithError($msg) {
        header('X-WebP-Express-Error: ' . $msg, true);
        echo $msg;
        exit;
    }

    //echo $_SERVER["SERVER_SOFTWARE"]; exit;
    //stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') !== false


    /**
     *  Get environment variable set with mod_rewrite module
     *  Return false if the environment variable isn't found
     */
    static function getEnvPassedInRewriteRule($envName) {
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

    /**
     * Get absolute path to destination file.
     *
     * The path can be passed to this file from the .htaccess file / nginx config in various ways.
     *
     * @return string  Absolute path to destination (unsanitized! - call sanitizeAbsFilePath immidiately after calling this method)
     */
    static function getDestinationUnsanitized($docRoot) {

        // First check if it is in an environment variable - thats the safest way
        $destinationRel = self::getEnvPassedInRewriteRule('DESTINATIONREL');
        if ($destinationRel !== false) {
            return $docRoot . '/' . $destinationRel;
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

        // Then querystring (full path) - But only on Nginx (our Apache .htaccess rules never passes absolute url)
        if (stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') !== false) {
            if (isset($_GET['xdestination'])) {
                return substr($_GET['xdestination'], 1);         // No url decoding needed as $_GET is already decoded
            } elseif (isset($_GET['destination'])) {
                return $_GET['destination'];
            }
        }

        // Last resort is to use $_SERVER['REQUEST_URI'], well knowing that it does not give the
        // correct result in all setups (ie "folder method 1")
        $requestUriNoQS = explode('?', $_SERVER['REQUEST_URI'])[0];
        $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
        $dest = $docRoot . urldecode($requestUriNoQS);
        return $dest;
    }


    static function getWpContentRel() {
        // Passed in env variable?
        $wpContentDirRel = self::getEnvPassedInRewriteRule('WPCONTENT');
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

    /*
    static function registerAutoload()
    {
        define('WEBPEXPRESS_PLUGIN_DIR', __DIR__);

        // Autoload WebPExpress classes
        spl_autoload_register('webpexpress_autoload');
        function webpexpress_autoload($class) {
            if (strpos($class, 'WebPExpress\\') === 0) {
                require_once WEBPEXPRESS_PLUGIN_DIR . '/lib/classes/' . substr($class, 12) . '.php';
            }
        }
    }*/

    static function process() {

        include_once "../lib/classes/ConvertHelperIndependent.php";
        include_once __DIR__ . '/../lib/classes/Sanitize.php';
        include_once __DIR__ . '/../lib/classes/Validate.php';
        include_once __DIR__ . '/../lib/classes/ValidateException.php';

        // Validate!
        // ----------

        try {

            // Validate DOCUMENT_ROOT
            // ----------------------
            $validating = 'DOCUMENT_ROOT';
            $realPathResult = realpath(Sanitize::removeNUL($_SERVER["DOCUMENT_ROOT"]));
            if ($realPathResult === false) {
                throw new ValidateException('Cannot find document root');
            }
            $docRoot = rtrim($realPathResult, '/');
            Validate::absPathLooksSaneExistsAndIsDir($docRoot);
            $docRoot = $docRoot;


            // Validate WebP Express content dir
            // ---------------------------------
            $validating = 'WebP Express content dir';
            $webExpressContentDirAbs = ConvertHelperIndependent::sanitizeAbsFilePath(
                $docRoot . '/' . self::getWpContentRel() . '/webp-express'
            );
            Validate::absPathLooksSaneExistsAndIsDir($webExpressContentDirAbs);


            // Validate config file name
            // ---------------------------------
            $validating = 'config file';
            $configFilename = $webExpressContentDirAbs . '/config/wod-options.json';
            Validate::absPathLooksSaneExistsAndIsFile($configFilename);


            // Validate config file
            // --------------------
            $configLoadResult = file_get_contents($configFilename);
            if ($configLoadResult === false) {
                throw new ValidateException('Cannot open config file');
            }
            Validate::isJSONObject($configLoadResult);
            $json = $configLoadResult;
            $options = json_decode($json, true);
            $wodOptions = $options['wod'];
            $serveOptions = $options['webp-convert'];
            $convertOptions = &$serveOptions['convert'];
            //echo '<pre>' . print_r($wodOptions, true) . '</pre>'; exit;


            // Validate that WebPExpress was configured to redirect to this conversion script
            // ------------------------------------------------------------------------------
            $validating = 'settings';
            if (!isset($wodOptions['enable-redirection-to-webp-realizer']) || ($wodOptions['enable-redirection-to-webp-realizer'] === false)) {
                throw new ValidateException('Redirection to conversion script is not enabled');
            }


            // Validate destination (the image that was requested, but has not been converted yet)
            // ------------------------------------------------------------------------------------
            $validating = 'destination path';
            $destination = Sanitize::removeNUL(self::getDestinationUnsanitized($docRoot));
            Validate::absPathLooksSane($destination);
            //echo $destination; exit;


            // Validate source path
            // --------------------------------------------
            $validating = 'source path';
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
                die();
                //echo 'destination requested:<br><i>' . $destination . '</i>';
            }
            Validate::absPathLooksSaneExistsAndIsFile($source);
            //echo $source; exit;

        } catch (ValidateException $e) {
            self::exitWithError('failed validating ' . $validating . ': '. $e->getMessage());
        }

        // Change a cwebp default option
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

        /*
        if ($wodOptions['forward-query-string']) {
            if (isset($_GET['debug'])) {
                $serveOptions['show-report'] = true;
            }
            if (isset($_GET['reconvert'])) {
                $serveOptions['reconvert'] = true;
            }
        }*/

        $serveOptions['add-vary-header'] = false;
        $serveOptions['fail'] = '404';
        $serveOptions['fail-when-fail-fails'] = '404';
        $serveOptions['serve-image']['headers']['vary-accept'] = false;

        /*
        function aboutToServeImageCallBack($servingWhat, $whyServingThis, $obj) {
            // Redirect to same location.
            header('Location: ?fresh' , 302);
            return false;   // tell webp-convert not to serve!
        }
        */

        //echo '<pre>' . print_r($serveOptions, true) . '</pre>'; exit;
        //$serveOptions['show-report'] = true;

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
