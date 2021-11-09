<?php
/*
This class is used by wod/webp-on-demand.php, which does not do a Wordpress bootstrap, but does register an autoloader for
the WebPExpress classes.

Calling Wordpress functions will FAIL. Make sure not to do that in either this class or the helpers.
*/
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

namespace WebPExpress;

use \WebPConvert\Convert\Converters\Ewww;

use \WebPExpress\ImageRoots;
use \WebPExpress\Sanitize;
use \WebPExpress\SanityCheck;
use \WebPExpress\SanityException;
use \WebPExpress\ValidateException;
use \WebPExpress\Validate;

class WodConfigLoader
{

    protected static $docRoot;
    protected static $checking;
    protected static $wodOptions;
    protected static $options;
    protected static $usingDocRoot;
    protected static $webExpressContentDirAbs;

    public static function exitWithError($msg) {
        header('X-WebP-Express-Error: ' . $msg, true);
        echo $msg;
        exit;
    }

    /**
     *  Check if Apache handles the PHP requests (Note that duel setups are possible and ie Nginx could be handling the image requests).
     */
    public static function isApache()
    {
        return (stripos($_SERVER['SERVER_SOFTWARE'], 'apache') !== false);
    }

    protected static function isNginxHandlingImages()
    {
        if (stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') !== false) {
            return true;
        }

        // On WP Engine, SERVER_SOFTWARE is "Apache", but images are handled by NGINX.
        if (isset($_SERVER['WPENGINE_ACCOUNT'])) {
            return true;
        };
        return false;
    }

    public static function preventDirectAccess($filename)
    {
        // Protect against directly accessing webp-on-demand.php
        // Only protect on Apache. We know for sure that the method is not reliable on nginx.
        // We have not tested on litespeed yet, so we dare not.
        if (self::isApache() && (!self::isNginxHandlingImages())) {
            if (strpos($_SERVER['REQUEST_URI'], $filename) !== false) {
                self::exitWithError(
                    'It seems you are visiting this file (plugins/webp-express/wod/' . $filename . ') directly. We do not allow this.'
                );
                exit;
            }
        }
    }

