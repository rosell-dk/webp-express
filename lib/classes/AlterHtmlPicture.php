<?php

namespace WebPExpress;

/**
 * Class AlterHtmlPicture - convert an <img> tag to a <picture> tag and add the webp versions of the images
 * Based this code on code from the ShortPixel plugin, which used code from Responsify WP plugin
 */

use \WebPExpress\AlterHtmlHelper;

class AlterHtmlPicture
{


    /**
     * Look for attributes such as "data-lazy-src" and "data-src" and prefer them over "src"
     *
     * @param $attributes  an array of attributes for the element
     * @param $attrName    ie "src", "srcset" or "sizes"
     *
     * @return [value:.., attrName:...]  (value is the value of the attribute and attrName is the name of the attribute used)
     *
     */
    private static function lazyGet($attributes, $attrName) {
        return array(
            'value' =>
                (isset($attributes['data-lazy-' . $attrName]) && strlen($attributes['data-lazy-' . $attrName])) ?
                    trim($attributes['data-lazy-' . $attrName])
                    : (isset($attributes['data-' . $attrName]) && strlen($attributes['data-' . $attrName]) ?
                        trim($attributes['data-' . $attrName])
                        : (isset($attributes[$attrName]) && strlen($attributes[$attrName]) ? trim($attributes[$attrName]) : false)),
            'attrName' =>
                (isset($attributes['data-lazy-' . $attrName]) && strlen($attributes['data-lazy-' . $attrName])) ? 'data-lazy-' . $attrName
                    : (isset($attributes['data-' . $attrName]) && strlen($attributes['data-' . $attrName]) ? 'data-' . $attrName
                        : (isset($attributes[$attrName]) && strlen($attributes[$attrName]) ? $attrName : false))
        );
    }

    private static function getAttributes( $image_node )
    {
        if(function_exists("mb_convert_encoding")) {
            $image_node = mb_convert_encoding($image_node, 'HTML-ENTITIES', 'UTF-8');
        }
        $dom = new \DOMDocument();
        @$dom->loadHTML($image_node);
        $image = $dom->getElementsByTagName('img')->item(0);
        $attributes = array();
        foreach ( $image->attributes as $attr ) {
                $attributes[$attr->nodeName] = $attr->nodeValue;
        }
        return $attributes;
    }

    /**
     * Makes a string with all attributes.
     *
     * @param $attribute_array
     * @return string
     */
    private static function createAttributes($attribute_array)
    {
        $attributes = '';
        foreach ($attribute_array as $attribute => $value) {
            $attributes .= $attribute . '="' . $value . '" ';
        }
        // Removes the extra space after the last attribute
        return substr($attributes, 0, -1);
    }

    /**
     *  Replace <image> tag with <picture> tag.
     */
    private static function replaceCallback($match) {
        $imgTag = $match[0];

        // Do nothing with images that have the 'webpexpress-processed' class.
        if ( strpos($imgTag, 'webpexpress-processed') ) {
            return $imgTag;
        }
        $attributes = self::getAttributes($imgTag);

        $srcInfo = self::lazyGet($attributes, 'src');
        $srcsetInfo = self::lazyGet($attributes, 'srcset');
        $sizesInfo = self::lazyGet($attributes, 'sizes');

        // We don't wanna have src-ish attributes on the <picture>
        unset($attributes['src']);
        unset($attributes['data-src']);
        unset($attributes['data-lazy-src']);
        //unset($attributes['srcset']);
        //unset($attributes['sizes']);

        $srcsetWebP = '';
        if ($srcsetInfo['value']) {

            $srcsetArr = explode(', ', $srcsetInfo["value"]);
            $srcsetArrWebP = [];
            foreach ($srcsetArr as $i => $srcSetEntry) {
                // $srcSetEntry is ie "http://example.com/image.jpg 520w"
                list($src, $width) = preg_split('/\s+/', trim($srcSetEntry));        // Note that $width might not be set

                $webpUrl = \WebPExpress\AlterHtmlHelper::getWebPUrl($src, false);
                if ($webpUrl !== false) {
                    $srcsetArrWebP[] = $webpUrl . (isset($width) ? ' ' . $width : '');
                }
            }
            $srcsetWebP = implode(', ', $srcsetArrWebP);
            if (strlen($srcsetWebP) == 0)  {
                // We have no webps for you, so no reason to create <picture> tag
                return $imgTag;
            }

        } else {
            // Being a little hackish here.
            // - we use src attribute instead of srcset
            $srcsetInfo = $srcInfo;

            $srcsetWebP = \WebPExpress\AlterHtmlHelper::getWebPUrl($srcInfo['value'], false);
            if ($srcsetWebP === false) {
                // No reason to create <picture> tag
                return $imgTag;
            }
        }

        // add the exclude class so if this content is processed again in other filter, the img is not converted again in picture
        $attributes['class'] = (isset($attributes['class']) ? $attributes['class'] . " " : "") . "webpexpress-processed";

        // TODO:
        // We currently set src-set on picture tag.
        // We inherited that behaviour from ShortPixel. But does it make any sense?
        // Maybe ask them in forum?

        $sizesAttr = ($sizesInfo['value'] ? (' ' . $sizesInfo['attrName'] . '="' . $sizesInfo['value'] . '"') : '');
        return '<picture ' . self::createAttributes($attributes) . '>'
            . '<source ' . $srcsetInfo['attrName'] . '="' . $srcsetWebP . '"' . $sizesAttr . ' type="image/webp">'
            . '<source ' . $srcsetInfo['attrName'] . '="' . $srcsetInfo["value"] . '"' . $sizesAttr . '>'
            . '<img ' . $srcInfo['attrName'] . '="' . $srcInfo['value'] . '" ' . self::createAttributes($attributes) . '>'
            . '</picture>';
    }

    /*
     *
     */
    public static function alter($content) {
        require_once "AlterHtmlHelper.php";

        // TODO: We should not replace <img> tags that are inside <picture> tags already, now should we?
        return preg_replace_callback('/<img[^>]*>/', 'self::replaceCallback', $content);
    }

}
