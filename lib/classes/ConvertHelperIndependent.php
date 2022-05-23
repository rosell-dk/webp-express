<?php

/*
This class is made to not be dependent on Wordpress functions and must be kept like that.
It is used by webp-on-demand.php. It is also used for bulk conversion.
*/
namespace WebPExpress;

use \WebPConvert\WebPConvert;
use \WebPConvert\Convert\ConverterFactory;
use \WebPConvert\Exceptions\WebPConvertException;
use \WebPConvert\Loggers\BufferLogger;

use \WebPExpress\FileHelper;
use \WebPExpress\SanityCheck;
use \WebPExpress\SanityException;

class ConvertHelperIndependent
{

    /**
     *
     * @return boolean  Whether or not the destination corresponding to a given source should be stored in the same folder or the separate (in wp-content/webp-express)
     */
    private static function storeMingledOrNot($source, $destinationFolder, $uploadDirAbs)
    {
        if ($destinationFolder != 'mingled') {
            return false;
        }

        // Option is set for mingled, but this does not neccessarily means we should store "mingled".
        // - because the mingled option only applies to upload folder, the rest is stored in separate cache folder
        // So, return true, if $source is located in upload folder
        return (strpos($source, $uploadDirAbs) === 0);
    }

    /**
     *  Verify if source is inside in document root
     *  Note: This function relies on the existence of both.
     *
     *  @return true if windows; false if not.
     */
    public static function sourceIsInsideDocRoot($source, $docRoot){

        $normalizedSource = realpath($source);
        $normalizedDocRoot = realpath($docRoot);

        return strpos($normalizedSource, $normalizedDocRoot) === 0;
    }

    public static function getSource()
    {

    }

    /**
     * Append ".webp" to path or replace extension with "webp", depending on what is appropriate.
     *
     * If destination-folder is set to mingled and destination-extension is set to "set" and
     * the path is inside upload folder, the appropriate thing is to SET the extension.
     * Otherwise, it is to APPEND.
     *
     * @param  string  $path
     * @param  string  $destinationFolder
     * @param  string  $destinationExt
     * @param  boolean $inUploadFolder
     */
    public static function appendOrSetExtension($path, $destinationFolder, $destinationExt, $inUploadFolder)
    {
        if (($destinationFolder == 'mingled') && ($destinationExt == 'set') && $inUploadFolder) {
            return preg_replace('/\\.(jpe?g|png)$/i', '', $path) . '.webp';
        } else {
            return $path . '.webp';
        }
    }

