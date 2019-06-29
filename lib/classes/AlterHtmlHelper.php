<?php

namespace WebPExpress;

//use AlterHtmlInit;

use \WebPExpress\Paths;
use \WebPExpress\PathHelper;
use \WebPExpress\Multisite;
use \WebPExpress\Option;

class AlterHtmlHelper
{

    public static $options;
    /*
    public static function hasWebP($src)
    {
        return true;
    }

    public static function inUploadDir($src)
    {
        $upload_dir = wp_upload_dir();
        $src_url = parse_url($upload_dir['baseurl']);
        $upload_path = $src_url['path'];

        return (strpos($src, $upload_path) !== false );

    }

    public static function checkSrc($src)
    {
        self::$options = \WebPExpress\AlterHtmlInit::self::$options();


        if (self::$options['destination-folder'] == 'mingled') {

        }
    }
*/
    /**
     *  Gets relative path between a base url and another.
     *  Returns false if the url isn't a subpath
     *
     *  @param $imageUrl       (ie "http://example.com/wp-content/image.jpg")
     *  @param $baseUrl        (ie "http://example.com/wp-content")
     *  @return path or false  (ie "/image.jpg")
     */
    public static function getRelUrlPath($imageUrl, $baseUrl)
    {

        $baseUrlComponents = parse_url($baseUrl);
        /* ie:
        (
            [scheme] => http
            [host] => we0
            [path] => /wordpress/uploads-moved
        )*/

        $imageUrlComponents = parse_url($imageUrl);
        /* ie:
        (
            [scheme] => http
            [host] => we0
            [path] => /wordpress/uploads-moved/logo.jpg
        )*/
        if ($baseUrlComponents['host'] != $imageUrlComponents['host']) {
            return false;
        }

        // Check if path begins with base path
        if (strpos($imageUrlComponents['path'], $baseUrlComponents['path']) !== 0) {
            return false;
        }

        // Remove base path from path (we know it begins with basepath, from previous check)
        return substr($imageUrlComponents['path'], strlen($baseUrlComponents['path']));

    }

    /**
     *  Looks if $imageUrl is rooted in $baseUrl and if the file is there
     *
     *
     *  @param $imageUrl    (ie http://example.com/wp-content/image.jpg)
     *  @param $baseUrl     (ie http://example.com/wp-content)
     *  @param $baseDir     (ie /var/www/example.com/wp-content)
     */
    public static function isImageUrlHere($imageUrl, $baseUrl, $baseDir)
    {

        $srcPathRel = self::getRelUrlPath($imageUrl, $baseUrl);

        if ($srcPathRel === false) {
            return false;
        }

        // Calculate file path to src
        $srcPathAbs = $baseDir . $srcPathRel;
        //return 'dyt:' . $srcPathAbs;

        // Check that src file exists
        if (!@file_exists($srcPathAbs)) {
            return false;
        }

        return true;

    }


    public static function isSourceInUpload($src)
    {
        /* $src is ie http://we0/wp-content-moved/themes/twentyseventeen/assets/images/header.jpg */

        $uploadDir = wp_upload_dir();
        /* ie:

            [path] => /var/www/webp-express-tests/we0/wordpress/uploads-moved
            [url] => http://we0/wordpress/uploads-moved
            [subdir] =>
            [basedir] => /var/www/webp-express-tests/we0/wordpress/uploads-moved
            [baseurl] => http://we0/wordpress/uploads-moved
            [error] =>
        */

        return self::isImageUrlHere($src, $uploadDir['baseurl'], $uploadDir['basedir']);
    }


