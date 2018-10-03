<?php

namespace WebPExpress;

include_once "Config.php";
use \WebPExpress\Config;

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

    public static $localConverters = ['cwebp', 'imagick', 'gmagick', 'gd'];

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
        // Actually, it would be most correct to load wod options.
        // - because options might have different names in that file.
        // But it is currently not the case.
        // AND if we load wod options, we do not get the inactive converters,
        // but we want to test those as well.
        // so for now, we simply load the config
        //$options = Config::loadWodOptions();
        $options = Config::loadConfig();
        //print_r($options);
        if (!$options) {
            $options = [
                'converters' => ConverterHelper::$availableConverters
            ];
        }
        //echo '<pre>' . print_r($options, true) . '</pre>';
        foreach ($options['converters'] as $converter) {
            if (!isset($converter['converter'])) {
                $converter = ['converter' => $converter];
            }
            $converterId = $converter['converter'];

            if (!isset($converter['options'])) {
                $converter['options'] = [];
            }
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
