<?php
namespace WebPConvertAndServe;

use WebPConvert\WebPConvert;
use WebPConvertAndServe\BufferLogger;
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

        $bufferLogger = new BufferLogger();

        try {
            $success = WebPConvert::convert($source, $destination, $options, $bufferLogger);

            if ($success) {
                $status = 'Success';
                $msg = 'Success';
            } else {
                $status = 'Failure (no converters are operational)';
                $msg = 'No converters are operational';
            }
        } catch (\WebPConvert\Exceptions\InvalidFileExtensionException $e) {
            $criticalFail = true;
            $status = 'Failure (invalid file extension)';
            $msg = $e->getMessage();
        } catch (\WebPConvert\Exceptions\TargetNotFoundException $e) {
            $criticalFail = true;
            $status = 'Failure (target file not found)';
            $msg = $e->getMessage();
        } catch (\WebPConvert\Converters\Exceptions\ConverterFailedException $e) {
            // No converters could convert the image. At least one converter failed, even though it appears to be operational
            $status = 'Failure (no converters could convert the image)';
            $msg = $e->getMessage();
        } catch (\WebPConvert\Converters\Exceptions\ConversionDeclinedException $e) {
            // (no converters could convert the image. At least one converter declined
            $status = 'Failure (no converters could/wanted to convert the image)';
            $msg = $e->getMessage();
        } catch (\WebPConvert\Exceptions\ConverterNotFoundException $e) {
            $status = 'Failure (a converter was not found!)';
            $msg = $e->getMessage();
        } catch (\WebPConvert\Exceptions\CreateDestinationFileException $e) {
            $status = 'Failure (cannot create destination file)';
            $msg = $e->getMessage();
        } catch (\WebPConvert\Exceptions\CreateDestinationFolderException $e) {
            $status = 'Failure (cannot create destination folder)';
            $msg = $e->getMessage();
        } catch (\Exception $e) {
            $status = 'Failure (an unanticipated exception was thrown)';
            $msg = $e->getMessage();
        }

        $optionsForPrint = [];
        foreach (self::getPrintableOptions($options) as $optionName => $optionValue) {
            if ($optionName == 'converters') {
                $converterNames = [];
                $extraConvertOptions = [];
                foreach ($optionValue as $converter) {
                    if (is_array($converter)) {
                        $converterNames[] = $converter['converter'];
                        if (isset($converter['options'])) {
                            $extraConvertOptions[$converter['converter']] = $converter['options'];
                        }
                    } else {
                        $converterNames[] = $converter;
                    }
                }
                $optionsForPrint[] = 'converters:' . implode(',', $converterNames);
                foreach ($extraConvertOptions as $converter => $extraOptions) {
                    $opt = [];
                    foreach ($extraOptions as $oName => $oValue) {
                        $opt[] = $oName . ':"' . $oValue . '"';
                    }
                    $optionsForPrint[] = $converter . ' options:(' . implode($opt, ', ') . ')';
                }
            } else {
                $optionsForPrint[] = $optionName . ':' . $optionValue ;
            }

        }

        header('X-WebP-Convert-And-Serve-Options:' . implode('. ', $optionsForPrint));

        header('X-WebP-Convert-And-Serve-Status: ' . $status);

        // Next line is commented out, because we need to be absolute sure that the details does not violate header syntax
        // We could either try to filter it, or we could change WebPConvert, such that it only provides safe texts.
        // header('X-WebP-Convert-And-Serve-Details: ' . $bufferLogger->getText());

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
                    self::serveErrorMessageImage($status . '. ' . $msg);
                    break;
                case WebPConvertAndServe::$REPORT:
                    echo '<h1>' . $status . '</h1>';
                    echo $msg;
                    echo '<p>This is how conversion process went:</p>' . $bufferLogger->getHtml();
                    break;
            }
            return $action;
        }
    }

    /* Hides sensitive options */
    private static function getPrintableOptions($options)
    {

        $printable_options = [];

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
        }
        return $printable_options;
    }

    public static function convertAndReport($source, $destination, $options)
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 'On');

        echo '<html><style>td {vertical-align: top} table {color: #666}</style>';
        echo '<body><table>';
        echo '<tr><td><i>source:</i></td><td>' . $source . '</td></tr>';
        echo '<tr><td><i>destination:</i></td><td>' . $destination . '<td></tr>';

        echo '<tr><td><i>options:</i></td><td>' . print_r(self::getPrintableOptions($options), true) . '</td></tr>';
        echo '</table>';

        // TODO:
        // We could display warning if unknown options are set
        // but that requires that WebPConvert also describes its general options

        echo '<br>';

        try {
            $echoLogger = new \WebPConvert\Loggers\EchoLogger();
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
