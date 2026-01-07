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

    protected static function getPassedHash() {
        // First check if it is passed in as an environment variable
        $hash = self::getEnvPassedInRewriteRule('HASH');
        if ($hash !== false) {
          return $hash;
        }
        // Then check if it is passed in the query string
        if (isset($_GET['hash'])) {
            return $_GET['hash'];
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

    /**
     * Find the hash from config.[hash].json inside a directory.
     *
     * @param string $configDir Absolute path to the config directory (no trailing slash)
     */
    protected static function findConfigFileByInspection($configDir)
    {
        if (!is_dir($configDir)) {
            throw new \Exception(
              'WebP Express configuration directory was not found. ' .
              'Please check that wp-content/webp-express/config exists and is readable.'
            );
        }

        if (!is_readable($configDir)) {
            throw new \Exception('WebP Express configuration directory is not readable. Please fix the permissions (wp-content/webp-express/config)');
        }

        $files = glob($configDir . '/wod-options.*.json');

        if ($files === false || empty($files)) {
            // Failed finding the pattern. Lets see if old plain "wod-options.json" is there due to migration not complete
            $oldFileName = $configDir . '/wod-options.json';
            if (file_exists($oldFileName)) {
                return $oldFileName;
            }
            throw new \Exception('Could not find a file that matches the pattern "wod-options.[hash].json" in the config dir. Is it there? Please check manually in this folder: wp-content/webp-express/config');
        }

        // We expect only one match. But failed migrations / manual restores could perhaps cause there to be several
        // So therefore, lets make sure to pick the newest file, in case there are several
        usort($files, function ($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        $configFilename = $files[0];
        if (!file_exists($configFilename)) {
            throw new \Exception('Configuration file found in filesystem, but at the same, it seems not to exist!');
        }
        return $configFilename;

        // Take the first match
        //$filename = basename($files[0]);
        // Extract hash between "config." and ".json"
        /*
        if (preg_match('/^wod-options\.([a-f0-9]+)\.json$/i', $filename, $matches)) {
            return $matches[1];
        }*/
        // throw new \Exception('The filename Could not find a file that matches the pattern "wod-options.[hash].json" in the config dir. Is it there? Please check manually in this folder: wp-content/webp-express/config');
    }

    protected static function loadConfig() {

        self::$checking = 'config folder';
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

        $hash = self::getPassedHash();
        if ($hash === false) {
            self::$checking = 'finding config file by introspecting config dir';
            $configFilename = self::findConfigFileByInspection(self::$webExpressContentDirAbs . '/config');
        } else {
            $hash = SanityCheck::noDirectoryTraversal($hash);
            if (!preg_match('/^[a-f0-9]{32}$/i', $hash)) {
                //throw new \Exception('Provided hash does not match correct pattern. Hash is expected to be 32 chars of letters/digits. Check the .htaccess files, its where the hash comes from (unless you are using Nginx - then check your Nginx rewrite rules)');
                self::$checking = 'finding config file by introspecting config dir (as no hash passed in did not conform to expected pattern, which is 32 chars of letters/digits)';
                $configFilename = self::findConfigFileByInspection(self::$webExpressContentDirAbs . '/config');
            }
            else {
                $filename = 'wod-options.' . $hash . '.json';
                $configFilename = self::$webExpressContentDirAbs . '/config/' . $filename;
                if (!file_exists($configFilename)) {
                    self::$checking = 'finding config file by introspecting config dir (as no config file was found using hash passed in)';
                    $configFilename = self::findConfigFileByInspection(self::$webExpressContentDirAbs . '/config');
                }
            }
        }

        // Check config file
        // --------------------
        $configLoadResult = file_get_contents($configFilename);
        if ($configLoadResult === false) {
            throw new \Exception('Cannot open config file: ' . $configFilename);
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
