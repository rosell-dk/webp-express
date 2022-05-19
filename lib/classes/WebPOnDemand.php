<?php
/*
This class is used by wod/webp-on-demand.php, which does not do a Wordpress bootstrap, but does register an autoloader for
the WebPExpress classes.

Calling Wordpress functions will FAIL. Make sure not to do that in either this class or the helpers.
*/
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

namespace WebPExpress;

use \WebPExpress\ConvertHelperIndependent;
use \WebPExpress\Sanitize;
use \WebPExpress\SanityCheck;
use \WebPExpress\SanityException;
use \WebPExpress\ValidateException;
use \WebPExpress\Validate;
use \WebPExpress\WodConfigLoader;
use WebPConvert\Loggers\EchoLogger;

class WebPOnDemand extends WodConfigLoader
{
    private static function getSourceDocRoot() {


        //echo 't:' . $_GET['test'];exit;
        // Check if it is in an environment variable
        $source = self::getEnvPassedInRewriteRule('REQFN');
        if ($source !== false) {
            self::$checking = 'source (passed through env)';
            return SanityCheck::absPathExistsAndIsFile($source);
        }

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
            self::$checking = 'source (passed through request header)';
            return SanityCheck::absPathExistsAndIsFile($_SERVER['HTTP_REQFN']);
        }

        if (!isset(self::$docRoot)) {
            //$source = self::getEnvPassedInRewriteRule('REQFN');
            if (isset($_GET['root-id']) && isset($_GET['xsource-rel-to-root-id'])) {
                $xsrcRelToRootId = SanityCheck::noControlChars($_GET['xsource-rel-to-root-id']);
                $srcRelToRootId = SanityCheck::pathWithoutDirectoryTraversal(substr($xsrcRelToRootId, 1));
                //echo $srcRelToRootId; exit;

                $rootId = SanityCheck::noControlChars($_GET['root-id']);
                SanityCheck::pregMatch('#^[a-z]+$#', $rootId, 'Not a valid root-id');

                $source = self::getRootPathById($rootId) . '/' . $srcRelToRootId;
                return SanityCheck::absPathExistsAndIsFile($source);
            }
        }

        // Check querystring (relative path to docRoot) - when docRoot is available
        if (isset(self::$docRoot) && isset($_GET['xsource-rel'])) {
            self::$checking = 'source (passed as relative path, through querystring)';
            $xsrcRel = SanityCheck::noControlChars($_GET['xsource-rel']);
            $srcRel = SanityCheck::pathWithoutDirectoryTraversal(substr($xsrcRel, 1));
            return SanityCheck::absPathExistsAndIsFile(self::$docRoot . '/' . $srcRel);
        }

        // Check querystring (relative path to plugin) - when docRoot is unavailable
        /*TODO
        if (!isset(self::$docRoot) && isset($_GET['xsource-rel-to-plugin-dir'])) {
            self::$checking = 'source (passed as relative path to plugin dir, through querystring)';
            $xsrcRelPlugin = SanityCheck::noControlChars($_GET['xsource-rel-to-plugin-dir']);
            $srcRelPlugin = SanityCheck::pathWithoutDirectoryTraversal(substr($xsrcRelPlugin, 1));
            return SanityCheck::absPathExistsAndIsFile(self::$docRoot . '/' . $srcRel);
        }*/


        // Check querystring (full path)
        // - But only on Nginx (our Apache .htaccess rules never passes absolute url)
        if (
            (self::isNginxHandlingImages()) &&
            (isset($_GET['source']) || isset($_GET['xsource']))
        ) {
            self::$checking = 'source (passed as absolute path on nginx)';
            if (isset($_GET['source'])) {
                $source = SanityCheck::absPathExistsAndIsFile($_GET['source']);
            } else {
                $xsrc = SanityCheck::noControlChars($_GET['xsource']);
                return SanityCheck::absPathExistsAndIsFile(substr($xsrc, 1));
            }
        }

        // Last resort is to use $_SERVER['REQUEST_URI'], well knowing that it does not give the
        // correct result in all setups (ie "folder method 1")
        if (isset(self::$docRoot)) {
            self::$checking = 'source (retrieved by the request_uri server var)';
            $srcRel = SanityCheck::pathWithoutDirectoryTraversal(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
            return SanityCheck::absPathExistsAndIsFile(self::$docRoot . $srcRel);
        }
    }

    private static function getSourceNoDocRoot()
    {
        $dirIdOfHtaccess = self::getEnvPassedInRewriteRule('WE_HTACCESS_ID');
        if ($dirIdOfHtaccess === false) {
            $dirIdOfHtaccess = SanityCheck::noControlChars($_GET['htaccess-id']);
        }

        if (!in_array($dirIdOfHtaccess, ['uploads', 'themes', 'wp-content', 'plugins', 'index'])) {
            throw new \Exception('invalid htaccess directory id argument.');
        }

        // First try ENV
        $sourceRelHtaccess = self::getEnvPassedInRewriteRule('WE_SOURCE_REL_HTACCESS');

        // Otherwise use query-string
        if ($sourceRelHtaccess === false) {
            if (isset($_GET['xsource-rel-htaccess'])) {
                $x = SanityCheck::noControlChars($_GET['xsource-rel-htaccess']);
                $sourceRelHtaccess = SanityCheck::pathWithoutDirectoryTraversal(substr($x, 1));
            } else {
                throw new \Exception('Argument for source path is missing');
            }
        }

        $sourceRelHtaccess = SanityCheck::pathWithoutDirectoryTraversal($sourceRelHtaccess);


        $imageRoots = self::getImageRootsDef();

        $source = $imageRoots->byId($dirIdOfHtaccess)->getAbsPath() . '/' . $sourceRelHtaccess;
        return $source;
    }

