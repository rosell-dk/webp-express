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

        $destination = self::getDestination($source, $config);

        $result = ConvertHelperIndependent::convert($source, $destination, $options);

        //$result['destination'] = $destination;
        if ($result['success']) {
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

}