    /**
     * Get destination path corresponding to the source path given (and some configurations)
     *
     * If for example Operation mode is set to "mingled" and extension is set to "Append .webp",
     * the result of finding the destination path that corresponds to "/path/to/logo.jpg" will be "/path/to/logo.jpg.webp".
     *
     * @param  string   $source                     Path to source file
     * @param  string   $destinationFolder          'mingled' or 'separate'
     * @param  string   $destinationExt             Extension ('append' or 'set')
     * @param  string   $webExpressContentDirAbs
     * @param  string   $uploadDirAbs
     * @param  boolean  $useDocRootForStructuringCacheDir
     * @param  ImageRoots  $imageRoots                An image roots object
     *
     * @return string|false   Returns path to destination corresponding to source, or false on failure
     */
    public static function getDestination(
        $source,
        $destinationFolder,
        $destinationExt,
        $webExpressContentDirAbs,
        $uploadDirAbs,
        $useDocRootForStructuringCacheDir,
        $imageRoots)
    {
        // At this point, everything has already been checked for sanity. But for good meassure, lets
        // check the most important parts again. This is after all a public method.
        // ------------------------------------------------------------------

        try {
            // Check source
            // --------------
            // TODO: make this check work with symlinks
            //$source = SanityCheck::absPathExistsAndIsFileInDocRoot($source);

            // Calculate destination and check that the result is sane
            // -------------------------------------------------------
            if (self::storeMingledOrNot($source, $destinationFolder, $uploadDirAbs)) {
                $destination = self::appendOrSetExtension($source, $destinationFolder, $destinationExt, true);
            } else {

                if ($useDocRootForStructuringCacheDir) {
                    // We must find the relative path from document root to source.
                    // However, we dont know if document root is resolved or not.
                    // We also do not know if source begins with a resolved or unresolved document root.
                    // And we cannot be sure that document root is resolvable.

                    // Lets say:
                    // 1. document root is unresolvable.
                    // 2. document root is configured to something unresolved ("/my-website")
                    // 3. source is resolved and within an image root ("/var/www/my-website/wp-content/uploads/test.jpg")
                    // 4. all image roots are resolvable.
                    // 5. Paths::canUseDocRootForRelPaths()) returned true

                    // Can the relative path then be found?
                    // Actually, yes.
                    // We can loop through the image roots.
                    // When we get to the "uploads" root, it must neccessarily contain the unresolved document root.
                    // It will in other words be: "my-website/wp-content/uploads"
                    // It can not be configured to the resolved path because canUseDocRootForRelPaths would have then returned false as
                    // It would not be possible to establish that "/var/www/my-website/wp-content/uploads/" is within document root, as
                    // document root is "/my-website" and unresolvable.
                    // To sum up, we have:
                    // If document root is unresolvable while canUseDocRootForRelPaths() succeeded, then the image roots will all begin with
                    // the unresolved path.
                    // In this method, if $useDocRootForStructuringCacheDir is true, then it is assumed that canUseDocRootForRelPaths()
                    // succeeded.
                    // OH!
                    // I realize that the image root can be passed as well:
                    // $imageRoot = $webExpressContentDirAbs . '/webp-images';
                    // So the question is: Will $webExpressContentDirAbs also be the unresolved path?
                    // That variable is calculated in WodConfigLoader based on various methods available.
                    // I'm not digging into it, but would expect it to in some cases be resolved. Which means that relative path can not
                    // be found.
                    // So. Lets play it safe and require that document root is resolvable in order to use docRoot for structure

                    if (!PathHelper::isDocRootAvailable()) {
                        throw new \Exception(
                            'Can not calculate destination using "doc-root" structure as document root is not available. $_SERVER["DOCUMENT_ROOT"] is empty. ' .
                            'This is probably a misconfiguration on the server. ' .
                            'However, WebP Express can function without using documument root. If you resave options and regenerate the .htaccess files, it should ' .
                            'automatically start to structure the webp files in subfolders that are relative the image root folders rather than document-root.'
                        );
                    }

                    if (!PathHelper::isDocRootAvailableAndResolvable()) {
                        throw new \Exception(
                            'Can not calculate destination using "doc-root" structure as document root cannot be resolved for symlinks using "realpath". The ' .
                            'reason for that is probably that open_basedir protection has been set up and that document root is outside outside that open_basedir. ' .
                            'WebP Express can function in that setting, however you will need to resave options and regenerate the .htaccess files. It should then ' .
                            'automatically stop to structure the webp files as relative to document root and instead structure them as relative to image root folders.'
                        );
                    }
                    $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
                    $imageRoot = $webExpressContentDirAbs . '/webp-images';

                    // TODO: make this check work with symlinks
                    //SanityCheck::absPathIsInDocRoot($imageRoot);

                    $sourceRel = substr(realpath($source), strlen($docRoot) + 1);
                    $destination = $imageRoot . '/doc-root/' . $sourceRel;
                    $destination = self::appendOrSetExtension($destination, $destinationFolder, $destinationExt, false);


                    // TODO: make this check work with symlinks
                    //$destination = SanityCheck::absPathIsInDocRoot($destination);
                } else {
                    $destination = '';

                    $sourceResolved = realpath($source);


                    // Check roots until we (hopefully) get a match.
                    // (that is: find a root which the source is inside)
                    foreach ($imageRoots->getArray() as $i => $imageRoot) {
                        // in $obj, "rel-path" is only set when document root can be used for relative paths.
                        // So, if it is set, we can use it (beware: we cannot neccessarily use realpath on document root,
                        // but we do not need to - see the long comment in Paths::canUseDocRootForRelPaths())

                        $rootPath = $imageRoot->getAbsPath();
                        /*
                        if (isset($obj['rel-path'])) {
                            $docRoot = rtrim($_SERVER["DOCUMENT_ROOT"], '/');
                            $rootPath = $docRoot . '/' . $obj['rel-path'];
                        } else {
                            // If "rel-path" isn't set, then abs-path is, and we can use that.
                            $rootPath = $obj['abs-path'];
                        }*/

                        // $source may be resolved or not. Same goes for $rootPath.
                        // We can assume that $rootPath is resolvable using realpath (it ought to exist and be within open_basedir for WP to function)
                        // We can also assume that $source is resolvable (it ought to exist and within open_basedir)
                        // So: Resolve both! and test if the resolved source begins with the resolved rootPath.
                        if (strpos($sourceResolved, realpath($rootPath)) !== false) {
                            $relPath = substr($sourceResolved, strlen(realpath($rootPath)) + 1);
                            $relPath = self::appendOrSetExtension($relPath, $destinationFolder, $destinationExt, false);

                            $destination = $webExpressContentDirAbs . '/webp-images/' . $imageRoot->id . '/' . $relPath;
                            break;
                        }
                    }
                    if ($destination == '') {
                        return false;
                    }
                }
            }

        } catch (SanityException $e) {
            return false;
        }

        return $destination;
    }


