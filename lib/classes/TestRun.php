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

        //echo '<pre>' . print_r($options, true) . '</pre>';
        foreach ($options['converters'] as $converter) {
            $converterId = $converter['converter'];
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
                $converterInstance->doConvert();
                $workingConverters[] = $converterId;
            } catch (\Exception $e) {
                //echo $e->getMessage() . '<br>';
                $errors[$converterId] = $e->getMessage();
            }
        }
        //print_r($errors);

        // cache the result
        self::$converterStatus = [
            'workingConverters' => $workingConverters,
            'errors' => $errors
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
