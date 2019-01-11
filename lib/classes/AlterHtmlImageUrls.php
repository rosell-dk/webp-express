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
        return preg_replace_callback($regex_rule, 'self::replaceCallback', $content);
    }

    private static function replaceCallback($match) {
        list($attrValue, $attrName) = $match;
        switch ($attrName) {
            case 'src':
            case 'ref':
                return \WebPExpress\AlterHtmlHelper::getWebPUrl($attrValue, $attrValue);

            case 'set':
                $srcsetArr = explode(',', $attrValue);
                foreach ($srcsetArr as $i => $srcSetEntry) {
                    // $srcSetEntry is ie "http://example.com/image.jpg 520w"
                    list($src, $width) = preg_split('/\s+/', trim($srcSetEntry));        // $width might not be set, but thats ok.

                    $webpUrl = \WebPExpress\AlterHtmlHelper::getWebPUrl($src, false);
                    if ($webpUrl !== false) {
                        $srcsetArr[$i] = $webpUrl . (isset($width) ? ' ' . $width : '');
                    }
                }
                return implode(', ', $srcsetArr);

            default:
                return $src;
        }
    }

}