    /**
     * Find source corresponding to destination, separate.
     *
     * We can rely on destinationExt being "append" for separate.
     * Returns false if source file is not found or if a path is not sane. Otherwise returns path to source
     * destination does not have to exist.
     *
     * @param  string      $destination               Path to destination file (does not have to exist)
     * @param  string      $destinationStructure      "doc-root" or "image-roots"
     * @param  string      $webExpressContentDirAbs
     * @param  ImageRoots  $imageRoots                An image roots object
     *
     * @return string|false   Returns path to source, if found. If not - or a path is not sane, false is returned
     */
    private static function findSourceSeparate($destination, $destinationStructure, $webExpressContentDirAbs, $imageRoots)
    {
        try {

            if ($destinationStructure == 'doc-root') {

                // Check that destination path is sane and inside document root
                // --------------------------
                $destination = SanityCheck::absPathIsInDocRoot($destination);


                // Check that calculated image root is sane and inside document root
                // --------------------------
                $imageRoot = SanityCheck::absPathIsInDocRoot($webExpressContentDirAbs . '/webp-images/doc-root');


                // Calculate source and check that it is sane and exists
                // -----------------------------------------------------

                // TODO: This does not work on Windows yet.
                if (strpos($destination, $imageRoot . '/') === 0) {

                    // "Eat" the left part off the $destination parameter. $destination is for example:
                    // "/var/www/webp-express-tests/we0/wp-content-moved/webp-express/webp-images/doc-root/wordpress/uploads-moved/2018/12/tegning5-300x265.jpg.webp"
                    // We also eat the slash (+1)
                    $sourceRel = substr($destination, strlen($imageRoot) + 1);

                    $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
                    $source = $docRoot . '/' . $sourceRel;
                    $source =  preg_replace('/\\.(webp)$/', '', $source);
                } else {
                    // Try with symlinks resolved
                    // This is not trivial as this must also work when the destination path doesn't exist, and
                    // realpath can only be used to resolve symlinks for files that exists.
                    // But here is how we achieve it anyway:
                    //
                    // 1. We make sure imageRoot exists (if not, create it) - this ensures that we can resolve it.
                    // 2. Find closest folder existing folder (resolved) of destination - using PathHelper::findClosestExistingFolderSymLinksExpanded()
                    // 3. Test that resolved closest existing folder starts with resolved imageRoot
                    // 4. If it does, we could create a dummy file at the destination to get its real path, but we want to avoid that, so instead
                    //    we can create the containing directory.
                    // 5. We can now use realpath to get the resolved path of the containing directory. The rest is simple enough.
                    if (!file_exists($imageRoot)) {
                        mkdir($imageRoot, 0777, true);
                    }
                    $closestExistingResolved = PathHelper::findClosestExistingFolderSymLinksExpanded($destination);
                    if ($closestExistingResolved == '') {
                        return false;
                    } else {
                        $imageRootResolved = realpath($imageRoot);
                        if (strpos($closestExistingResolved . '/', $imageRootResolved . '/') === 0) {
//                            echo $destination . '<br>' . $closestExistingResolved . '<br>' . $imageRootResolved . '/'; exit;
                            // Create containing dir for destination
                            $containingDir = PathHelper::dirname($destination);
                            if (!file_exists($containingDir)) {
                                mkdir($containingDir, 0777, true);
                            }
                            $containingDirResolved = realpath($containingDir);

                            $filename = PathHelper::basename($destination);
                            $destinationResolved = $containingDirResolved . '/' . $filename;

                            $sourceRel = substr($destinationResolved, strlen($imageRootResolved) + 1);

                            $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
                            $source = $docRoot . '/' . $sourceRel;
                            $source =  preg_replace('/\\.(webp)$/', '', $source);
                            return $source;
                        } else {
                            return false;
                        }
                    }
                }

                return SanityCheck::absPathExistsAndIsFileInDocRoot($source);
            } else {

                // Mission: To find source corresponding to destination (separate) - using the "image-roots" structure.

                // How can we do that?
                // We got the destination (unresolved) - ie '/website-symlinked/wp-content/webp-express/webp-images/uploads/2018/07/hello.jpg.webp'
                // If we were lazy and unprecise, we could simply:
                // - search for "webp-express/webp-images/"
                // - strip anything before that - result: 'uploads/2018/07/hello.jpg.webp'
                // - the first path component is the root id.
                // - the rest of the path is the relative path to the source - if we strip the ".webp" ending

                // So, are we lazy? - what is the alternative?
                // - Get closest existing resolved folder of destination (ie "/var/www/website/wp-content-moved/webp-express/webp-images/wp-content")
                // - Check if that folder is below the cache root (resolved) (cache root is the "wp-content" image root + 'webp-express/webp-images')
                // - Create dir for destination (if missing)
                // - We can now resolve destination. With cache root also being resolved, we can get the relative dir.
                //   ie 'uploads/2018/07/hello.jpg.webp'.
                //   The first path component is the root id, the rest is the relative path to the source.

                $closestExistingResolved = PathHelper::findClosestExistingFolderSymLinksExpanded($destination);
                $cacheRoot = $webExpressContentDirAbs . '/webp-images';
                if ($closestExistingResolved == '') {
                    return false;
                } else {
                    $cacheRootResolved = realpath($cacheRoot);
                    if (strpos($closestExistingResolved . '/', $cacheRootResolved . '/') === 0) {

                        // Create containing dir for destination
                        $containingDir = PathHelper::dirname($destination);
                        if (!file_exists($containingDir)) {
                            mkdir($containingDir, 0777, true);
                        }
                        $containingDirResolved = realpath($containingDir);

                        $filename = PathHelper::basename($destination);
                        $destinationResolved = $containingDirResolved . '/' . $filename;
                        $destinationRelToCacheRoot = substr($destinationResolved, strlen($cacheRootResolved) + 1);

                        $parts = explode('/', $destinationRelToCacheRoot);
                        $imageRoot = array_shift($parts);
                        $sourceRel = implode('/', $parts);

                        $source = $imageRoots->byId($imageRoot)->getAbsPath() . '/' . $sourceRel;
                        $source = preg_replace('/\\.(webp)$/', '', $source);
                        return $source;
                    } else {
                        return false;
                    }
                }
                return false;
            }
        } catch (SanityException $e) {
            return false;
        }

        return $source;
    }

