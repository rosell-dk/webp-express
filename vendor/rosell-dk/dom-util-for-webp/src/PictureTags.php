<?php

namespace DOMUtilForWebP;

//use Sunra\PhpSimple\HtmlDomParser;
use KubAT\PhpSimple\HtmlDomParser;

/**
 * Class PictureTags - convert an <img> tag to a <picture> tag and add the webp versions of the images
 * Code is based on code from the ShortPixel plugin, which in turn used code from Responsify WP plugin
 *
 * It works like this:
 *
 * 1. Remove existing <picture> tags and their content - replace with tokens in order to reinsert later
 * 2. Process <img> tags.
 *    - The tags are found with regex.
 *    - The attributes are parsed with DOMDocument if it exists, otherwise with the Simple Html Dom library,
 *      which is included inside this library
 * 3. Re-insert the existing <picture> tags
 *
 * This procedure is very gentle and needle-like. No need for a complete parse - so invalid HTML is no big issue
 *
 * PS:
 * https://packagist.org/packages/masterminds/html5
 */


class PictureTags
{

    /**
     * Empty constructor for preventing child classes from creating constructors.
     *
     * We do this because otherwise the "new static()" call inside the ::replace() method
     * would be unsafe. See #21
     * @return  void
     */
    final public function __construct()
    {
        $this->existingPictureTags = [];
    }

    private $existingPictureTags;

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

    /**
     * Look for attribute such as "src", but also with prefixes such as "data-lazy-src" and "data-src"
     *
     * @param  array  $attributes  an array of all attributes for the element
     * @param  string  $attrName    ie "src", "srcset" or "sizes"
     *
     * @return array  an array with "value" key and "attrName" key. ("value" is the value of the attribute and
     *                                    "attrName" is the name of the attribute used)
     *
     */
    private static function findAttributesWithNameOrPrefixed($attributes, $attrName)
    {
        $tryThesePrefixes = ['', 'data-lazy-', 'data-'];
        $result = [];
        foreach ($tryThesePrefixes as $prefix) {
            $name = $prefix . $attrName;
            if (isset($attributes[$name]) && strlen($attributes[$name])) {
                /*$result[] = [
                    'value' => trim($attributes[$name]),
                    'attrName' => $name,
                ];*/
                $result[$name] = trim($attributes[$name]);
            }
        }
        return $result;
    }

    /**
     *  Convert to UTF-8 and encode chars outside of ascii-range
     *
     *  Input: html that might be in any character encoding and might contain non-ascii characters
     *  Output: html in UTF-8 encding, where non-ascii characters are encoded
     *
     */
    private static function textToUTF8WithNonAsciiEncoded($html)
    {
        if (function_exists("mb_convert_encoding")) {
            $html = mb_convert_encoding($html, 'UTF-8');
            $html = mb_encode_numericentity($html, array (0x7f, 0xffff, 0, 0xffff), 'UTF-8');
        }
        return $html;
    }

