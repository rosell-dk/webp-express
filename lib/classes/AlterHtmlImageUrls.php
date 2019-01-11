<?php

namespace WebPExpress;

include_once "Paths.php";
use \WebPExpress\Paths;

use \WebPExpress\AlterHtmlInit;

/**
 * Class AlterHtmlImageUrls - convert image urls to webp
 * Based this code on code from the Cache Enabler plugin
 */

use \WebPExpress\AlterHtmlHelper;

class AlterHtmlImageUrls
{
    /*
     *
     */
    public static function alter($content) {
        require_once "AlterHtmlHelper.php";

        // TODO:
        // We look for a "ref" attribute, but what is that used for?

        // TODO: also replace attributes typically used for lazy loading (see AlterHtmlPicture.php)
        
        $regex_rule = '#(?<=(?:(ref|src|set)=[\"\']))(?:http[s]?[^\"\']+)(\.png|\.jp[e]?g)(?:[^\"\']+)?(?=[\"\')])#';
        return preg_replace_callback($regex_rule, 'self::_convertWebP', $content);
    }

    private static function _convertWebP($asset) {
        list($src, $attr) = $asset;
        switch ($attr) {
            case 'src':
            case 'ref':
                return self::_convertWebPSrc($src);
            case 'set':
                return self::_convertWebPSrcSet($src);
            default:
                return $src;
        }
    }

    private static function _convertWebPSrc($src) {
        return \WebPExpress\AlterHtmlHelper::getWebPUrl($src, $src);
    }

    private static function _convertWebPSrcSet($srcset) {

        $srcsetArr = explode(', ', $srcset);
        foreach ($srcsetArr as $i => $srcSetEntry) {
            // $srcSetEntry is ie "http://example.com/image.jpg 520w"
            list($src, $width) = explode(' ', $srcSetEntry);

            $webpUrl = \WebPExpress\AlterHtmlHelper::getWebPUrl($src, false);
            if ($webpUrl !== false) {
                $srcsetArr[$i] = $webpUrl . ' ' . $width;
            }
        }
        $srcset = implode(', ', $srcsetArr);
        return $srcset;
    }
}
