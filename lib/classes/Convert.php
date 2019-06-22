<?php

namespace WebPExpress;

use \WebPExpress\ConvertHelperIndependent;
use \WebPExpress\Config;
use \WebPExpress\ConvertersHelper;
use \WebPExpress\Sanitize;
use \WebPExpress\Validate;
use \WebPExpress\ValidateException;

class Convert
{

    public static function getDestination($source, &$config = null)
    {
        if (is_null($config)) {
            $config = Config::loadConfigAndFix();
        }
        return ConvertHelperIndependent::getDestination(
            $source,
            $config['destination-folder'],
            $config['destination-extension'],
            Paths::getWebPExpressContentDirAbs(),
            Paths::getUploadDirAbs()
        );
    }

    public static function convertFile($source, $config = null, $convertOptions = null, $converter = null)
    {
        // PS: No need to check mime type as the WebPConvert library does that (it only accepts image/jpeg and image/png)
        $source = ConvertHelperIndependent::sanitizeAbsFilePath($source);

        if (is_null($config)) {
            $config = Config::loadConfigAndFix();
        }
        if (is_null($convertOptions)) {
            $convertOptions = Config::generateWodOptionsFromConfigObj($config)['webp-convert']['convert'];
        }
        /*
        if (isset($config['converter'])) {
            $options['convert']['converter'] = $config['converter'];
        }*/

        $destination = self::getDestination($source, $config);
        $destination = ConvertHelperIndependent::sanitizeAbsFilePath($destination);

        $logDir = Paths::getWebPExpressContentDirAbs() . '/log';

        $result = ConvertHelperIndependent::convert($source, $destination, $convertOptions, $logDir, $converter);

        //$result['destination'] = $destination;
        if ($result['success'] === true) {
            $result['filesize-original'] = @filesize($source);
            $result['filesize-webp'] = @filesize($destination);
        }
        return $result;
    }

    public static function findSource($destination, &$config = null)
    {
        $destination = ConvertHelperIndependent::sanitizeAbsFilePath($destination);
        if (is_null($config)) {
            $config = Config::loadConfigAndFix();
        }
        return ConvertHelperIndependent::findSource(
            $destination,
            $config['destination-folder'],
            $config['destination-extension'],
            Paths::getWebPExpressContentDirAbs()
        );
    }

    public static function processAjaxConvertFile()
    {

        if (!check_ajax_referer('webpexpress-ajax-convert-nonce', 'nonce', false)) {
            wp_send_json_error('Invalid security nonce (it has probably expired - try refreshing)');
            wp_die();
        }

        // Validate input
        // ---------------------------
        try {
            // validate "filename"
            $validating = '"filename" argument';
            Validate::postHasKey('filename');
            $filename = sanitize_text_field($_POST['filename']);
            Validate::absPathLooksSaneExistsAndIsNotDir($filename);


            // validate converter id
            // ---------------------
            $validating = '"converter" argument';
            if (isset($_POST['converter'])) {
                $converterId = sanitize_text_field($_POST['converter']);
                Validate::isConverterId($converterId);
            }


            // validate "config-overrides"
            // ---------------------------
            $validating = '"config-overrides" argument';
            if (isset($_POST['config-overrides'])) {
                $configOverridesJSON = Sanitize::removeNUL($_POST['config-overrides']);
                $configOverridesJSON = preg_replace('/\\\\"/', '"', $configOverridesJSON); // We got crazy encoding, perhaps by jQuery. This cleans it up

                Validate::isJSONObject($configOverridesJSON, $configOverridesJSON);
                $configOverrides = json_decode($configOverridesJSON, true);

                // PS: We do not need to validate the overrides.
                // webp-convert checks all options. Nothing can be passed to webp-convert which causes harm.
            }

        } catch (ValidateException $e) {
            wp_send_json_error('failed validating ' . $validating . ': '. $e->getMessage());
            wp_die();
        }


        // Input has been processed, now lets get to work!
        // -----------------------------------------------
        if (isset($configOverrides)) {
            $config = Config::loadConfigAndFix();


            // convert using specific converter
            if (!is_null($converterId)) {

                // Merge in the config-overrides (config-overrides only have effect when using a specific converter)
                $config = array_merge($config, $configOverrides);

                $converter = ConvertersHelper::getConverterById($config, $converterId);
                if ($converter === false) {
                    wp_send_json_error('Converter could not be loaded');
                    wp_die();
                }

                // the converter options stored in config.json is not precisely the same as the ones
                // we send to webp-convert.
                // We need to "regenerate" webp-convert options in order to use the ones specified in the config-overrides
                // And we need to merge the general options (such as quality etc) into the option for the specific converter

                $generalWebpConvertOptions = Config::generateWodOptionsFromConfigObj($config)['webp-convert']['convert'];
                $converterSpecificWebpConvertOptions = $converter['options'];

                $webpConvertOptions = array_merge($generalWebpConvertOptions, $converterSpecificWebpConvertOptions);
                unset($webpConvertOptions['converters']);

                // what is this? - I forgot why!
                //$config = array_merge($config, $converter['options']);
                $result = self::convertFile($filename, $config, $webpConvertOptions, $converterId);

            } else {
                $result = self::convertFile($filename, $config);
            }
        } else {
            $result = self::convertFile($filename);
        }

        echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
        wp_die();
    }

}
