<?php

namespace WebPExpress;

/**
 *  This class is not used yet! - and not finished!
 *  It is the beginning of a refactor, where code is to be moved from AlterHtmlHelper to here
 */
class DestinationUrl
{

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
     *  Get url for webp from source url,  (if ), given a certain baseUrl / baseDir.
     *  Base can for example be uploads or wp-content.
     *
     *  returns false
     *  - if no source file found in that base
     *  - or source file is found but webp file isn't there and the `only-for-webps-that-exists` option is set
     *
     *  @param  string  $sourceUrl   Url of source image (ie http://example.com/wp-content/image.jpg)
     *  @param  string  $rootId      Id (created in Config::updateAutoloadedOptions). Ie "uploads", "content" or any image root id
     *  @param  string  $baseUrl     Base url of source image (ie http://example.com/wp-content)
     *  @param  string  $baseDir     Base dir of source image (ie /var/www/example.com/wp-content)
     *  @param  object  $destinationOptions
     */
    public static function getWebPUrlInImageRoot($sourceUrl, $rootId, $baseUrl, $baseDir, $destinationOptions)
    {
        //error_log('getWebPUrlInImageRoot:' . $sourceUrl . ':' . $baseUrl . ':' . $baseDir);


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

        // Calculate destination of webp (both path and url)
        // ----------------------------------------

        // We are calculating: $destPathAbs and $destUrl.

        if (!isset($destinationOptions->scope) || !in_array($rootId, $destinationOptions->scope)) {
            return false;
        }

        $destinationRoot = Paths::destinationRoot(
            $rootId,
            $destinationOptions
        );

        $relPathFromImageRootToSource = PathHelper::getRelDir(
            realpath(Paths::getAbsDirById($rootId)),
            realpath($srcPathAbs)
        );
        $relPathFromImageRootToDest = Destination::appendOrSetExtension(
            $relPathFromImageRootToSource,
            $destinationOptions->mingled,
            $destinationOptions->replaceExt,
            ($rootId == 'uploads')
        );
        $destPathAbs = $destinationRoot['abs-path'] . '/' . $relPathFromImageRootToDest;
        $webpMustExist = self::$options['only-for-webps-that-exists'];
        if ($webpMustExist && (!@file_exists($destPathAbs))) {
            return false;
        }

        $destUrl = $destinationRoot['url'] . '/' . $relPathFromImageRootToDest;

        // Fix scheme (use same as source)
        $sourceUrlComponents = parse_url($sourceUrl);
        $destUrlComponents = parse_url($destUrl);
        $port = isset($sourceUrlComponents['port']) ? ":" . $sourceUrlComponents['port'] : "";
        return $sourceUrlComponents['scheme'] . '://' . $sourceUrlComponents['host'] . $port . $destUrlComponents['path'];
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

            //error_log('baseurl: ' . $baseUrl);
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
