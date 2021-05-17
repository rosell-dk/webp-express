<?php

namespace WebPExpress;

use \WebPConvert\Convert\Converters\Ewww;

use \WebPExpress\ConvertHelperIndependent;
use \WebPExpress\Config;
use \WebPExpress\ConvertersHelper;
use \WebPExpress\ImageRoots;
use \WebPExpress\SanityCheck;
use \WebPExpress\SanityException;
use \WebPExpress\Validate;
use \WebPExpress\ValidateException;
use \Onnov\DetectEncoding\EncodingDetector;

class BackgroundConvert extends \WP_CLI_Command
{
    public function bgconvert($args, $assoc_args)
    {
        $config = Config::loadConfigAndFix();
        $bc = new BulkConvert();
        $cn = new Convert();
        $arr = $bc->getList($config);
        foreach($arr as $singleitem){

            $root = $singleitem['root'];
            //echo count($singleitem["files"]);
            foreach($singleitem["files"] as $onesingleitem)
            {
                $filename = $root . '/' . $onesingleitem;
                \WP_CLI::line( $filename);
                $result = $cn->convertFile($filename);
                //var_dump($result);
            }
        }
    }
}