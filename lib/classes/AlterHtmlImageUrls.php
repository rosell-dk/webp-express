<?php

namespace WebPExpress;

use \WebPExpress\AlterHtmlInit;
use \WebPExpress\Paths;

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
        return preg_match('#^(src|srcset|poster|(data-[^=]*(lazy|small|slide|img|large|src|thumb|source|set|bg-url)[^=]*))$#i', $attrName);

        // If you want to limit it further, only allowing attributes known to be used for lazy load,
        // use the following regex instead:
        //return preg_match('#^(src|srcset|data-(src|srcset|cvpsrc|cvpset|thumb|bg-url|large_image|lazyload|source-url|srcsmall|srclarge|srcfull|slide-img|lazy-original))$#i', $attrName);
    }

}
