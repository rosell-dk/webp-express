<?php
namespace WebPConvertAndServe;

use WebPConvert\WebPConvert;
use WebPConvertAndServe\PathHelper;
use WebPConvert\Converters\ConverterHelper;
//use WebPConvert\Loggers\EchoLogger;

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

        $echoLogger = null;
        if (class_exists('WebPConvert\Loggers\EchoLogger')) {
          $echoLogger = new \WebPConvert\Loggers\EchoLogger();
        }

        ob_start();
        try {
            $success = WebPConvert::convert($source, $destination, $options, $echoLogger);

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
        $conversionInsights = ob_get_contents();
        ob_end_clean();

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
                    echo '<h1>' . $msg . '</h1>';
                    if ($echoLogger) {
                      echo '<p>This is how conversion process went:</p>' . $conversionInsights;
                    }
                    break;
            }
            return $action;
        }
    }

    public static function convertAndReport($source, $destination, $options)
    {
        echo '<html><style>td {vertical-align: top} table {color: #666}</style>';
        echo '<body><table>';
        echo '<tr><td><i>source:</i></td><td>' . $source . '</td></tr>';
        echo '<tr><td><i>destination:</i></td><td>' . $destination . '<td></tr>';

        // Take care of not displaing sensitive converter options.
        // (psst: the is_callable check is needed in order to work with WebPConvert v1.0)

        if (is_callable('ConverterHelper', 'getClassNameOfConverter')) {

          $printable_options = $options;
          if (isset($printable_options['converters'])) {
            foreach ($printable_options['converters'] as &$converter) {
              if (is_array($converter)) {
                //echo '::' . $converter['converter'] . '<br>';
                $className = ConverterHelper::getClassNameOfConverter($converter['converter']);

                // (pstt: the isset check is needed in order to work with WebPConvert v1.0)
                if (isset($className::$extraOptions)) {
                  foreach ($className::$extraOptions as $extraOption) {
                    if ($extraOption['sensitive']) {
                      if (isset($converter['options'][$extraOption['name']])) {
                        $converter['options'][$extraOption['name']] = '*******';
                      }
                    }
                  }
                }
              }
            }
          }
          echo '<tr><td><i>options:</i></td><td>' . print_r($printable_options, true) . '</td></tr>';
        }
        echo '</table>';

        // TODO:
        // We could display warning if unknown options are set
        // but that requires that WebPConvert also describes its general options



        echo '<br>';

        try {
            $echoLogger = null;
            if (class_exists('WebPConvert\Loggers\EchoLogger')) {
              $echoLogger = new \WebPConvert\Loggers\EchoLogger();
            }
            $success = WebPConvert::convert($source, $destination, $options, $echoLogger);
        } catch (\Exception $e) {
            $success = false;

            $msg = $e->getMessage();

            echo '<b>' . $msg . '</b>';
            exit;
        }

        if ($success) {
            //echo 'ok';
        } else {
            echo '<b>Conversion failed. None of the tried converters are operational</b>';
        }
        echo '</body></html>';
    }
}
