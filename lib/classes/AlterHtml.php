<?php

namespace WebPExpress;

//include_once "State.php";
//use \WebPExpress\State;

/**
 * Class AlterHtml - convert an <img> tag to a <picture> tag and add the webp versions of the images
 * Based this code on code from the ShortPixel plugin, which used code from Responsify WP plugin
 */

class AlterHtml
{
    public static $options = [
        'enabled' => true,
        'replacement' => 'picture',          // "picture" or "extension"
        'hooks' => 'content-hooks',             // "content-hooks" or "init"
        'only-for-webp-enabled-browsers' => false,     // If true, there will be two HTML versions of each page
        'only-for-webps-that-exists' => false,
        'destination-folder' => 'separate',
        'destination-extension' => 'append',
    ];

    public static function lazyGet($img, $type) {
        return array(
            'value' =>
                (isset($img['data-lazy-' . $type]) && strlen($img['data-lazy-' . $type])) ?
                    $img['data-lazy-' . $type]
                    : (isset($img['data-' . $type]) && strlen($img['data-' . $type]) ?
                        $img['data-' . $type]
                        : (isset($img[$type]) && strlen($img[$type]) ? $img[$type] : false)),
            'prefix' =>
                (isset($img['data-lazy-' . $type]) && strlen($img['data-lazy-' . $type])) ? 'data-lazy-'
                    : (isset($img['data-' . $type]) && strlen($img['data-' . $type]) ? 'data-'
                        : (isset($img[$type]) && strlen($img[$type]) ? '' : false))
        );
    }

    public static function alter($content) {
        // Don't do anything with the RSS feed.
        if ( is_feed() || is_admin() ) { return $content; }

        return preg_replace_callback('/<img[^>]*>/', array('\WebPExpress\AlterHtml', 'convertImage'), $content);
    }

    public static function convertImage($match) {

        // Do nothing with images that have the 'webpexpress-processed' class.
        if ( strpos($match[0], 'webpexpress-processed') ) { return $match[0]; }

        //return 'look:' . print_r($match, true);
        $img = self::getAttributes($match[0]);
        //return 'look:' . print_r($img, true);


        $srcInfo = self::lazyGet($img, 'src');
        //return 'look:' . print_r($srcInfo, true);

        $src = $srcInfo['value'];
        $srcPrefix = $srcInfo['prefix'];

        $srcsetInfo = self::lazyGet($img, 'srcset');
        $srcset = $srcsetInfo['value'];
        $srcsetPrefix = $srcset ? $srcsetInfo['prefix'] : $srcInfo['prefix'];

        $sizesInfo = self::lazyGet($img, 'sizes');
        $sizes = $sizesInfo['value'];
        $sizesPrefix = $sizesInfo['prefix'];

        //check if there are webps
        /*$id = $thisClass::url_to_attachment_id( $src );
        if(!$id) {
            return $match[0];
        }
        $imageBase = dirname(get_attached_file($id)) . '/';
        */
        $updir = wp_upload_dir(null, false);
        $proto = explode("://", $src);
        if(count($proto) > 1) {
            //check that baseurl uses the same http/https proto and if not, change
            $proto = $proto[0];
            if(strpos($updir['baseurl'], $proto."://") === false) {
                $base = explode("://", $updir['baseurl']);
                if(count($base) > 1) {
                    $updir['baseurl'] = $proto . "://" . $base[1];
                }
            }
        }

        //$imageBase = str_replace($updir['baseurl'], SHORTPIXEL_UPLOADS_BASE, $src);

        $imageBase = str_replace($updir['baseurl'], $updir['basedir'], $src);
        if($imageBase == $src) {
            return $match[0];
        }
        $imageBase = dirname($imageBase) . '/';

        // We don't wanna have src-ish attributes on the <picture>
        unset($img['src']);
        unset($img['data-src']);
        unset($img['data-lazy-src']);
        //unset($img['srcset']);
        //unset($img['sizes']);

        $srcsetWebP = '';
        if($srcset) {

            $defs = explode(",", $srcset);
            foreach($defs as $item) {
                $parts = preg_split('/\s+/', trim($item));
                //echo(" file: " . $parts[0] . " ext: " . pathinfo($parts[0], PATHINFO_EXTENSION) . " basename: " . wp_basename($parts[0], '.' . pathinfo($parts[0], PATHINFO_EXTENSION)));

                $useThis = true;
                if (self::$options['only-for-webps-that-exists']) {
                    $fileWebP = $imageBase . wp_basename($parts[0], '.' . pathinfo($parts[0], PATHINFO_EXTENSION)) . '.webp';
                    $useThis = file_exists($fileWebP);

                }
                if ($useThis) {
                    $srcsetWebP .= (strlen($srcsetWebP) ? ',': '')
                        . preg_replace('/\.[a-zA-Z0-9]+$/', '.webp', $parts[0])
                        . (isset($parts[1]) ? ' ' . $parts[1] : '');
                }
            }
            //$srcsetWebP = preg_replace('/\.[a-zA-Z0-9]+\s+/', '.webp ', $srcset);
        } else {
            $srcset = trim($src);

            $useThis = true;
            if (self::$options['only-for-webps-that-exists']) {
                $fileWebP = $imageBase . wp_basename($srcset, '.' . pathinfo($srcset, PATHINFO_EXTENSION)) . '.webp';
                $useThis = file_exists($fileWebP);
            }
            if ($useThis) {
                $srcsetWebP = preg_replace('/\.[a-zA-Z0-9]+$/', '.webp', $srcset);
            }
        }
        if(!strlen($srcsetWebP))  { return $match[0]; }

        //add the exclude class so if this content is processed again in other filter, the img is not converted again in picture
        $img['class'] = (isset($img['class']) ? $img['class'] . " " : "") . "webpexpress-processed";

        return '<picture ' . self::create_attributes($img) . '>'
        .'<source ' . $srcsetPrefix . 'srcset="' . $srcsetWebP . '"' . ($sizes ? ' ' . $sizesPrefix . 'sizes="' . $sizes . '"' : '') . ' type="image/webp">'
        .'<source ' . $srcsetPrefix . 'srcset="' . $srcset . '"' . ($sizes ? ' ' . $sizesPrefix . 'sizes="' . $sizes . '"' : '') . '>'
        .'<img ' . $srcPrefix . 'src="' . $src . '" ' . self::create_attributes($img) . '>'
        .'</picture>';
    }