    /**
     * Find source corresponding to destination (mingled)
     * Returns false if not found. Otherwise returns path to source
     *
     * @param  string  $destination             Path to destination file (does not have to exist)
     * @param  string  $destinationExt          Extension ('append' or 'set')
     * @param  string  $destinationStructure    "doc-root" or "image-roots"
     *
     * @return string|false   Returns path to source, if found. If not - or a path is not sane, false is returned
     */
    private static function findSourceMingled($destination, $destinationExt, $destinationStructure)
    {
        try {

            if ($destinationStructure == 'doc-root') {
                // Check that destination path is sane and inside document root
                // --------------------------
                $destination = SanityCheck::absPathIsInDocRoot($destination);
            } else {
                // The following will fail if path contains directory traversal. TODO: Is that ok?
                $destination = SanityCheck::absPath($destination);
            }

            // Calculate source and check that it is sane and exists
            // -----------------------------------------------------
            if ($destinationExt == 'append') {
                $source =  preg_replace('/\\.(webp)$/', '', $destination);
            } else {
                $source =  preg_replace('#\\.webp$#', '.jpg', $destination);
                // TODO!
                // Also check for "Jpeg", "JpEg" etc.
                if (!@file_exists($source)) {
                    $source =  preg_replace('/\\.webp$/', '.jpeg', $destination);
                }
                if (!@file_exists($source)) {
                    $source =  preg_replace('/\\.webp$/', '.JPG', $destination);
                }
                if (!@file_exists($source)) {
                    $source =  preg_replace('/\\.webp$/', '.JPEG', $destination);
                }
                if (!@file_exists($source)) {
                    $source =  preg_replace('/\\.webp$/', '.png', $destination);
                }
                if (!@file_exists($source)) {
                    $source =  preg_replace('/\\.webp$/', '.PNG', $destination);
                }
            }
            if ($destinationStructure == 'doc-root') {
                $source = SanityCheck::absPathExistsAndIsFileInDocRoot($source);
            } else {
                $source = SanityCheck::absPathExistsAndIsFile($source);
            }


        } catch (SanityException $e) {
            return false;
        }

        return $source;
    }

