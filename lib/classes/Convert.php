<?php

/*
This class is made to be independent of other classes, and must be kept like that.
It is used by webp-on-demand.php, which does not register an auto loader. It is also used for bulk conversion.
*/
namespace WebPExpress;

use \WebPExpress\ConvertHelperIndependent;
use \WebPExpress\Config;

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

    public static function convertFile($source, $config = null)
    {
        if (is_null($config)) {
            $config = Config::loadConfigAndFix();
        }
        $options = Config::generateWodOptionsFromConfigObj($config);
        if (isset($config['converter'])) {
            $options['converter'] = $config['converter'];    
        }

        $destination = self::getDestination($source, $config);

        $result = ConvertHelperIndependent::convert($source, $destination, $options);

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
        $filename = $_POST['filename'];

        if (isset($_POST['config-overrides'])) {
            $config = Config::loadConfigAndFix();

            $overrides = $_POST['config-overrides'];

            // We got crazy encoding, perhaps by jQuery. Clean it up
            $overrides = preg_replace('/\\\\"/', '"', $overrides);
            $overrides = json_decode($overrides, true);

            $config = array_merge($config, $overrides);

            if (isset($_POST['converter'])) {
                $config['converter'] = $_POST['converter'];
            }
            $result = self::convertFile($filename, $config);

        } else {
            $result = self::convertFile($filename);
        }


        echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
        wp_die();
    }

}
