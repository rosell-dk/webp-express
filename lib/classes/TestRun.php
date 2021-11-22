<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\ConvertersHelper;
use \WebPExpress\Paths;
use \WebPExpress\FileHelper;

use \WebPConvert\Convert\ConverterFactory;
use \WebPConvert\Convert\Helpers\JpegQualityDetector;

include_once WEBPEXPRESS_PLUGIN_DIR . '/vendor/autoload.php';

/**
 *
 */

class TestRun
{


    public static $converterStatus = null; // to cache the result

    private static $warnings;

    public static function warningHandler($errno, $errstr, $errfile, $errline, $errcontext = null)
    {
        $errorTypes = [
            E_WARNING =>             "Warning",
            E_NOTICE =>              "Notice",
            E_STRICT =>              "Strict Notice",
            E_DEPRECATED =>          "Deprecated",
            E_USER_DEPRECATED =>     "User Deprecated",
        ];

        if (isset($errorTypes[$errno])) {
            $errType = $errorTypes[$errno];
        } else {
            $errType = "Warning ($errno)";
        }

        $msg = $errType . ': ' . $errstr . ' in ' . $errfile . ', line ' . $errline;
        self::$warnings[] = $msg;

        // suppress!
        return true;
    }
    /**
     *  Get a test result object OR false, if tests cannot be made.
     *
     *  @return object|false
     */
    public static function getConverterStatus() {
        //return false;

        // Is result cached?
        if (isset(self::$converterStatus)) {
            return self::$converterStatus;
        }
        $source = Paths::getWebPExpressPluginDirAbs() . '/test/small-q61.jpg';
        $destination = Paths::getUploadDirAbs() . '/webp-express-test-conversion.webp';
        if (!FileHelper::canCreateFile($destination)) {
            $destination = Paths::getContentDirAbs() . '/webp-express-test-conversion.webp';
        }
        if (!FileHelper::canCreateFile($destination)) {
            self::$converterStatus = false;     // // cache the result
            return false;
        }
        $workingConverters = [];
        $errors = [];
        self::$warnings = [];

        // We need wod options.
        // But we cannot simply use loadWodOptions - because that would leave out the deactivated
        // converters. And we need to test all converters - even the deactivated ones.
        // So we load config, set "deactivated" to false, and generate Wod options from the config
        $config = Config::loadConfigAndFix();

        // set deactivated to false on all converters
        foreach($config['converters'] as &$converter) {
            $converter['deactivated'] = false;
        }

        $options = Config::generateWodOptionsFromConfigObj($config);
        $options['converters'] = ConvertersHelper::normalize($options['webp-convert']['convert']['converters']);

        $previousErrorHandler = set_error_handler(
            array('\WebPExpress\TestRun', "warningHandler"),
            E_WARNING | E_USER_WARNING | E_NOTICE | E_USER_NOTICE
        );

        $warnings = [];
        //echo '<pre>' . print_r($options, true) . '</pre>';
        foreach ($options['converters'] as $converter) {
            $converterId = $converter['converter'];
            self::$warnings = [];
            try {
                $converterOptions = array_merge($options, $converter['options']);
                unset($converterOptions['converters']);

                //ConverterHelper::runConverter($converterId, $source, $destination, $converterOptions);
                $converterInstance = ConverterFactory::makeConverter(
                    $converterId,
                    $source,
                    $destination,
                    $converterOptions
                );
                // Note: We now suppress warnings.
                // WebPConvert logs warnings but purposefully does not stop them - warnings should generally not be
                // stopped. However, as these warnings are logged in conversion log, it is preferable not to make them
                // bubble here. #
                $converterInstance->doConvert();

                if (count(self::$warnings) > 0) {
                    $warnings[$converterId] = self::$warnings;
                }
                $workingConverters[] = $converterId;
            } catch (\Exception $e) {
                $errors[$converterId] = $e->getMessage();
            } catch (\Throwable $e) {
                $errors[$converterId] = $e->getMessage();
            }
        }

        restore_error_handler();
        //print_r($errors);

        // cache the result
        self::$converterStatus = [
            'workingConverters' => $workingConverters,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
        return self::$converterStatus;
    }


    public static $localQualityDetectionWorking = null; // to cache the result

    public static function isLocalQualityDetectionWorking() {
        if (isset(self::$localQualityDetectionWorking)) {
            return self::$localQualityDetectionWorking;
        } else {
            $q = JpegQualityDetector::detectQualityOfJpg(
                Paths::getWebPExpressPluginDirAbs() . '/test/small-q61.jpg'
            );
            self::$localQualityDetectionWorking = ($q === 61);
            return self::$localQualityDetectionWorking;
        }
    }
}