    /**
     * Get source from destination (and some configurations)
     * Returns false if not found. Otherwise returns path to source
     *
     * @param  string  $destination               Path to destination file (does not have to exist). May not contain directory traversal
     * @param  string  $destinationFolder         'mingled' or 'separate'
     * @param  string  $destinationExt            Extension ('append' or 'set')
     * @param  string  $destinationStructure      "doc-root" or "image-roots"
     * @param  string  $webExpressContentDirAbs
     * @param  ImageRoots  $imageRoots                An image roots object
     *
     * @return string|false  Returns path to source, if found. If not - or a path is not sane, false is returned
     */
    public static function findSource($destination, $destinationFolder, $destinationExt, $destinationStructure, $webExpressContentDirAbs, $imageRoots)
    {

        try {

            if ($destinationStructure == 'doc-root') {
                // Check that destination path is sane and inside document root
                // --------------------------
                $destination = SanityCheck::absPathIsInDocRoot($destination);
            } else {
                // The following will fail if path contains directory traversal. TODO: Is that ok?
                $destination = SanityCheck::absPath($destination);
            }

        } catch (SanityException $e) {
            return false;
        }

        if ($destinationFolder == 'mingled') {
            $result = self::findSourceMingled($destination, $destinationExt, $destinationStructure);
            if ($result === false) {
                $result = self::findSourceSeparate($destination, $destinationStructure, $webExpressContentDirAbs, $imageRoots);
            }
            return $result;
        } else {
            return self::findSourceSeparate($destination, $destinationStructure, $webExpressContentDirAbs, $imageRoots);
        }
    }

