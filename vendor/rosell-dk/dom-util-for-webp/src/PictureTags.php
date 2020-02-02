<?php

namespace DOMUtilForWebP;

//use Sunra\PhpSimple\HtmlDomParser;
/**
 * Class PictureTags - convert an <img> tag to a <picture> tag and add the webp versions of the images
 * Based this code on code from the ShortPixel plugin, which used code from Responsify WP plugin
 */

use \WebPExpress\AlterHtmlHelper;

class PictureTags
{

    /**
     * Empty constructor for preventing child classes from creating constructors.
     *
     * We do this because otherwise the "new static()" call inside the ::replace() method
     * would be unsafe. See #21
     * @return  void
     */
    public final function __construct()
    {
    }

    public function replaceUrl($url)
    {
        if (!preg_match('#(png|jpe?g)$#', $url)) {
            return;
        }
        return $url . '.webp';
    }

    public function replaceUrlOr($url, $returnValueIfDenied)
    {
        $url = $this->replaceUrl($url);
        return (isset($url) ? $url : $returnValueIfDenied);
    }

    /**
     * Look for attributes such as "data-lazy-src" and "data-src" and prefer them over "src"
     *
     * @param  array  $attributes  an array of attributes for the element
     * @param  string  $attrName    ie "src", "srcset" or "sizes"
     *
     * @return array  an array with "value" key and "attrName" key. ("value" is the value of the attribute and
     *                                    "attrName" is the name of the attribute used)
     *
     */
    private static function lazyGet($attributes, $attrName)
    {
        return array(
            'value' =>
                (isset($attributes['data-lazy-' . $attrName]) && strlen($attributes['data-lazy-' . $attrName])) ?
                    trim($attributes['data-lazy-' . $attrName])
                    : (isset($attributes['data-' . $attrName]) && strlen($attributes['data-' . $attrName]) ?
                        trim($attributes['data-' . $attrName])
                        : (isset($attributes[$attrName]) && strlen($attributes[$attrName]) ?
                            trim($attributes[$attrName]) : false)),
            'attrName' =>
                (isset($attributes['data-lazy-' . $attrName]) && strlen($attributes['data-lazy-' . $attrName])) ?
                    'data-lazy-' . $attrName
                    : (isset($attributes['data-' . $attrName]) && strlen($attributes['data-' . $attrName]) ?
                        'data-' . $attrName
                        : (isset($attributes[$attrName]) && strlen($attributes[$attrName]) ? $attrName : false))
        );
    }

    private static function getAttributes($html)
    {
        if (function_exists("mb_convert_encoding")) {
            $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        }
        if (class_exists('\\DOMDocument')) {
            $dom = new \DOMDocument();
            @$dom->loadHTML($html);
            $image = $dom->getElementsByTagName('img')->item(0);
            $attributes = [];
            foreach ($image->attributes as $attr) {
                    $attributes[$attr->nodeName] = $attr->nodeValue;
            }
            return $attributes;
        } else {
            //$dom = HtmlDomParser::str_get_html($html, false, false, 'UTF-8', false);
            $dom = str_get_html($html, false, false, 'UTF-8', false);
            if ($dom !== false) {
                $elems = $dom->find('img,IMG');
                foreach ($elems as $index => $elem) {
                    $attributes = [];
                    foreach ($elem->getAllAttributes() as $attrName => $attrValue) {
                        $attributes[strtolower($attrName)] = $attrValue;
                    }
                    return $attributes;
                }
            }
            return [];
        }
    }

    /**
     * Makes a string with all attributes.
     *
     * @param  array $attribute_array
     * @return string
     */
    private static function createAttributes($attribute_array)
    {
        $attributes = '';
        foreach ($attribute_array as $attribute => $value) {
            $attributes .= $attribute . '="' . $value . '" ';
        }
        if ($attributes == '') {
            return '';
        }
        // Removes the extra space after the last attribute. Add space before
        return ' ' . substr($attributes, 0, -1);
    }

    /**
     *  Replace <image> tag with <picture> tag.
     */
    private function replaceCallback($match)
    {
        $imgTag = $match[0];

        // Do nothing with images that have the 'webpexpress-processed' class.
        if (strpos($imgTag, 'webpexpress-processed')) {
            return $imgTag;
        }
        $imgAttributes = self::getAttributes($imgTag);

        $srcInfo = self::lazyGet($imgAttributes, 'src');
        $srcsetInfo = self::lazyGet($imgAttributes, 'srcset');
        $sizesInfo = self::lazyGet($imgAttributes, 'sizes');

        // add the exclude class so if this content is processed again in other filter,
        // the img is not converted again in picture
        $imgAttributes['class'] = (isset($imgAttributes['class']) ? $imgAttributes['class'] . " " : "") .
            "webpexpress-processed";

        $srcsetWebP = '';
        if ($srcsetInfo['value']) {
            $srcsetArr = explode(', ', $srcsetInfo["value"]);
            $srcsetArrWebP = [];
            foreach ($srcsetArr as $i => $srcSetEntry) {
                // $srcSetEntry is ie "http://example.com/image.jpg 520w"
                $result = preg_split('/\s+/', trim($srcSetEntry));
                $src = trim($srcSetEntry);
                $width = null;
                if ($result && count($result) >= 2) {
                    list($src, $width) = $result;
                }

                $webpUrl = $this->replaceUrlOr($src, false);
                if ($webpUrl !== false) {
                    $srcsetArrWebP[] = $webpUrl . (isset($width) ? ' ' . $width : '');
                }
            }
            $srcsetWebP = implode(', ', $srcsetArrWebP);
            if (strlen($srcsetWebP) == 0) {
                // We have no webps for you, so no reason to create <picture> tag
                return $imgTag;
            }
            $sizesAttr = ($sizesInfo['value'] ? (' ' . $sizesInfo['attrName'] . '="' . $sizesInfo['value'] . '"') : '');
            $sourceSrcAttrName = $srcsetInfo['attrName'];
            if ($sourceSrcAttrName == 'src') {
                // "src" isn't allowed in <source> tag with <picture> tag as parent.
                $sourceSrcAttrName = 'srcset';
            }
            return '<picture>'
                . '<source ' . $sourceSrcAttrName . '="' . $srcsetWebP . '"' . $sizesAttr . ' type="image/webp">'
                . '<img' . self::createAttributes($imgAttributes) . '>'
                . '</picture>';
        } else {
            $srcWebP = $this->replaceUrlOr($srcInfo['value'], false);
            if ($srcWebP === false) {
                // No reason to create <picture> tag
                return $imgTag;
            }

            $sourceSrcAttrName = $srcInfo['attrName'];
            if ($sourceSrcAttrName == 'src') {
                // "src" isn't allowed in <source> tag with <picture> tag as parent.
                $sourceSrcAttrName = 'srcset';
            }

            return '<picture>'
                . '<source ' . $sourceSrcAttrName . '="' . $srcWebP . '" type="image/webp">'
                . '<img' . self::createAttributes($imgAttributes) . '>'
                . '</picture>';
        }
    }

    /*
     *
     */
    public function replaceHtml($content)
    {
        // TODO: We should not replace <img> tags that are inside <picture> tags already, now should we?
        return preg_replace_callback('/<img[^>]*>/i', array($this, 'replaceCallback'), $content);
    }

    /* Main replacer function */
    public static function replace($html)
    {
        if (!function_exists('str_get_html')) {
            require_once __DIR__ . '/../src-vendor/simple_html_dom/simple_html_dom.inc';
        }
        $pt = new static();
        return $pt->replaceHtml($html);
    }
}