    /**
     *  Get environment variable set with mod_rewrite module
     *  Return false if the environment variable isn't found
     */
    protected static function getEnvPassedInRewriteRule($envName) {
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

    protected static function getWebPExpressContentDirWithDocRoot()
    {
        // Get relative path to wp-content
        // --------------------------------
        self::$checking = 'Relative path to wp-content dir';

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
        self::$checking = 'WebP Express content dir';

        $webExpressContentDirAbs = SanityCheck::absPathExistsAndIsDir(self::$docRoot . '/' . $wpContentDirRel . '/webp-express');
        return $webExpressContentDirAbs;
    }

    protected static function getWebPExpressContentDirNoDocRoot() {
        // Check wp-content
        // ----------------------
        self::$checking = 'relative path between webp-express plugin dir and wp-content dir';

        // From v0.22.0, we pass relative to webp-express dir rather than to the general plugin dir.
        // - this allows symlinking the webp-express dir.
        $wpContentDirRelToWEPluginDir = self::getEnvPassedInRewriteRule('WE_WP_CONTENT_REL_TO_WE_PLUGIN_DIR');
        if (!$wpContentDirRelToWEPluginDir) {
            // Passed in QS?
            if (isset($_GET['xwp-content-rel-to-we-plugin-dir'])) {
                $xwpContentDirRelToWEPluginDir = SanityCheck::noControlChars($_GET['xwp-content-rel-to-we-plugin-dir']);
                $wpContentDirRelToWEPluginDir = SanityCheck::pathDirectoryTraversalAllowed(substr($xwpContentDirRelToWEPluginDir, 1));
            }
        }

        // Old .htaccess rules from before 0.22.0 passed relative path to general plugin dir.
        // these rules must still be supported, which is what we do here:
        if (!$wpContentDirRelToWEPluginDir) {
            self::$checking = 'relative path between plugin dir and wp-content dir';

            $wpContentDirRelToPluginDir = self::getEnvPassedInRewriteRule('WE_WP_CONTENT_REL_TO_PLUGIN_DIR');
            if ($wpContentDirRelToPluginDir === false) {
                // Passed in QS?
                if (isset($_GET['xwp-content-rel-to-plugin-dir'])) {
                    $xwpContentDirRelToPluginDir = SanityCheck::noControlChars($_GET['xwp-content-rel-to-plugin-dir']);
                    $wpContentDirRelToPluginDir = SanityCheck::pathDirectoryTraversalAllowed(substr($xwpContentDirRelToPluginDir, 1));

                } else {
                    throw new \Exception('Path to wp-content was not received in any way');
                }
            }
            $wpContentDirRelToWEPluginDir = $wpContentDirRelToPluginDir . '..';
        }


        // Check WebP Express content dir
        // ---------------------------------
        self::$checking = 'WebP Express content dir';

        $pathToWEPluginDir = dirname(dirname(__DIR__));
        $webExpressContentDirAbs = SanityCheck::pathDirectoryTraversalAllowed($pathToWEPluginDir . '/' . $wpContentDirRelToWEPluginDir . '/webp-express');

        //$pathToPluginDir = dirname(dirname(dirname(__DIR__)));
        //$webExpressContentDirAbs = SanityCheck::pathDirectoryTraversalAllowed($pathToPluginDir . '/' . $wpContentDirRelToPluginDir . '/webp-express');
        //echo $webExpressContentDirAbs; exit;
        if (@!file_exists($webExpressContentDirAbs)) {
            throw new \Exception('Dir not found');
        }
        $webExpressContentDirAbs = @realpath($webExpressContentDirAbs);
        if ($webExpressContentDirAbs === false) {
            throw new \Exception('WebP Express content dir is outside restricted open_basedir!');
        }
        return $webExpressContentDirAbs;
    }

    protected static function getImageRootsDef()
    {
        if (!isset(self::$wodOptions['image-roots'])) {
            throw new \Exception('No image roots defined in config.');
        }
        return new ImageRoots(self::$wodOptions['image-roots']);
    }

    protected static function loadConfig() {

        $usingDocRoot = !(
            isset($_GET['xwp-content-rel-to-we-plugin-dir']) ||
            self::getEnvPassedInRewriteRule('WE_WP_CONTENT_REL_TO_WE_PLUGIN_DIR') ||
            isset($_GET['xwp-content-rel-to-plugin-dir']) ||
            self::getEnvPassedInRewriteRule('WE_WP_CONTENT_REL_TO_PLUGIN_DIR')
        );
        self::$usingDocRoot = $usingDocRoot;

        if ($usingDocRoot) {
            // Check DOCUMENT_ROOT
            // ----------------------
            self::$checking = 'DOCUMENT_ROOT';
            $docRootAvailable = PathHelper::isDocRootAvailableAndResolvable();
            if (!$docRootAvailable) {
                throw new \Exception(
                    'Document root is no longer available. It was available when the .htaccess rules was created and ' .
                    'the rules are based on that. You need to regenerate the rules (or fix your document root configuration)'
                );
            }

            $docRoot = SanityCheck::absPath($_SERVER["DOCUMENT_ROOT"]);
            $docRoot = rtrim($docRoot, '/');
            self::$docRoot = $docRoot;
        }

        if ($usingDocRoot) {
            self::$webExpressContentDirAbs = self::getWebPExpressContentDirWithDocRoot();
        } else {
            self::$webExpressContentDirAbs = self::getWebPExpressContentDirNoDocRoot();
        }

        // Check config file name
        // ---------------------------------
        self::$checking = 'config file';

        $configFilename = self::$webExpressContentDirAbs . '/config/wod-options.json';
        if (!file_exists($configFilename)) {
            throw new \Exception('Configuration file was not found (wod-options.json)');
        }

        // Check config file
        // --------------------
        $configLoadResult = file_get_contents($configFilename);
        if ($configLoadResult === false) {
            throw new \Exception('Cannot open config file');
        }
        $json = SanityCheck::isJSONObject($configLoadResult);

        self::$options = json_decode($json, true);
        self::$wodOptions = self::$options['wod'];
    }

    /**
     *  Must be called after conversion.
     */
    protected static function fixConfigIfEwwwDiscoveredNonFunctionalApiKeys()
    {
        if (isset(Ewww::$nonFunctionalApiKeysDiscoveredDuringConversion)) {
            // We got an invalid or exceeded api key (at least one).
            //error_log('look:' . print_r(Ewww::$nonFunctionalApiKeysDiscoveredDuringConversion, true));
            EwwwTools::markApiKeysAsNonFunctional(
                Ewww::$nonFunctionalApiKeysDiscoveredDuringConversion,
                self::$webExpressContentDirAbs . '/config'
            );
        }

    }
}