    /**
     *
     * @param  string  $source  Path to source file
     * @param  string  $logDir  The folder where log files are kept
     *
     * @return string|false   Returns computed filename of log - or false if a path is not sane
     *
     */
    public static function getLogFilename($source, $logDir)
    {
        try {

            // Check that source path is sane and inside document root
            // -------------------------------------------------------
            $source = SanityCheck::absPathIsInDocRoot($source);


            // Check that log path is sane and inside document root
            // -------------------------------------------------------
            $logDir = SanityCheck::absPathIsInDocRoot($logDir);


            // Compute and check log path
            // --------------------------
            $logDirForConversions = $logDir .= '/conversions';

            // We store relative to document root.
            // "Eat" the left part off the source parameter which contains the document root.
            // and also eat the slash (+1)

            $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
            $sourceRel = substr($source, strlen($docRoot) + 1);
            $logFileName = $logDir . '/doc-root/' . $sourceRel . '.md';
            SanityCheck::absPathIsInDocRoot($logFileName);

        } catch (SanityException $e) {
            return false;
        }
        return $logFileName;

    }

    /**
     * Create the directory for log files and put a .htaccess file into it, which prevents
     * it to be viewed from the outside (not that it contains any sensitive information btw, but for good measure).
     *
     * @param  string  $logDir  The folder where log files are kept
     *
     * @return boolean  Whether it was created successfully or not.
     *
     */
    private static function createLogDir($logDir)
    {
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
            @chmod($logDir, 0775);
            @file_put_contents(rtrim($logDir . '/') . '/.htaccess', <<<APACHE
<IfModule mod_authz_core.c>
Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
Order deny,allow
Deny from all
</IfModule>
APACHE
            );
            @chmod($logDir . '/.htaccess', 0664);
        }
        return is_dir($logDir);
    }

    /**
     * Saves the log file corresponding to a conversion.
     *
     * @param  string  $source   Path to the source file that was converted
     * @param  string  $logDir   The folder where log files are kept
     * @param  string  $text     Content of the log file
     * @param  string  $msgTop   A message that is printed before the conversion log (containing version info)
     *
     *
     */
    private static function saveLog($source, $logDir, $text, $msgTop)
    {

        if (!file_exists($logDir)) {
            self::createLogDir($logDir);
        }

        $text = preg_replace('#' . preg_quote($_SERVER["DOCUMENT_ROOT"]) . '#', '[doc-root]', $text);

        // TODO: Put version number somewhere else. Ie \WebPExpress\VersionNumber::version
        $text = 'WebP Express 0.25.5. ' . $msgTop . ', ' . date("Y-m-d H:i:s") . "\n\r\n\r" . $text;

        $logFile = self::getLogFilename($source, $logDir);

        if ($logFile === false) {
            return;
        }

        $logFolder = @dirname($logFile);
        if (!@file_exists($logFolder)) {
            mkdir($logFolder, 0777, true);
        }
        if (@file_exists($logFolder)) {
            file_put_contents($logFile, $text);
        }
    }

    /**
     * Trigger an actual conversion with webp-convert.
     *
     * PS: To convert with a specific converter, set it in the $converter param.
     *
     * @param  string  $source          Full path to the source file that was converted.
     * @param  string  $destination     Full path to the destination file (may exist or not).
     * @param  array   $convertOptions  Conversion options.
     * @param  string  $logDir          The folder where log files are kept or null for no logging
     * @param  string  $converter       (optional) Set it to convert with a specific converter.
     */
    public static function convert($source, $destination, $convertOptions, $logDir = null, $converter = null) {
        include_once __DIR__ . '/../../vendor/autoload.php';

        // At this point, everything has already been checked for sanity. But for good meassure, lets
        // check the most important parts again. This is after all a public method.
        // ------------------------------------------------------------------
        try {

            // Check that source path is sane, exists, is a file and is inside document root
            // -------------------------------------------------------
            $source = SanityCheck::absPathExistsAndIsFileInDocRoot($source);


            // Check that destination path is sane and is inside document root
            // -------------------------------------------------------
            $destination = SanityCheck::absPathIsInDocRoot($destination);
            $destination = SanityCheck::pregMatch('#\.webp$#', $destination, 'Destination does not end with .webp');


            // Check that log path is sane and inside document root
            // -------------------------------------------------------
            if (!is_null($logDir)) {
                $logDir = SanityCheck::absPathIsInDocRoot($logDir);
            }


            // PS: No need to check $logMsgTop. Log files are markdown and stored as ".md". They can do no harm.

        } catch (SanityException $e) {
            return [
                'success' => false,
                'msg' => $e->getMessage(),
                'log' => '',
            ];
        }

        $success = false;
        $msg = '';
        $logger = new BufferLogger();
        try {
            if (!is_null($converter)) {
            //if (isset($convertOptions['converter'])) {
                //print_r($convertOptions);exit;
                $logger->logLn('Converter set to: ' . $converter);
                $logger->logLn('');
                $converter = ConverterFactory::makeConverter($converter, $source, $destination, $convertOptions, $logger);
                $converter->doConvert();
            } else {
//error_log('options:' . print_r(json_encode($convertOptions,JSON_PRETTY_PRINT), true));
                WebPConvert::convert($source, $destination, $convertOptions, $logger);
            }
            $success = true;
        } catch (\WebpConvert\Exceptions\WebPConvertException $e) {
            $msg = $e->getMessage();
        } catch (\Exception $e) {
            //$msg = 'An exception was thrown!';
            $msg = $e->getMessage();
        } catch (\Throwable $e) {
            //Executed only in PHP 7 and 8, will not match in PHP 5
            $msg = $e->getMessage();
        }

        if (!is_null($logDir)) {
            self::saveLog($source, $logDir, $logger->getMarkDown("\n\r"), 'Conversion triggered using bulk conversion');
        }

        return [
            'success' => $success,
            'msg' => $msg,
            'log' => $logger->getMarkDown("\n"),
        ];

    }

    /**
     *  Serve a converted file (if it does not already exist, a conversion is triggered - all handled in webp-convert).
     *
     */
    public static function serveConverted($source, $destination, $serveOptions, $logDir = null, $logMsgTop = '')
    {
        include_once __DIR__ . '/../../vendor/autoload.php';

        // At this point, everything has already been checked for sanity. But for good meassure, lets
        // check again. This is after all a public method.
        // ---------------------------------------------
        try {

            // Check that source path is sane, exists, is a file.
            // -------------------------------------------------------
            //$source = SanityCheck::absPathExistsAndIsFileInDocRoot($source);
            $source = SanityCheck::absPathExistsAndIsFile($source);


            // Check that destination path is sane
            // -------------------------------------------------------
            //$destination = SanityCheck::absPathIsInDocRoot($destination);
            $destination = SanityCheck::absPath($destination);
            $destination = SanityCheck::pregMatch('#\.webp$#', $destination, 'Destination does not end with .webp');


            // Check that log path is sane
            // -------------------------------------------------------
            //$logDir = SanityCheck::absPathIsInDocRoot($logDir);
            if ($logDir != null) {
                $logDir = SanityCheck::absPath($logDir);
            }

            // PS: No need to check $logMsgTop. Log files are markdown and stored as ".md". They can do no harm.

        } catch (SanityException $e) {
            $msg = $e->getMessage();
            echo $msg;
            header('X-WebP-Express-Error: ' . $msg, true);
            // TODO: error_log() ?
            exit;
        }

        $convertLogger = new BufferLogger();
        WebPConvert::serveConverted($source, $destination, $serveOptions, null, $convertLogger);
        if (!is_null($logDir)) {
            $convertLog = $convertLogger->getMarkDown("\n\r");
            if ($convertLog != '') {
                self::saveLog($source, $logDir, $convertLog, $logMsgTop);
            }
        }
    }
}
