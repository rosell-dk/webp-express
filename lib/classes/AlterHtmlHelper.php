<?php

namespace WebPExpress;

//use AlterHtmlInit;
use \WebPExpress\Config;
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

    public static function getOptions() {
      if (!isset(self::$options)) {
          self::$options = json_decode(Option::getOption('webp-express-alter-html-options', null), true);
          if (!isset(self::$options['prevent-using-webps-larger-than-original'])) {
              self::$options['prevent-using-webps-larger-than-original'] = true;
          }
          // Set scope if it isn't there (it wasn't cached until 0.17.5)
          if (!isset(self::$options['scope'])) {
            $config = Config::loadConfig();
            if ($config) {
              $config = Config::fix($config, false);
              self::$options['scope'] = $config['scope'];

              Option::updateOption(
                  'webp-express-alter-html-options',
                  json_encode(self::$options, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK),
                  true
              );
            }
          }
      }
    }

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
     *  PS: NOT USED ANYMORE!
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

    // NOT USED ANYMORE
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
     * Get url for webp from source url,  (if ), given a certain baseUrl / baseDir.
     * Base can for example be uploads or wp-content.
     *
     * returns false:
     * - if no source file found in that base
     * - if source file is found but webp file isn't there and the `only-for-webps-that-exists` option is set
     * - if webp is marked as bigger than source
     *
     *  @param  string  $sourceUrl   Url of source image (ie http://example.com/wp-content/image.jpg)
     *  @param  string  $rootId      Id (created in Config::updateAutoloadedOptions). Ie "uploads", "content" or any image root id
     *  @param  string  $baseUrl     Base url of source image (ie http://example.com/wp-content)
     *  @param  string  $baseDir     Base dir of source image (ie /var/www/example.com/wp-content)
     */
    public static function getWebPUrlInImageRoot($sourceUrl, $rootId, $baseUrl, $baseDir)
    {


        $srcPathRel = self::getRelUrlPath($sourceUrl, $baseUrl);

        if ($srcPathRel === false) {
            return false;
        }

        // Calculate file path to source
        $srcPathAbs = $baseDir . $srcPathRel;

        // Check that source file exists
        if (!@file_exists($srcPathAbs)) {
            return false;
        }

        if (file_exists($srcPathAbs . '.do-not-convert')) {
            return false;
        }
        if (file_exists($srcPathAbs . '.dontreplace')) {
            return false;
        }

        // Calculate destination of webp (both path and url)
        // ----------------------------------------

        // We are calculating: $destPathAbs and $destUrl.

        // Make sure the options are loaded (and fixed)
        self::getOptions();
        $destinationOptions = new DestinationOptions(
            self::$options['destination-folder'] == 'mingled',
            self::$options['destination-structure'] == 'doc-root',
            self::$options['destination-extension'] == 'set',
            self::$options['scope']
        );

        if (!isset(self::$options['scope']) || !in_array($rootId, self::$options['scope'])) {
            return false;
        }

        $destinationRoot = Paths::destinationRoot($rootId, $destinationOptions);

        $relPathFromImageRootToSource = PathHelper::getRelDir(
            realpath(Paths::getAbsDirById($rootId)),  // note: In multisite (subfolders), it contains ie "/site/2/"
            realpath($srcPathAbs)
        );
        $relPathFromImageRootToDest = ConvertHelperIndependent::appendOrSetExtension(
            $relPathFromImageRootToSource,
            self::$options['destination-folder'],
            self::$options['destination-extension'],
            ($rootId == 'uploads')
        );
        $destPathAbs = $destinationRoot['abs-path'] . '/' . $relPathFromImageRootToDest;
        $webpMustExist = self::$options['only-for-webps-that-exists'];
        if ($webpMustExist && (!@file_exists($destPathAbs))) {
            return false;
        }

        // check if webp is marked as bigger than source
        /*
        $biggerThanSourcePath = Paths::getBiggerThanSourceDirAbs() . '/' . $rootId . '/' . $relPathFromImageRootToDest;
        if (@file_exists($biggerThanSourcePath)) {
            return false;
        }*/

        // check if webp is larger than original
        if (self::$options['prevent-using-webps-larger-than-original']) {
            if (BiggerThanSource::bigger($srcPathAbs, $destPathAbs)) {
                return false;
            }
        }

        $destUrl = $destinationRoot['url'] . '/' . $relPathFromImageRootToDest;

        // Fix scheme (use same as source)
        $sourceUrlComponents = parse_url($sourceUrl);
        $destUrlComponents = parse_url($destUrl);
        $port = isset($sourceUrlComponents['port']) ? ":" . $sourceUrlComponents['port'] : "";
        $result = $sourceUrlComponents['scheme'] . '://' . $sourceUrlComponents['host'] . $port . $destUrlComponents['path'];

        /*
        error_log(
            "getWebPUrlInImageRoot:\n" .
            "- url: " . $sourceUrl . "\n" .
            "- baseUrl: " . $baseUrl . "\n" .
            "- baseDir: " . $baseDir . "\n" .
            "- root id: " . $rootId . "\n" .
            "- root abs: " . Paths::getAbsDirById($rootId) . "\n" .
            "- destination root (abs): " . $destinationRoot['abs-path'] . "\n" .
            "- destination root (url): " . $destinationRoot['url'] . "\n" .
            "- rel: " . $srcPathRel . "\n" .
            "- srcPathAbs: " . $srcPathAbs . "\n" .
            '- relPathFromImageRootToSource: ' . $relPathFromImageRootToSource . "\n" .
            '- get_blog_details()->path: '  . get_blog_details()->path . "\n" .
            "- result: " . $result . "\n"
        );*/
        return $result;
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
        // Get the options
        self::getOptions();

        // Fail for webp-disabled  browsers (when "only-for-webp-enabled-browsers" is set)
        if (self::$options['only-for-webp-enabled-browsers']) {
            if (!isset($_SERVER['HTTP_ACCEPT']) || (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') === false)) {
                return $returnValueOnFail;
            }
        }

        // Fail for relative urls. Wordpress doesn't use such very much anyway
        if (!preg_match('#^https?://#', $sourceUrl)) {
            return $returnValueOnFail;
        }

        // Fail if the image type isn't enabled
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


        //error_log('source url:' . $sourceUrl);

        // Try all image roots
        foreach (self::$options['scope'] as $rootId) {
            $baseDir = Paths::getAbsDirById($rootId);
            $baseUrl = Paths::getUrlById($rootId);

            if (Multisite::isMultisite() && ($rootId == 'uploads')) {
                $baseUrl = Paths::getUploadUrl();
                $baseDir = Paths::getUploadDirAbs();
            }

            $result = self::getWebPUrlInImageRoot($sourceUrl, $rootId, $baseUrl, $baseDir);
            if ($result !== false) {
                return $result;
            }

            // Try the hostname aliases.
            if (!isset(self::$options['hostname-aliases'])) {
                continue;
            }
            $hostnameAliases = self::$options['hostname-aliases'];

            $hostname = Paths::getHostNameOfUrl($baseUrl);
            $baseUrlComponents = parse_url($baseUrl);
            $sourceUrlComponents = parse_url($sourceUrl);
            // ie: [scheme] => http, [host] => we0, [path] => /wordpress/uploads-moved

            if ((!isset($baseUrlComponents['host'])) || (!isset($sourceUrlComponents['host']))) {
                continue;
            }

            foreach ($hostnameAliases as $hostnameAlias) {

                if ($sourceUrlComponents['host'] != $hostnameAlias) {
                    continue;
                }
                //error_log('hostname alias:' . $hostnameAlias);

                $baseUrlOnAlias = $baseUrlComponents['scheme'] . '://' . $hostnameAlias . $baseUrlComponents['path'];
                //error_log('baseurl (alias):' . $baseUrlOnAlias);

                $result = self::getWebPUrlInImageRoot($sourceUrl, $rootId, $baseUrlOnAlias, $baseDir);
                if ($result !== false) {
                    $resultUrlComponents = parse_url($result);
                    return $sourceUrlComponents['scheme'] . '://' . $hostnameAlias . $resultUrlComponents['path'];
                }
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
