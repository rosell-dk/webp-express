<?php

/*
This class is made to be independent of other classes, and must be kept like that.
It is used by webp-on-demand.php, which does not register an auto loader. It is also used for bulk conversion.
*/
namespace WebPExpress;

use \WebPExpress\ConvertHelperIndependent;

class Convert
{

    public static function convertFile($source, $config = null)
    {
        if (is_null($config)) {
            $config = Config::loadConfigAndFix();            
        }
        $options = Config::generateWodOptionsFromConfigObj($config);

        $destination = ConvertHelperIndependent::getDestination(
            $source,
            $options['destination-folder'],
            $options['destination-extension'],
            Paths::getWebPExpressContentDirAbs(),
            Paths::getUploadDirAbs()
        );

        return ConvertHelperIndependent::convert($source, $destination, $options);
    }

}