    private static function getSource() {
        if (self::$usingDocRoot) {
            $source = self::getSourceDocRoot();
        } else {
            $source = self::getSourceNoDocRoot();
        }
        return $source;
    }

    private static function processRequestNoTryCatch() {

        self::loadConfig();

        $options = self::$options;
        $wodOptions = self::$wodOptions;
        $serveOptions = $options['webp-convert'];
        $convertOptions = &$serveOptions['convert'];
        //echo '<pre>' . print_r($wodOptions, true) . '</pre>'; exit;


        // Validate that WebPExpress was configured to redirect to this conversion script
        // (but do not require that for Nginx)
        // ------------------------------------------------------------------------------
        self::$checking = 'settings';
        if (stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') === false) {
            if (!isset($wodOptions['enable-redirection-to-converter']) || ($wodOptions['enable-redirection-to-converter'] === false)) {
                throw new ValidateException('Redirection to conversion script is not enabled');
            }
        }

        // Check source (the image to be converted)
        // --------------------------------------------
        self::$checking = 'source';

        // Decode URL in case file contains encoded symbols (#413)
        $source = urldecode(self::getSource());

        //self::exitWithError($source);

        $imageRoots = self::getImageRootsDef();

        // Get upload dir
        $uploadDirAbs = $imageRoots->byId('uploads')->getAbsPath();

        // Check destination path
        // --------------------------------------------
        self::$checking = 'destination path';
        $destination = ConvertHelperIndependent::getDestination(
            $source,
            $wodOptions['destination-folder'],
            $wodOptions['destination-extension'],
            self::$webExpressContentDirAbs,
            $uploadDirAbs,
            self::$usingDocRoot,
            self::getImageRootsDef()
        );

        //$destination = SanityCheck::absPathIsInDocRoot($destination);
        $destination = SanityCheck::pregMatch('#\.webp$#', $destination, 'Does not end with .webp');

        //self::exitWithError($destination);

        // Done with sanitizing, lets get to work!
        // ---------------------------------------
        self::$checking = 'done';

        if (isset($wodOptions['success-response']) && ($wodOptions['success-response'] == 'original')) {
            $serveOptions['serve-original'] = true;
            $serveOptions['serve-image']['headers']['vary-accept'] = false;
        } else {
            $serveOptions['serve-image']['headers']['vary-accept'] = true;
        }
//echo $source . '<br>' . $destination; exit;

        /*
        // No caching!
        // - perhaps this will solve it for WP engine.
        // but no... Perhaps a 302 redirect to self then? (if redirect to existing is activated).
        // TODO: try!
        //$serveOptions['serve-image']['headers']['vary-accept'] = false;

        */
/*
        include_once __DIR__ . '/../../vendor/autoload.php';
        $convertLogger = new \WebPConvert\Loggers\BufferLogger();
        \WebPConvert\WebPConvert::convert($source, $destination, $serveOptions['convert'], $convertLogger);
        header('Location: ?fresh' , 302);
*/

        if (isset($_SERVER['WPENGINE_ACCOUNT'])) {
            // Redirect to self rather than serve directly for WP Engine.
            // This overcomes that Vary:Accept header set from PHP is lost on WP Engine.
            // To prevent endless loop in case "redirect to existing webp" isn't set up correctly,
            // only activate when destination is missing.
            //   (actually it does not prevent anything on wpengine as the first request is cached!
            //    -even though we try to prevent it:)
            // Well well. Those users better set up "redirect to existing webp" as well!
            $serveOptions['serve-image']['headers']['cache-control'] = true;
            $serveOptions['serve-image']['headers']['expires'] = false;
            $serveOptions['serve-image']['cache-control-header'] = 'no-store, no-cache, must-revalidate, max-age=0';
            //header("Pragma: no-cache", true);

            if (!@file_exists($destination)) {
                $serveOptions['redirect-to-self-instead-of-serving'] = true;
            }
        }

        $loggingEnabled = (isset($wodOptions['enable-logging']) ? $wodOptions['enable-logging'] : true);
        $logDir = ($loggingEnabled ? self::$webExpressContentDirAbs . '/log' : null);

        ConvertHelperIndependent::serveConverted(
            $source,
            $destination,
            $serveOptions,
            $logDir,
            'Conversion triggered with the conversion script (wod/webp-on-demand.php)'
        );

        BiggerThanSourceDummyFiles::updateStatus(
            $source,
            $destination,
            self::$webExpressContentDirAbs,
            self::getImageRootsDef(),
            $wodOptions['destination-folder'],
            $wodOptions['destination-extension']
        );

        self::fixConfigIfEwwwDiscoveredNonFunctionalApiKeys();
    }

    public static function processRequest() {
        try {
            self::processRequestNoTryCatch();
        } catch (SanityException $e) {
            self::exitWithError('Sanity check failed for ' . self::$checking . ': '. $e->getMessage());
        } catch (ValidateException $e) {
            self::exitWithError('Validation failed for ' . self::$checking . ': '. $e->getMessage());
        } catch (\Exception $e) {
            if (self::$checking == 'done') {
                self::exitWithError('Error occured during conversion/serving:' . $e->getMessage());
            } else {
                self::exitWithError('Error occured while calculating ' . self::$checking . ': '. $e->getMessage());
            }
        }
    }
}
