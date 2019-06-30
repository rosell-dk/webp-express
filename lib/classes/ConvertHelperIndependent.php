<?php

/*
This class is made to be dependent only on a few WebPExpress classes, and must be kept like that.
It is used by webp-on-demand.php, which does not register an auto loader. It is also used for bulk conversion.
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


    /**
     * Get destination path corresponding to the source path given (and some configurations)
     *
     *  If for example Operation mode is set to "mingled" and extension is set to "Append .webp",
     *  the result of finding the destination path that corresponds to "/path/to/logo.jpg" will be "/path/to/logo.jpg.webp".
     *
     * @param  string  $source                     Path to source file
     * @param  string  $destinationFolder          'mingled' or 'separate'
     * @param  string  $destinationExt             Extension ('append' or 'set')
     * @param  string  $webExpressContentDirAbs
     * @param  string  $uploadDirAbs
     *
     * @return string|false   Returns path to destination corresponding to source, or false on failure
     */
    public static function getDestination($source, $destinationFolder, $destinationExt, $webExpressContentDirAbs, $uploadDirAbs)
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
                if ($destinationExt == 'append') {
                    // TODO: make this check work with symlinks
                    //$destination = SanityCheck::absPathIsInDocRoot($source . '.webp');
                    $destination = $source . '.webp';
                } else {
                    $destination = preg_replace('/\\.(jpe?g|png)$/', '', $source) . '.webp';
                    // TODO: make this check work with symlinks
                    //$destination = SanityCheck::absPathIsInDocRoot($source . '.webp');
                    $destination = $source . '.webp';
                }
            } else {
                $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
                $imageRoot = $webExpressContentDirAbs . '/webp-images';

                // TODO: make this check work with symlinks
                //SanityCheck::absPathIsInDocRoot($imageRoot);

                $sourceRel = substr($source, strlen($docRoot) + 1);
                $destination = $imageRoot . '/doc-root/' . $sourceRel . '.webp';

                // TODO: make this check work with symlinks
                //$destination = SanityCheck::absPathIsInDocRoot($destination);
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
     * @param  string  $destination               Path to destination file (does not have to exist)
     * @param  string  $webExpressContentDirAbs
     *
     * @return string|false   Returns path to source, if found. If not - or a path is not sane, false is returned
     */
    private static function findSourceSeparate($destination, $webExpressContentDirAbs)
    {
        try {

            // Check that destination path is sane and inside document root
            // --------------------------
            $destination = SanityCheck::absPathIsInDocRoot($destination);


            // Check that calculated image root is sane and inside document root
            // --------------------------
            $imageRoot = SanityCheck::absPathIsInDocRoot($webExpressContentDirAbs . '/webp-images/doc-root');


            // Calculate source and check that it is sane and exists
            // -----------------------------------------------------

            // TODO: This does not work on Windows yet.
            // NOTE: WE CANNOT DO AS WITH sourceIsInsideDocRoot, because it relies on realpath, which only translates EXISTING paths.
            //       $destination does not exist yet, when this method is called from webp-realizer.php
            if (strpos($destination, $imageRoot . '/') === 0) {

                // "Eat" the left part off the $destination parameter. $destination is for example:
                // "/var/www/webp-express-tests/we0/wp-content-moved/webp-express/webp-images/doc-root/wordpress/uploads-moved/2018/12/tegning5-300x265.jpg.webp"
                // We also eat the slash (+1)
                $sourceRel = substr($destination, strlen($imageRoot) + 1);

                $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
                $source = $docRoot . '/' . $sourceRel;
                $source =  preg_replace('/\\.(webp)$/', '', $source);
            } else {
                return false;
            }

            $source = SanityCheck::absPathExistsAndIsFileInDocRoot($source);

        } catch (SanityException $e) {
            return false;
        }

        return $source;
    }

    /**
     * Find source corresponding to destination (mingled)
     * Returns false if not found. Otherwise returns path to source
     *
     * @param  string  $destination      Path to destination file (does not have to exist)
     * @param  string  $destinationExt   Extension ('append' or 'set')
     *
     * @return string|false   Returns path to source, if found. If not - or a path is not sane, false is returned
     */
    private static function findSourceMingled($destination, $destinationExt)
    {
        try {

            // Check that destination path is sane and inside document root
            // --------------------------
            $destination = SanityCheck::absPathIsInDocRoot($destination);


            // Calculate source and check that it is sane and exists
            // -----------------------------------------------------
            if ($destinationExt == 'append') {
                $source =  preg_replace('/\\.(webp)$/', '', $destination);
            } else {
                $source =  preg_replace('#\\.webp$#', '.jpg', $destination);
                if (!@file_exists($source)) {
                    $source =  preg_replace('/\\.webp$/', '.jpeg', $destination);
                }
                if (!@file_exists($source)) {
                    $source =  preg_replace('/\\.webp$/', '.png', $destination);
                }
            }
            $source = SanityCheck::absPathExistsAndIsFileInDocRoot($source);


        } catch (SanityException $e) {
            return false;
        }

        return $source;
    }

    /**
     * Get source from destination (and some configurations)
     * Returns false if not found. Otherwise returns path to source
     *
     * @param  string  $destination               Path to destination file (does not have to exist)
     * @param  string  $destinationFolder         'mingled' or 'separate'
     * @param  string  $destinationExt            Extension ('append' or 'set')
     * @param  string  $webExpressContentDirAbs
     *
     * @return string|false  Returns path to source, if found. If not - or a path is not sane, false is returned
     */
    public static function findSource($destination, $destinationFolder, $destinationExt, $webExpressContentDirAbs)
    {
        try {

            // Check that destination path is sane and inside document root
            // --------------------------
            $destination = SanityCheck::absPathIsInDocRoot($destination);

        } catch (SanityException $e) {
            return false;
        }

        if ($destinationFolder == 'mingled') {
            $result = self::findSourceMingled($destination, $destinationExt);
            if ($result === false) {
                $result = self::findSourceSeparate($destination, $webExpressContentDirAbs);
            }
            return $result;
        } else {
            return self::findSourceSeparate($destination, $webExpressContentDirAbs);
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

        $text = 'WebP Express 0.14.21. ' . $msgTop . ', ' . date("Y-m-d H:i:s") . "\n\r\n\r" . $text;

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
     * @param  string  $source          Path to the source file that was converted.
     * @param  string  $destination     Path to the destination file (may exist or not).
     * @param  array   $convertOptions  Conversion options.
     * @param  string  $logDir          The folder where log files are kept.
     * @param  string  $converter       (optional) Set it to convert with a specific converter.
     */
    public static function convert($source, $destination, $convertOptions, $logDir, $converter = null) {
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
            $logDir = SanityCheck::absPathIsInDocRoot($logDir);


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
                WebPConvert::convert($source, $destination, $convertOptions, $logger);
            }
            $success = true;
        } catch (\WebpConvert\Exceptions\WebPConvertException $e) {
            $msg = $e->getMessage();
        } catch (\Exception $e) {
            $msg = 'An exception was thrown!';
        } catch (Throwable $e) {
            //Executed only in PHP 7, will not match in PHP 5
            //$msg = $e->getMessage();
            //$msg = 'oh no';
        }

        self::saveLog($source, $logDir, $logger->getMarkDown("\n\r"), 'Conversion triggered using bulk conversion');

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
    public static function serveConverted($source, $destination, $serveOptions, $logDir, $logMsgTop = '')
    {
        include_once __DIR__ . '/../../vendor/autoload.php';

        // At this point, everything has already been checked for sanity. But for good meassure, lets
        // check again. This is after all a public method.
        // ---------------------------------------------
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
            $logDir = SanityCheck::absPathIsInDocRoot($logDir);


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
        $convertLog = $convertLogger->getMarkDown("\n\r");
        if ($convertLog != '') {
            self::saveLog($source, $logDir, $convertLog, $logMsgTop);
        }

    }
}