    /**
     *  Get url for webp, given a certain baseUrl / baseDir.
     *  Base can for example be uploads or wp-content.
     *
     *  returns false
     *  - if no source file found in that base
     *  - or webp file isn't there and the `only-for-webps-that-exists` option is set
     *
     *  @param $imageUrl    (ie http://example.com/wp-content/image.jpg)
     *  @param $baseUrl     (ie http://example.com/wp-content)
     *  @param $baseDir     (ie /var/www/example.com/wp-content)
     */
    private static function getWebPUrlInBase($sourceUrl, $baseUrl, $baseDir)
    {
        //error_log('getWebPUrlInBase:' . $sourceUrl . ':' . $baseUrl . ':' . $baseDir);

        $srcPathRel = self::getRelUrlPath($sourceUrl, $baseUrl);

        if ($srcPathRel === false) {
            return false;
        }

        // Calculate file path to src
        $srcPathAbs = $baseDir . $srcPathRel;

        // Check that src file exists
        if (!@file_exists($srcPathAbs)) {
            return false;
        }


        // Calculate $destPathAbs and $destUrl
        // -------------------------------------
        $inUpload = self::isSourceInUpload($sourceUrl);

        if ((self::$options['destination-folder'] == 'mingled') && $inUpload) {
            // mingled
            if (self::$options['destination-extension'] == 'append') {
                $destPathAbs = $srcPathAbs . '.webp';
                $destUrl = $sourceUrl . '.webp';
            } else {
                $destPathAbs = preg_replace('/\\.(png|jpe?g)$/', '', $srcPathAbs) . '.webp';
                $destUrl = preg_replace('/\\.(png|jpe?g)$/', '', $sourceUrl) . '.webp';
            }
        } else {
            // separate (images that are not in upload are always put in separate)

            $relPathFromDocRoot = '/webp-express/webp-images/doc-root/';
            $relPathFromDocRoot .= PathHelper::getRelDir(realpath($_SERVER['DOCUMENT_ROOT']), $baseDir) . $srcPathRel;

            list ($contentDirAbs, $contentUrl) = self::$options['bases']['content'];

            $destPathAbs = $contentDirAbs . $relPathFromDocRoot . '.webp';
            $destUrl = $contentUrl . $relPathFromDocRoot . '.webp';
        }

        $webpMustExist = self::$options['only-for-webps-that-exists'];
        if ($webpMustExist && (!@file_exists($destPathAbs))) {
            return false;
        }
        return $destUrl;

    }


    /**
     *  Get url for webp
     *  returns second argument if no webp
     *
     *  @param $sourceUrl
     *  @param $returnValueOnFail
     */
    public static function getWebPUrl($sourceUrl, $returnValueOnFail)
    {
        if (!isset(self::$options)) {
            self::$options = json_decode(Option::getOption('webp-express-alter-html-options', null), true);
        }


        // Currently we do not handle relative urls - so we skip
        if (!preg_match('#^https?://#', $sourceUrl)) {
            return $returnValueOnFail;
        }

        switch (self::$options['image-types']) {
            case 0:
                return $returnValueOnFail;
            case 1:
                if (!preg_match('#(jpe?g)$#', $sourceUrl)) {
                    return $returnValueOnFail;
                }
                break;
            case 2:
                if (!preg_match('#(png)$#', $sourceUrl)) {
                    return $returnValueOnFail;
                }
                break;
            case 3:
                if (!preg_match('#(jpe?g|png)$#', $sourceUrl)) {
                    return $returnValueOnFail;
                }
                break;
        }

        if ((self::$options['only-for-webp-enabled-browsers']) && (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') === false)) {
            return $returnValueOnFail;
        }

        foreach (self::$options['bases'] as $id => list($baseDir, $baseUrl)) {
            if (Multisite::isMultisite() && ($id == 'uploads')) {
                $baseUrl = Paths::getUploadUrl();
                $baseDir = Paths::getUploadDirAbs();
            }

            $result = self::getWebPUrlInBase($sourceUrl, $baseUrl, $baseDir);
            if ($result !== false) {
                return $result;
            }
        }
        return $returnValueOnFail;
    }

/*
    public static function getWebPUrlOrSame($sourceUrl, $returnValueOnFail)
    {
        return self::getWebPUrl($sourceUrl, $sourceUrl);
    }*/

}
