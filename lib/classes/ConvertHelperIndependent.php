<?php

/*
This class is made to be independent of other classes, and must be kept like that.
It is used by webp-on-demand.php, which does not register an auto loader. It is also used for bulk conversion.
*/
namespace WebPExpress;

use \WebPConvert\WebPConvert;
use \WebPConvert\Convert\ConverterFactory;
use \WebPConvert\Loggers\BufferLogger;
use \WebPExpress\FileHelper;
use WebPConvert\Exceptions\WebPConvertException;


class ConvertHelperIndependent
{

    public static function storeMingledOrNot($source, $destinationFolder, $uploadDirAbs)
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

    /*
    public static function getDestinationFolder($sourceDir, $destinationFolder, $destinationExt, $webExpressContentDirAbs, $uploadDirAbs)
    {
        if (self::storeMingledOrNot($sourceDir, $destinationFolder, $uploadDirAbs)) {
            return $sourceDir;
        } else {

            $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
            $imageRoot = $webExpressContentDirAbs . '/webp-images';

            // Check if source dir is residing inside document root.
            // (it is, if path starts with document root + '/')
            if (substr($sourceDir, 0, strlen($docRoot) + 1) === $docRoot . '/') {

                // We store relative to document root.
                // "Eat" the left part off the source parameter which contains the document root.
                // and also eat the slash (+1)
                $sourceDirRel = substr($sourceDir, strlen($docRoot) + 1);
                return $imageRoot . '/doc-root/' . $sourceDirRel;
            } else {
                // Source file is residing outside document root.
                // we must add complete path to structure
                return $imageRoot . '/abs' . $sourceDir;
            }
        }
    }*/

    /**
     *  Get destination from source (and some configurations)
     */
    public static function getDestination($source, $destinationFolder, $destinationExt, $webExpressContentDirAbs, $uploadDirAbs)
    {
        if (self::storeMingledOrNot($source, $destinationFolder, $uploadDirAbs)) {
            if ($destinationExt == 'append') {
                return $source . '.webp';
            } else {
                return preg_replace('/\\.(jpe?g|png)$/', '', $source) . '.webp';
            }
        } else {

            $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
            $imageRoot = $webExpressContentDirAbs . '/webp-images';

            // Check if source is residing inside document root.
            // (it is, if path starts with document root + '/')
            if (self::sourceIsInsideDocRoot($source, $docRoot) ) {

                // We store relative to document root.
                // "Eat" the left part off the source parameter which contains the document root.
                // and also eat the slash (+1)
                $sourceRel = substr($source, strlen($docRoot) + 1);
                return $imageRoot . '/doc-root/' . $sourceRel . '.webp';
            } else {
                // Source file is residing outside document root.
                // we must add complete path to structure
                return $imageRoot . '/abs' . $source . '.webp';
            }
        }
    }

    /**
     *  Find source corresponding to destination, separate
     *  We can rely on destinationExt being "append" for separate
     *  Returns false if not found. Otherwise returns path to source
     */
    private static function findSourceSeparate($destination, $webExpressContentDirAbs)
    {
        $imageRoot = $webExpressContentDirAbs . '/webp-images';

        // Check if destination is residing inside "doc-root" folder
        // TODO: This does not work on Windows yet.
        // NOTE: WE CANNOT DO AS WITH sourceIsInsideDocRoot, because it relies on realpath, which only translates EXISTING paths.
        //       $destination does not exist yet, when this method is called from webp-realizer.php
        if (strpos($destination, $imageRoot . '/doc-root/') === 0) {

            $imageRoot .= '/doc-root';
            // "Eat" the left part off the $destination parameter. $destination is for example:
            // "/var/www/webp-express-tests/we0/wp-content-moved/webp-express/webp-images/doc-root/wordpress/uploads-moved/2018/12/tegning5-300x265.jpg.webp"
            // We also eat the slash (+1)
            $sourceRel = substr($destination, strlen($imageRoot) + 1);

            $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');
            $source = $docRoot . '/' . $sourceRel;
            $source =  preg_replace('/\\.(webp)$/', '', $source);
        } else {
            $imageRoot .= '/abs';
            $sourceRel = substr($destination, strlen($imageRoot) + 1);
            $source = $sourceRel;
            $source =  preg_replace('/\\.(webp)$/', '', $source);
        }
        if (!@file_exists($source)) {
            return false;
        }
        return $source;
    }

