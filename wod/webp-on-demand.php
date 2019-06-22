<?php
namespace WebPExpress;

use \WebPConvert\WebPConvert;
use \WebPConvert\Serve\ServeConvertedWebP;
use \WebPExpress\ConvertHelperIndependent;
use \WebPExpress\Sanitize;
use \WebPExpress\ValidateException;

class WebPOnDempand
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
     * Get absolute path to source file.
     *
     * The path can be passed to this file from the .htaccess file / nginx config in various ways.
     *
     * @return string  Absolute path to source (unsanitized! - call sanitizeAbsFilePath immidiately after calling this method)
     */
    static function getSourceUnsanitized($docRoot, $wodOptions) {

        // First check if it is in an environment variable - thats the safest way
        $source = self::getEnvPassedInRewriteRule('REQFN');
        if ($source !== false) {
            return $source;
        }

        // Then header
        if (isset($wodOptions['base-htaccess-on-these-capability-tests'])) {
            $capTests = $wodOptions['base-htaccess-on-these-capability-tests'];
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

        // Then querystring (full path) - But only on Nginx (our Apache .htaccess rules never passes absolute url)
        if (stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') !== false) {

            // On Nginx, we allow passing absolute path
            if (isset($_GET['xsource'])) {
                return substr($_GET['xsource'], 1);         // No url decoding needed as $_GET is already decoded
            } elseif (isset($_GET['source'])) {
                return $_GET['source'];
            }

        }

        // Last resort is to use $_SERVER['REQUEST_URI'], well knowing that it does not give the
        // correct result in all setups (ie "folder method 1")
        $requestUriNoQS = explode('?', $_SERVER['REQUEST_URI'])[0];
        //$docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
        $source = Sanitize::removeNUL($docRoot . urldecode($requestUriNoQS));
        if (@file_exists($source)) {
            return $source;
        }

        // No luck whatsoever!
        self::exitWithError('webp-on-demand.php was not passed any filename to convert');
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
            if (!isset($wodOptions['enable-redirection-to-converter']) || ($wodOptions['enable-redirection-to-converter'] === false)) {
                throw new ValidateException('Redirection to conversion script is not enabled');
            }


            // Validate source (the image to be converted)
            // --------------------------------------------
            $validating = 'source';
            $source = Sanitize::removeNUL(self::getSourceUnsanitized($docRoot, $options['wod']));
            Validate::absPathLooksSaneExistsAndIsFile($source);
            //echo $source; exit;


            // Validate destination path
            // --------------------------------------------
            $validating = 'destination path';
            $destination = ConvertHelperIndependent::getDestination(
                $source,
                $wodOptions['destination-folder'],
                $wodOptions['destination-extension'],
                $webExpressContentDirAbs,
                $docRoot . '/' . $wodOptions['paths']['uploadDirRel']
            );
            Validate::absPathLooksSane($destination);
            //echo $destination; exit;

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

        if (isset($wodOptions['success-response']) && ($wodOptions['success-response'] == 'original')) {
            $serveOptions['serve-original'] = true;
            $serveOptions['serve-image']['headers']['vary-accept'] = false;
        } else {
            $serveOptions['serve-image']['headers']['vary-accept'] = true;
        }

        //$serveOptions['show-report'] = true;

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