    private static function getAttributes($html)
    {
        if (class_exists('\\DOMDocument')) {
            $dom = new \DOMDocument();

            if (function_exists("mb_encode_numericentity")) {
                // I'm in doubt if I should add the following line (see #41)
                // $html = mb_convert_encoding($html, 'UTF-8');
                $html = mb_encode_numericentity($html, array (0x7f, 0xffff, 0, 0xffff));  // #41
            }

            @$dom->loadHTML($html);
            $image = $dom->getElementsByTagName('img')->item(0);
            $attributes = [];
            foreach ($image->attributes as $attr) {
                $attributes[$attr->nodeName] = $attr->nodeValue;
            }
            return $attributes;
        } else {
            // Convert to UTF-8 because HtmlDomParser::str_get_html needs to be told the
            // encoding. As UTF-8 might conflict with the charset set in the meta, we must
            // encode all characters outside the ascii-range.
            // It would perhaps have been better to try to guess the encoding rather than
            // changing it (see #39), but I'm reluctant to introduce changes.
            $html =  self::textToUTF8WithNonAsciiEncoded($html);
            $dom = HtmlDomParser::str_get_html($html, false, false, 'UTF-8', false);
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
     *  Replace <img> tag with <picture> tag.
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

        $srcSetAttributes = self::findAttributesWithNameOrPrefixed($imgAttributes, 'srcset');
        $srcAttributes = self::findAttributesWithNameOrPrefixed($imgAttributes, 'src');

        if ((!isset($srcSetAttributes['srcset'])) && (!isset($srcAttributes['src']))) {
            // better not mess with this html...
            return $imgTag;
        }

        // add the exclude class so if this content is processed again in other filter,
        // the img is not converted again in picture
        $imgAttributes['class'] = (isset($imgAttributes['class']) ? $imgAttributes['class'] . " " : "") .
            "webpexpress-processed";

        // Process srcset (also data-srcset etc)
        $atLeastOneWebp = false;
        $sourceTagAttributes = [];
        foreach ($srcSetAttributes as $attrName => $attrValue) {
            $srcsetArr = explode(', ', $attrValue);
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
                    if (substr($src, 0, 5) != 'data:') {
                        $atLeastOneWebp = true;
                        $srcsetArrWebP[] = $webpUrl . (isset($width) ? ' ' . $width : '');
                    }
                }
            }
            $sourceTagAttributes[$attrName] = implode(', ', $srcsetArrWebP);
        }

        foreach ($srcAttributes as $attrName => $attrValue) {
            if (substr($attrValue, 0, 5) == 'data:') {
                // ignore tags with data urls, such as <img src="data:...
                return $imgTag;
            }
            // Make sure not to override existing srcset with src
            if (!isset($sourceTagAttributes[$attrName . 'set'])) {
                $srcWebP = $this->replaceUrlOr($attrValue, false);
                if ($srcWebP !== false) {
                    $atLeastOneWebp = true;
                }
                $sourceTagAttributes[$attrName . 'set'] = $srcWebP;
            }
        }

        if ($sizesInfo['value']) {
            $sourceTagAttributes[$sizesInfo['attrName']] = $sizesInfo['value'];
        }

        if (!$atLeastOneWebp) {
            // We have no webps for you, so no reason to create <picture> tag
            return $imgTag;
        }

        return '<picture>'
            . '<source' . self::createAttributes($sourceTagAttributes) . ' type="image/webp">'
            . '<img' . self::createAttributes($imgAttributes) . '>'
            . '</picture>';
    }

    /*
     *
     */
    public function removePictureTagsTemporarily($content)
    {
        //print_r($content);
        $this->existingPictureTags[] = $content[0];
        return 'PICTURE_TAG_' . (count($this->existingPictureTags) - 1) . '_';
    }

    /*
     *
     */
    public function insertPictureTagsBack($content)
    {
        $numberString = $content[1];
        $numberInt = intval($numberString);
        return $this->existingPictureTags[$numberInt];
    }

    /**
     *
     */
    public function replaceHtml($content)
    {
        if (!class_exists('\\DOMDocument') && function_exists('mb_detect_encoding')) {
            // PS: Correctly identifying Windows-1251 encoding only works on some systems
            //     But at least I'm not aware of any false positives
            if (mb_detect_encoding($content, ["ASCII", "UTF8", "Windows-1251"]) == 'Windows-1251') {
                $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1251');
            }
        }

        $this->existingPictureTags = [];

        // Tempororily remove existing <picture> tags
        $content = preg_replace_callback(
            '/<picture[^>]*>.*?<\/picture>/is',
            array($this, 'removePictureTagsTemporarily'),
            $content
        );

        // Replace "<img>" tags
        $content = preg_replace_callback('/<img[^>]*>/i', array($this, 'replaceCallback'), $content);

        // Re-insert <picture> tags that was removed
        $content = preg_replace_callback('/PICTURE_TAG_(\d+)_/', array($this, 'insertPictureTagsBack'), $content);

        return $content;
    }

    /* Main replacer function */
    public static function replace($html)
    {
        $pt = new static();
        return $pt->replaceHtml($html);
    }
}