    public static function getAttributes( $image_node )
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
    public static function create_attributes( $attribute_array )
    {
        $attributes = '';
        foreach ($attribute_array as $attribute => $value) {
            $attributes .= $attribute . '="' . $value . '" ';
        }
        // Removes the extra space after the last attribute
        return substr($attributes, 0, -1);
    }

    /**
     * @param $image_url
     * @return array
     */
     /*
    public static function url_to_attachment_id ( $image_url ) {
        // Thx to https://github.com/kylereicks/picturefill.js.wp/blob/master/inc/class-model-picturefill-wp.php
        global $wpdb;
        $original_image_url = $image_url;
        $image_url = preg_replace('/^(.+?)(-\d+x\d+)?\.(jpg|jpeg|png|gif)((?:\?|#).+)?$/i', '$1.$3', $image_url);
        $prefix = $wpdb->prefix;
        $attachment_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM " . $prefix . "posts" . " WHERE guid='%s';", $image_url ));

        //try the other proto (https - http) if full urls are used
        if ( empty($attachment_id) && strpos($image_url, 'http://') === 0 ) {
            $image_url_other_proto =  strpos($image_url, 'https') === 0 ?
                str_replace('https://', 'http://', $image_url) :
                str_replace('http://', 'https://', $image_url);
            $attachment_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM " . $prefix . "posts" . " WHERE guid='%s';", $image_url_other_proto ));
        }

        //try using only path
        if (empty($attachment_id) ) {
            $image_path = parse_url($image_url, PHP_URL_PATH); //some sites have different domains in posts guid (site changes, etc.)
            $attachment_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM " . $prefix . "posts" . " WHERE guid like'%%%s';", $image_path ));
        }

        //try using the initial URL
        if ( empty($attachment_id) ) {
            $attachment_id = $wpdb->get_col($wpdb->prepare("SELECT ID FROM " . $prefix . "posts" . " WHERE guid='%s';", $original_image_url ));
        }
        return !empty($attachment_id) ? $attachment_id[0] : false;
    }*/

}
