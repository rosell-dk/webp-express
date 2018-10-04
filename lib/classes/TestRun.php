<?php

namespace WebPExpress;

include_once "Config.php";
use \WebPExpress\Config;

include_once __DIR__ . '/../classes/ConvertersHelper.php';
use \WebPExpress\ConvertersHelper;

include_once "Paths.php";
use \WebPExpress\Paths;

include_once "FileHelper.php";
use \WebPExpress\FileHelper;

include_once __DIR__ . '/../../vendor/autoload.php';
use \WebPConvert\Converters\ConverterHelper;

/**
 *
 */

class TestRun
{


    /**
     *  Get an array of working converters OR false, if tests cannot be made
     */
    public static function getConverterStatus() {
        $source = Paths::getWebPExpressPluginDirAbs() . '/test/small-q61.jpg';
        $destination = Paths::getUploadDirAbs() . '/webp-express-test-conversion.webp';
        if (!FileHelper::canCreateFile($destination)) {
            $destination = Paths::getWPContentDirAbs() . '/webp-express-test-conversion.webp';
        }
        if (!FileHelper::canCreateFile($destination)) {
            return false;
        }
        $workingConverters = [];
        $errors = [];

        // We need wod options.
        // But we cannot simply use loadWodOptions - because that would leave out the deactivated
        // converters. And we need to test all converters - even the deactivated ones.
        // So we load config, set "deactivated" to false, and generate Wod options from the config
        $config = Config::loadConfig();
        if ((!$config) || (!isset($config['converters'])) || (count($config['converters']) == 0)) {
            $config = [
                'converters' => ConvertersHelper::$defaultConverters
            ];
        } else {
            // set deactivated to false on all converters
            foreach($config['converters'] as &$converter) {
                $converter['deactivated'] = false;
            }

            // merge missing converters in
            $config['converters'] = ConvertersHelper::mergeConverters($config['converters'], ConvertersHelper::$defaultConverters);
//            echo '<pre>' . print_r($config, true) . '</pre>';

        }

        $options = Config::generateWodOptionsFromConfigObj($config);
        $options['converters'] = ConvertersHelper::normalize($options['converters']);

        //echo '<pre>' . print_r($options, true) . '</pre>';
        foreach ($options['converters'] as $converter) {
            $converterId = $converter['converter'];
            try {
                $converterOptions = array_merge($options, $converter['options']);
                unset($converterOptions['converters']);

                ConverterHelper::runConverter($converterId, $source, $destination, $converterOptions);
                $workingConverters[] = $converterId;
            } catch (\Exception $e) {
                //echo $e->getMessage() . '<br>';
                $errors[$converterId] = $e->getMessage();
            }
        }
        //print_r($errors);
        return [
            'workingConverters' => $workingConverters,
            'errors' => $errors
        ];
    }

    public static function isLocalQualityDetectionWorking() {
        $q = ConverterHelper::detectQualityOfJpg(
            Paths::getWebPExpressPluginDirAbs() . '/test/small-q61.jpg'
        );
        return ($q === 61);
    }
}
