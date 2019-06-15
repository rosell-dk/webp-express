<?php

/*
This class is made to be independent of other classes, and must be kept like that.
It is used by webp-on-demand.php, which does not register an auto loader. It is also used for bulk conversion.
*/
namespace WebPExpress;

use \WebPExpress\ConvertHelperIndependent;
use \WebPExpress\Config;
use \WebpExpress\ConvertersHelper;

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

        $filename = $_POST['filename'];

        if (isset($_POST['config-overrides'])) {
            $config = Config::loadConfigAndFix();

            // overrides
            $overrides = $_POST['config-overrides'];
            $overrides = preg_replace('/\\\\"/', '"', $overrides); // We got crazy encoding, perhaps by jQuery. This cleans it up
            $overrides = json_decode($overrides, true);

            $config = array_merge($config, $overrides);

            // single converter
            $converter = null;
            $convertOptions = null;
            if (isset($_POST['converter'])) {
                $converter = $_POST['converter'];

                // find converter
                $c = ConvertersHelper::getConverterById($config, $converter);
                if ($c !== false) {

                    $convertOptions = Config::generateWodOptionsFromConfigObj($config)['webp-convert']['convert'];
                    $convertOptions = array_merge($convertOptions, $c['options']);
                    unset($convertOptions['converters']);

                    $config = array_merge($config, $c['options']);
                    //echo 'options: <pre>' . print_r($convertOptions, true) . '</pre>'; exit;
                    //echo 'options: <pre>' . print_r($c['options'], true) . '</pre>'; exit;
                }
            }

            $result = self::convertFile($filename, $config, $convertOptions, $converter);

        } else {
            $result = self::convertFile($filename);
        }


        echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
        wp_die();
    }

}
