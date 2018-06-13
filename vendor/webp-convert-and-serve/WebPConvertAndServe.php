<?php
namespace WebPConvertAndServe;

use WebPConvert\WebPConvert;
use WebPConvertAndServe\PathHelper;

class WebPConvertAndServe
{
    public static $CONVERTED_IMAGE = 1;
    public static $ORIGINAL = -1;
    public static $HTTP_404 = -2;
    public static $REPORT_AS_IMAGE = -3;
    public static $REPORT = -4;

    private static function serve404()
    {
        $protocol = isset($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : 'HTTP/1.0';
        header($protocol . " 404 Not Found");
    }

    private static function serveOriginal($source)
    {
        // Prevent caching image
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        $arr = explode('.', $source);
        $ext = array_pop($arr);
        switch (strtolower($ext)) {
            case 'jpg':
            case 'jpeg':
                header('Content-type: image/jpeg');
                break;
            case 'png':
                header('Content-type: image/png');
                break;
        }
        readfile($source);
    }

    private static function serveErrorMessageImage($msg)
    {
        // Generate image containing error message
        header('Content-type: image/gif');

        // Prevent caching image
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        $image = imagecreatetruecolor(620, 200);
        imagestring($image, 1, 5, 5, $msg, imagecolorallocate($image, 233, 214, 291));
        // echo imagewebp($image);
        echo imagegif($image);
        imagedestroy($image);
    }


    public static function convertAndServeImage($source, $destination, $options, $failAction, $criticalFailAction, $debug = false)
    {
        if ($debug) {
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        } else {
            ini_set('display_errors', 'Off');
        }

        $criticalFail = false;

        $success = false;
        try {
            $success = WebPConvert::convert($source, $destination, $options);
            if (!$success) {
                $msg = 'No converters are operational';
            }
        } catch (\WebPConvert\Exceptions\InvalidFileExtensionException $e) {
            $criticalFail = true;
            $msg = $e->getMessage();
        } catch (\WebPConvert\Exceptions\TargetNotFoundException $e) {
            $criticalFail = true;
            $msg = $e->getMessage();
        } catch (\Exception $e) {
            $msg = $e->getMessage();
        }

        if ($success) {
            header('Content-type: image/webp');
            // Should we add Content-Length header?
            // header('Content-Length: ' . filesize($file));
            readfile($destination);
            return self::$CONVERTED_IMAGE;
        } else {
            $action = ($criticalFail ? $criticalFailAction : $failAction);

            switch ($action) {
                case WebPConvertAndServe::$ORIGINAL:
                    self::serveOriginal($source);
                    break;
                case WebPConvertAndServe::$HTTP_404:
                    self::serve404();
                    break;
                case WebPConvertAndServe::$REPORT_AS_IMAGE:
                    self::serveErrorMessageImage($msg);
                    break;
                case WebPConvertAndServe::$REPORT:
                    echo $msg;
                    break;
            }
            return $action;
        }
    }

    public static function convertAndReport($source, $destination, $options)
    {
        echo '<i>source:</i> ' . $source . '<br>';
        echo '<i>destination:</i> ' . $destination . '<br>';
        echo '<i>options:</i> ' . print_r($options, true) . '<br>';
        echo '<br>';

        try {
            $success = WebPConvert::convert($source, $destination, $options);
        } catch (\Exception $e) {
            $success = false;

            $msg = $e->getMessage();

            echo '<b>' . $msg . '</b>';
            exit;
        }

        if ($success) {
            echo 'ok';
        } else {
            echo '<b>Conversion failed. None of the tried converters are operational</b>';
        }
    }
}
