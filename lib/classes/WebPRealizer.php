<?php
/*
This class is used by wod/webp-realizer.php, which does not do a Wordpress bootstrap, but does register an autoloader for
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

class WebPRealizer extends WodConfigLoader
{
    private static function getDestinationDocRoot() {
        $docRoot = self::$docRoot;

        // Check if it is in an environment variable
        $destRel = self::getEnvPassedInRewriteRule('DESTINATIONREL');
        if ($destRel !== false) {
            return SanityCheck::absPath($docRoot . '/' . $destRel);
        }

        // Check querystring (relative path)
        if (isset($_GET['xdestination-rel'])) {
            $xdestRel = SanityCheck::noControlChars($_GET['xdestination-rel']);
            $destRel = SanityCheck::pathWithoutDirectoryTraversal(substr($xdestRel, 1));
            $destination = SanityCheck::absPath($docRoot . '/' . $destRel);
            return SanityCheck::absPathIsInDocRoot($destination);
        }

        // Check querystring (full path)
        // - But only on Nginx (our Apache .htaccess rules never passes absolute url)
        if (self::isNginxHandlingImages()) {
            if (isset($_GET['destination'])) {
                return SanityCheck::absPathIsInDocRoot($_GET['destination']);
            }
            if (isset($_GET['xdestination'])) {
                $xdest = SanityCheck::noControlChars($_GET['xdestination']);
                return SanityCheck::absPathIsInDocRoot(substr($xdest, 1));
            }
        }

        // Last resort is to use $_SERVER['REQUEST_URI'], well knowing that it does not give the
        // correct result in all setups (ie "folder method 1").
        // On nginx, it can even return the path to webp-realizer.php. TODO: Handle that better than now
        $destRel = SanityCheck::pathWithoutDirectoryTraversal(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        if ($destRel) {
            if (preg_match('#webp-realizer\.php$#', $destRel)) {
                throw new \Exception(
                    'webp-realizer.php need to know the file path and cannot simply use $_SERVER["REQUEST_URI"] ' .
                    'as that points to itself rather than the image requested. ' .
                    'On Nginx, please add: "&xdestination=x$request_filename" to the URL in the rules in the nginx config ' .
                    '(sorry, the parameter was missing in the rules in the README for a while, but it is back)'
                );
            }
        }
        $destination = SanityCheck::absPath($docRoot . $destRel);
        return SanityCheck::absPathIsInDocRoot($destination);
    }

    private static function getDestinationNoDocRoot() {

        $dirIdOfHtaccess = self::getEnvPassedInRewriteRule('WE_HTACCESS_ID');
        if ($dirIdOfHtaccess === false) {
            $dirIdOfHtaccess = SanityCheck::noControlChars($_GET['htaccess-id']);
        }

        if (!in_array($dirIdOfHtaccess, ['uploads', 'cache'])) {
            throw new \Exception('invalid htaccess directory id argument. It must be either "uploads" or "cache".');
        }


        // First try ENV
        $destinationRelHtaccess = self::getEnvPassedInRewriteRule('WE_DESTINATION_REL_HTACCESS');

        // Otherwise use query-string
        if ($destinationRelHtaccess === false) {
            if (isset($_GET['xdestination-rel-htaccess'])) {
                $x = SanityCheck::noControlChars($_GET['xdestination-rel-htaccess']);
                $destinationRelHtaccess = SanityCheck::pathWithoutDirectoryTraversal(substr($x, 1));
            } else {
                throw new \Exception('Argument for destination path is missing');
            }
        }

        $destinationRelHtaccess = SanityCheck::pathWithoutDirectoryTraversal($destinationRelHtaccess);

        $imageRoots = self::getImageRootsDef();
        if ($dirIdOfHtaccess == 'uploads') {
            return $imageRoots->byId('uploads')->getAbsPath() . '/' . $destinationRelHtaccess;
        } elseif ($dirIdOfHtaccess == 'cache') {
            return $imageRoots->byId('wp-content')->getAbsPath() . '/webp-express/webp-images/' . $destinationRelHtaccess;
        }
        /*
        $pathTokens = explode('/', $destinationRelCacheRoot);
        $imageRootId = array_shift($pathTokens);
        $destinationRelSpecificCacheRoot = implode('/', $pathTokens);

        $imageRootId = SanityCheck::pregMatch(
            '#^[a-z\-]+$#',
            $imageRootId,
            'The image root ID is not a valid root id'
        );

        // TODO: Validate that the root id is in scope

        if (count($pathTokens) == 0) {
            throw new \Exception('invalid destination argument. It must contain dashes.');
        }

        return $imageRoots->byId($imageRootId)->getAbsPath() . '/' . $destinationRelSpecificCacheRoot;

/*
        if ($imageRootId !== false) {

        //$imageRootId = self::getEnvPassedInRewriteRule('WE_IMAGE_ROOT_ID');
        if ($imageRootId !== false) {
            $imageRootId = SanityCheck::pregMatch('#^[a-z\-]+$#', $imageRootId, 'The image root ID passed in ENV is not a valid root-id');

            $destinationRelImageRoot = self::getEnvPassedInRewriteRule('WE_DESTINATION_REL_IMAGE_ROOT');
            if ($destinationRelImageRoot !== false) {
                $destinationRelImageRoot = SanityCheck::pathWithoutDirectoryTraversal($destinationRelImageRoot);
            }
            $imageRoots = self::getImageRootsDef();
            return $imageRoots->byId($imageRootId)->getAbsPath() . '/' . $destinationRelImageRoot;
        }

        if (isset($_GET['xdestination-rel-image-root'])) {
            $xdestinationRelImageRoot = SanityCheck::noControlChars($_GET['xdestination-rel-image-root']);
            $destinationRelImageRoot = SanityCheck::pathWithoutDirectoryTraversal(substr($xdestinationRelImageRoot, 1));

            $imageRootId = SanityCheck::noControlChars($_GET['image-root-id']);
            SanityCheck::pregMatch('#^[a-z\-]+$#', $imageRootId, 'Not a valid root-id');

            $imageRoots = self::getImageRootsDef();
            return $imageRoots->byId($imageRootId)->getAbsPath() . '/' . $destinationRelImageRoot;
        }

        throw new \Exception('Argument for destination file missing');
        //WE_DESTINATION_REL_IMG_ROOT*/

        /*
        $destAbs = SanityCheck::noControlChars(self::getEnvPassedInRewriteRule('WEDESTINATIONABS'));
        if ($destAbs !== false) {
            return SanityCheck::pathWithoutDirectoryTraversal($destAbs);
        }

        // Check querystring (relative path)
        if (isset($_GET['xdest-rel-to-root-id'])) {
            $xdestRelToRootId = SanityCheck::noControlChars($_GET['xdest-rel-to-root-id']);
            $destRelToRootId = SanityCheck::pathWithoutDirectoryTraversal(substr($xdestRelToRootId, 1));

            $rootId = SanityCheck::noControlChars($_GET['root-id']);
            SanityCheck::pregMatch('#^[a-z]+$#', $rootId, 'Not a valid root-id');
            return self::getRootPathById($rootId) . '/' . $destRelToRootId;
        }
        */

    }

    private static function getDestination() {
        self::$checking = 'destination path';
        if (self::$usingDocRoot) {
            $destination = self::getDestinationDocRoot();
        } else {
            $destination = self::getDestinationNoDocRoot();
        }
        SanityCheck::pregMatch('#\.webp$#', $destination, 'Does not end with .webp');

        return $destination;
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
            if (!isset($wodOptions['enable-redirection-to-webp-realizer']) || ($wodOptions['enable-redirection-to-webp-realizer'] === false)) {
                throw new ValidateException('Redirection to webp realizer is not enabled');
            }
        }

        // Get destination
        // --------------------------------------------
        self::$checking = 'destination';
        // Decode URL in case file contains encoded symbols (#413)
        $destination = urldecode(self::getDestination());

        //self::exitWithError($destination);

        // Validate source path
        // --------------------------------------------
        $checking = 'source path';
        $source = ConvertHelperIndependent::findSource(
            $destination,
            $wodOptions['destination-folder'],
            $wodOptions['destination-extension'],
            self::$usingDocRoot ? 'doc-root' : 'image-roots',
            self::$webExpressContentDirAbs,
            self::getImageRootsDef()
        );
        //self::exitWithError('source:' . $source);
        //echo '<h3>destination:</h3> ' . $destination . '<h3>source:</h3>' . $source; exit;

        if ($source === false) {
            header('X-WebP-Express-Error: webp-realizer.php could not find an existing jpg/png that corresponds to the webp requested', true);

            $protocol = isset($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : 'HTTP/1.0';
            header($protocol . " 404 Not Found");
            die();
            //echo 'destination requested:<br><i>' . $destination . '</i>';
        }
        //$source = SanityCheck::absPathExistsAndIsFileInDocRoot($source);

        // Done with sanitizing, lets get to work!
        // ---------------------------------------
        $serveOptions['add-vary-header'] = false;
        $serveOptions['fail'] = '404';
        $serveOptions['fail-when-fail-fails'] = '404';
        $serveOptions['serve-image']['headers']['vary-accept'] = false;

        $loggingEnabled = (isset($wodOptions['enable-logging']) ? $wodOptions['enable-logging'] : true);
        $logDir = ($loggingEnabled ? self::$webExpressContentDirAbs . '/log' : null);

        ConvertHelperIndependent::serveConverted(
            $source,
            $destination,
            $serveOptions,
            $logDir,
            'Conversion triggered with the conversion script (wod/webp-realizer.php)'
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
            self::exitWithError('Error occured while calculating ' . self::$checking . ': '. $e->getMessage());
        }
    }
}
