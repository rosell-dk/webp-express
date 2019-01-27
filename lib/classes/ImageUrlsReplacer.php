<?php

namespace WebPExpress;

use Sunra\PhpSimple\HtmlDomParser;

/**
 *  Highly configurable class for replacing image URLs in HTML (both src and srcset syntax)
 *
 *  Based on http://simplehtmldom.sourceforge.net/ - a library for easily manipulating HTML by means of a DOM.
 *  The great thing about this library is that it supports working on invalid HTML and it only applies the changes you
 *  make - very gently.
 *  We are using this packaged version:  https://packagist.org/packages/sunra/php-simple-html-dom-parser
 *
 * TODO: https://github.com/paquettg/php-html-parser
 * TODO: https://github.com/Masterminds/html5-php
 * TODO: or perhaps https://packagist.org/packages/symfony/dom-crawler
 * TODO: Other encodings than UTF-8
 * TODO: Other languages
 * TODO: Only use the parser on bits of html, not whole html. Ie use preg to find tags, and THEN parse
 * TODO: Check out how ewww does it
 * https://github.com/Masterminds/html5-php/wiki/End-User-Submitted-Markup
 * http://htmlpurifier.org/
 * https://stackoverflow.com/questions/3577641/how-do-you-parse-and-process-html-xml-in-php
 *
 *  Behaviour can be customized by setting the public variables ($searchInTags, $urlReplacerFunction, etc)
 *
 *  Default behaviour:
 *  - The modified URL is the same as the original, with ".webp" appended                   ($urlReplacerFunction)
 *  - Limits to these tags: <img>, <source>, <input> and <iframe>                           ($searchInTags)
 *  - Limits to these attributes: "src", "src-set" and any attribute starting with "data-"  ($attributeFilterFunction)
 *  - Only replaces URLs that ends with "png", "jpg" or "jpeg" (no query strings either)    ($urlReplacerFunction)
 *
 *
 */
class ImageUrlsReplacer
{

    public static $searchInTags = ['img', 'source', 'input', 'iframe'];
    public static $attributeFilterFunction = 'self::attributeFilter';
    public static $urlReplacerFunction = 'self::replaceUrl';
    //public static $urlValidatorFunction = 'self::isValidUrl';
    public static $handleAttributeFunction = 'self::handleAttribute';
    public static $processCSSFunction = 'self::processCSS';

    public function hello()
    {
        return 'hi';
    }

    /**
     *
     * @return webp url or same url as passed in if it should not be modified
     **/
    public function replaceUrl($url)
    {
        if (!preg_match('#(png|jpe?g)$#', $url)) {
            return $url;
        }
        return $url . '.webp';
    }

    /*
    public function isValidUrl($url)
    {
        return preg_match('#(png|jpe?g)$#', $url);
    }*/

    public function handleSrc($attrValue)
    {
        return call_user_func(self::$urlReplacerFunction, $attrValue);
    }

    public function handleSrcSet($attrValue)
    {
        // $attrValue is ie: <img data-x="1.jpg 1000w, 2.jpg">
        $srcsetArr = explode(',', $attrValue);
        foreach ($srcsetArr as $i => $srcSetEntry) {
            // $srcSetEntry is ie "image.jpg 520w", but can also lack width, ie just "image.jpg"
            $srcSetEntry = trim($srcSetEntry);
            $entryParts = preg_split('/\s+/', $srcSetEntry);
            if (count($entryParts) == 2) {
                list($src, $width) = $entryParts;
            } else {
                $src = $srcSetEntry;
                $width = null;
            }

            $webpUrl = call_user_func(self::$urlReplacerFunction, $src);
            if ($webpUrl != $src) {
                $srcsetArr[$i] = $webpUrl . (isset($width) ? ' ' . $width : '');
            }
        }
        return implode(', ', $srcsetArr);
    }

    public function looksLikeSrcSet($value)
    {
        if (preg_match('#\s\d*w#', $value)) {
            return true;
        }
        return false;
    }

    public function handleAttribute($value)
    {
        if (self::looksLikeSrcSet($value)) {
            return self::handleSrcSet($value);
        }
        return self::handleSrc($value);
    }

    public function attributeFilter($attrName)
    {
        if (($attrName == 'src') || ($attrName == 'srcset') || (strpos($attrName, 'data-') === 0)) {
            return true;
        }
        return false;
    }

    public function processCSSRegExCallback($matches)
    {
        list($all, $pre, $quote, $url, $post) = $matches;
        return $pre . call_user_func(self::$urlReplacerFunction, $url) . $post ;
    }

    public function processCSS($css)
    {
        $declarations = explode(';', $css);
        foreach ($declarations as $i => &$declaration) {
            if (preg_match('#(background(-image)?)\\s*:#', $declaration)) {
                // https://regexr.com/46qdg
                //$regex = '#(url\s*\(([\"\']?))([^\'\";\)]*)(\2\s*\))#';
                $parts = explode(',', $declaration);
                //print_r($parts);
                foreach ($parts as &$part) {
                    //echo 'part:' . $part . "\n";
                    $regex = '#(url\\s*\\(([\\"\\\']?))([^\\\'\\";\\)]*)(\\2\\s*\\))#';
                    $part = preg_replace_callback($regex, 'self::processCSSRegExCallback', $part);
                    //echo 'result:' . $part . "\n";
                }
                $declarations[$i] = implode($parts, ',');
            }
        }
        return implode(';', $declarations);
    }

    /* Main replacer function */

    public function replace($html)
    {
        if ($html == '') {
            return '';
        }

        // https://stackoverflow.com/questions/4812691/preserve-line-breaks-simple-html-dom-parser

        // function str_get_html($str, $lowercase=true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT)

        $dom = HtmlDomParser::str_get_html( $html, false, false, 'UTF-8', false);

        // Replace attributes (src, srcset, data-src, etc)
        foreach (self::$searchInTags as $tagName) {
            $elems = $dom->find($tagName);
            foreach ($elems as $index => $elem) {
                $attributes = $elem->getAllAttributes();
                foreach ($elem->getAllAttributes() as $attrName => $attrValue) {
                    if (call_user_func(self::$attributeFilterFunction, $attrName)) {
                        $elem->setAttribute($attrName, call_user_func(self::$handleAttributeFunction, $attrValue));
                    }
                }
            }
        }

        // Replace <style> elements
        $elems = $dom->find('style');
        foreach ($elems as $index => $elem) {
            $css = call_user_func(self::$processCSSFunction, $elem->innertext);
            if ($css != $elem->innertext) {
                $elem->innertext = $css;
            }
        }

        // Replace "style attributes
        $elems = $dom->find('*[style]');
        foreach ($elems as $index => $elem) {
            $css = call_user_func(self::$processCSSFunction, $elem->style);
            if ($css != $elem->style) {
                $elem->style = $css;
            }
        }

        return $dom->save();
    }

}
