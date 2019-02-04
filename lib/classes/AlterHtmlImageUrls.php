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
//use \WebPExpress\ImageUrlsReplacer;
use DOMUtilForWebP\ImageUrlReplacer;

class AlterHtmlImageUrls extends ImageUrlReplacer
{
    public function replaceUrl($url) {
        return AlterHtmlHelper::getWebPUrl($url, null);
    }

    public function attributeFilter($attrName) {
        // Allow "src", "srcset" and data-attributes that smells like they are used for images
        // The following rule matches all attributes used for lazy loading images that we know of
        return preg_match('#^(src|srcset|(data-[^=]*(lazy|small|slide|img|large|src|thumb|source|set|bg-url)[^=]*))$#i', $attrName);

        // If you want to limit it further, only allowing attributes known to be used for lazy load,
        // use the following regex instead:
        //return preg_match('#^(src|srcset|data-(src|srcset|cvpsrc|cvpset|thumb|bg-url|large_image|lazyload|source-url|srcsmall|srclarge|srcfull|slide-img|lazy-original))$#i', $attrName);
    }

     /*
    public static function alter($content) {
        require_once "AlterHtmlHelper.php";


        //Find image urls in HTML attributes.
        //- Ignore URLs with query string
        //- Matches src, src-set and data-attributes in a limited set of tags
        //- Only jpeg, jpg or png (NO GIFs)
        //- Both relative and absolute URLs are matched
        //- tag name is returned in $1, url in $&

        //Pattern for attributes can be tested here:  https://regexr.com/46jat
        //PS: Based on regex found in Cache Enabler 1.3.2: https://regexr.com/46isf

        //TODO: Dont match PNG, unless choosen
        $regex_rule = '#(?<=(?:<(img|source|input|iframe)[^>]*\s+(src|srcset|data-[^=]*)\s*=\s*[\"\']?))(?:[^\"\'>]+)(\.png|\.jp[e]?g)(\s\d+w)?(?=\/?[\"\'\s\>])#';
        return preg_replace_callback($regex_rule, 'self::replaceCallback', $content);

        // TODO:  css too. I already got the regex ready: https://regexr.com/46jcg


    }

/*
    private static function replaceCallback($match) {
        list($attrValue, $attrName) = $match;

        // A data attribute contain a srcset or single url.
        // We should probably examine the attrValue further. For now, we however just use the attrName as a clue.
        // If it contains "set", we handle it as a srcset. This takes care of both "data-cvpset" and "srcset"
        if (strpos($attrName, 'set') > 0) {
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
        } else {
            return \WebPExpress\AlterHtmlHelper::getWebPUrl($attrValue, $attrValue);
        }
    }*/

}