    /**
     *  Find source corresponding to destination (mingled)
     *  Returns false if not found. Otherwise returns path to source
     */
    private static function findSourceMingled($destination, $destinationExt)
    {
        global $options;
        global $destination;
        if ($destinationExt == 'append') {
            $source =  preg_replace('/\\.(webp)$/', '', $destination);
        } else {
            $source =  preg_replace('/\\.webp$/', '.jpg', $destination);
            if (!@file_exists($source)) {
                $source =  preg_replace('/\\.webp$/', '.jpeg', $destination);
            }
            if (!@file_exists($source)) {
                $source =  preg_replace('/\\.webp$/', '.png', $destination);
            }
        }
        if (!@file_exists($source)) {
            return false;
        }
        return $source;
    }

    /**
     *  Get source from destination (and some configurations)
     *  Returns false if not found. Otherwise returns path to source
     */
    public static function findSource($destination, $destinationFolder, $destinationExt, $webExpressContentDirAbs)
    {
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

    public static function getLogFilename($source, $logDir)
    {
        // Calculate path for log file
        // ---------------------------
        $logDir .= '/conversions';
        $docRoot = rtrim(realpath($_SERVER["DOCUMENT_ROOT"]), '/');

        // Check if source is residing inside document root.
        // (it is, if path starts with document root + '/')
        if (self::sourceIsInsideDocRoot($source, $docRoot) ) {

            // We store relative to document root.
            // "Eat" the left part off the source parameter which contains the document root.
            // and also eat the slash (+1)
            $sourceRel = substr($source, strlen($docRoot) + 1);
            return $logDir . '/doc-root/' . $sourceRel . '.md';
        } else {
            // Source file is residing outside document root.
            // we must add complete path to structure
            return $logDir . '/abs' . $source . '.md';
        }

    }

    public static function createLogDir($logDir)
    {
        $logDir = Paths::getLogDirAbs();

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

    public static function saveLog($source, $logDir, $text, $msgTop)
    {
        if (!file_exists($logDir)) {
            self::createLogDir($logDir);
        }

        $text = preg_replace('#' . preg_quote($_SERVER["DOCUMENT_ROOT"]) . '#', '[doc-root]', $text);

        $text = 'WebP Express 0.14.4. ' . $msgTop . ', ' . date("Y-m-d H:i:s") . "\n\r\n\r" . $text;

        $logFile = self::getLogFilename($source, $logDir);

        $logFolder = @dirname($logFile);
        if (!@file_exists($logFolder)) {
            mkdir($logFolder, 0777, true);
        }
        if (@file_exists($logFolder)) {
            file_put_contents($logFile, $text);
        }
    }

    /**
     *  To convert with a specific converter, set it in the $converter param.
     */
    public static function convert($source, $destination, $convertOptions, $logDir, $converter = null) {
        include_once __DIR__ . '/../../vendor/autoload.php';

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
            'log' => $logger->getHtml(),
        ];

    }

    public static function serveConverted($source, $destination, $serveOptions, $logDir, $logMsgTop = '')
    {
        include_once __DIR__ . '/../../vendor/autoload.php';

        // TODO: error_log()
        //ini_set('display_errors', 0);
        //error_reporting(0);

        //echo '<pre>' . print_r($serveOptions, true) . '</pre>'; exit;

        $convertLogger = new BufferLogger();
        WebPConvert::serveConverted($source, $destination, $serveOptions, null, $convertLogger);
        $convertLog = $convertLogger->getMarkDown("\n\r");
        if ($convertLog != '') {
            self::saveLog($source, $logDir, $convertLog, $logMsgTop);
        }

    }
}
