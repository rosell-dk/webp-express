<?php

/**
 * WebPConvert - Convert JPEG & PNG to WebP with PHP
 *
 * @link https://github.com/rosell-dk/webp-convert
 * @license MIT
 */

namespace WebPExpressTests;

use WebPExpress\ImageUrlsReplacer;

class ImageUrlsReplacerExtended extends ImageUrlsReplacer
{

    public function replaceUrl($url) {
        return 'hello';
    }
}
