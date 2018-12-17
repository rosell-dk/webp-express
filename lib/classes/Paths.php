<?php

namespace WebPExpress;

include_once "PathHelper.php";
use \WebPExpress\PathHelper;

include_once "FileHelper.php";
use \WebPExpress\FileHelper;

class Paths
{

    public static function createDirIfMissing($dir)
    {
        if (!file_exists($dir)) {
          wp_mkdir_p($dir);
        }
        return file_exists($dir);
    }

    /**
     *  Find out if $dir1 is inside - or equal to - $dir2
     */
    public static function isDirInsideDir($dir1, $dir2)
    {
        $rel = PathHelper::getRelDir($dir2, $dir1);
        return (substr($rel, 0, 3) != '../');
    }

    // ------------ Home Dir -------------

    public static function getHomeDirAbs()
    {
        if (!function_exists('get_home_path')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        return rtrim(get_home_path(), '/');
    }

    public static function getHomeDirRel()
    {
        return PathHelper::getRelDir($_SERVER['DOCUMENT_ROOT'], self::getHomeDirAbs());
    }

    // ------------ Index Dir  -------------
    // (The Wordpress installation dir)

    public static function getIndexDirAbs()
    {
        return rtrim(ABSPATH, '/');
    }

    public static function getIndexDirRel()
    {
        return PathHelper::getRelDir($_SERVER['DOCUMENT_ROOT'], self::getIndexDirAbs());
    }


    // ------------ .htaccess dir -------------
    // (directory containing the relevant .htaccess)
    // (see https://github.com/rosell-dk/webp-express/issues/36)



    public static function canWriteHTAccessRulesHere($dirName) {
        return FileHelper::canEditOrCreateFileHere($dirName . '/.htaccess');
    }

    public static function returnFirstWritableHTAccessDir($dirs)
    {
        foreach ($dirs as $dir) {
            if (self::canWriteHTAccessRulesHere($dir)) {
                return $dir;
            }
        }
        return false;
    }

    // ------------ WP Content Dir -------------
    public static function getWPContentDirAbs()
    {
        return rtrim(WP_CONTENT_DIR, '/');
    }
    public static function getWPContentDirRel()
    {
        return PathHelper::getRelDir($_SERVER['DOCUMENT_ROOT'], self::getWPContentDirAbs());
    }

    public static function isWPContentDirMoved()
    {
        return (self::getWPContentDirAbs() != (ABSPATH . 'wp-content'));
    }

    public static function isWPContentDirMovedOutOfAbsPath()
    {
        return !(self::isDirInsideDir(self::getWPContentDirAbs(), ABSPATH));
    }


    // ------------ Content Dir -------------
    // (the "webp-express" directory inside wp-content)

    public static function getContentDirAbs()
    {
        if (!defined(WP_CONTENT_DIR)) {

        }
        return rtrim(WP_CONTENT_DIR, '/') . '/webp-express';
    }

    public static function getContentDirRel()
    {
        return PathHelper::getRelDir($_SERVER['DOCUMENT_ROOT'], self::getContentDirAbs());
    }

    public static function createContentDirIfMissing()
    {
        return self::createDirIfMissing(self::getContentDirAbs());
    }

    // ------------ Upload Dir -------------
    public static function getUploadDirAbs()
    {
        $upload_dir = wp_upload_dir(null, false);
        return $upload_dir['basedir'];
    }
    public static function getUploadDirRel()
    {
        return PathHelper::getRelDir($_SERVER['DOCUMENT_ROOT'], self::getUploadDirAbs());
    }

    /*
    public static function getUploadDirAbs()
    {
        if ( defined( 'UPLOADS' ) ) {
            return ABSPATH . rtrim(UPLOADS, '/');
        } else {
            return self::getWPContentDirAbs() . '/uploads';
        }
    }*/

    public static function isUploadDirMovedOutOfWPContentDir()
    {
        return !(self::isDirInsideDir(self::getUploadDirAbs(), self::getWPContentDirAbs()));
    }

    public static function isUploadDirMovedOutOfAbsPath()
    {
        return !(self::isDirInsideDir(self::getUploadDirAbs(), ABSPATH));
    }

    // ------------ Config Dir -------------

    public static function getConfigDirAbs()
    {
        return self::getContentDirAbs() . '/config';
    }

    public static function getConfigDirRel()
    {
        return PathHelper::getRelDir($_SERVER['DOCUMENT_ROOT'], self::getConfigDirAbs());
    }

    public static function createConfigDirIfMissing()
    {
        $configDir = self::getConfigDirAbs();
        // Using code from Wordfence bootstrap.php...
        // Why not simply use wp_mkdir_p ? - it sets the permissions to same as parent. Isn't that better?
        // or perhaps not... - Because we need write permissions in the config dir.
        if (!is_dir($configDir)) {
            @mkdir($configDir, 0775);
            @chmod($configDir, 0775);
            @file_put_contents(rtrim($configDir . '/') . '/.htaccess', <<<APACHE
<IfModule mod_authz_core.c>
Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
Order deny,allow
Deny from all
</IfModule>
APACHE
            );
            @chmod($configDir . '/.htaccess', 0664);
        }
        return is_dir($configDir);
    }

    public static function getConfigFileName()
    {
        return self::getConfigDirAbs() . '/config.json';
    }

    public static function getWodOptionsFileName()
    {
        return self::getConfigDirAbs() . '/wod-options.json';
    }

    // ------------ Cache Dir -------------

    public static function getCacheDirAbs()
    {
        return self::getContentDirAbs() . '/webp-images';
    }

    public static function getCacheDirRel()
    {
        return PathHelper::getRelDir($_SERVER['DOCUMENT_ROOT'], self::getCacheDirAbs());
    }

    public static function createCacheDirIfMissing()
    {
        return self::createDirIfMissing(self::getCacheDirAbs());
    }

    // ------------ Plugin Dir (all plugins) -------------

    public static function getPluginDirAbs()
    {
        return untrailingslashit(WP_PLUGIN_DIR);
    }

    public static function getPluginDirRel()
    {
        return PathHelper::getRelDir($_SERVER['DOCUMENT_ROOT'], self::getPluginDirAbs());
    }

    public static function isPluginDirMovedOutOfAbsPath()
    {
        return !(self::isDirInsideDir(self::getPluginDirAbs(), ABSPATH));
    }

    public static function isPluginDirMovedOutOfWpContent()
    {
        return !(self::isDirInsideDir(self::getPluginDirAbs(), self::getWPContentDirAbs()));
    }

    // ------------ WebP Express Plugin Dir -------------

    public static function getWebPExpressPluginDirAbs()
    {
        return untrailingslashit(WEBPEXPRESS_PLUGIN_DIR);
    }


    // ------------------------------------
    // ---------    Url paths    ----------
    // ------------------------------------

    /**
     *  Get url path (relative to domain) from absolute url.
     *  Ie: "http://example.com/blog" => "blog"
     *  Btw: By "url path" we shall always mean relative to domain
     *       By "url" we shall always mean complete URL (with domain and everything)
     *                                (or at least something that starts with it...)
     *
     *  Also note that in this library, we never returns trailing or leading slashes.
     */
    public static function getUrlPathFromUrl($url)
    {
        $parsed = parse_url($url);
        if (!isset($parsed['path'])) {
            return '';
        }
        if (is_null($parsed['path'])) {
            return '';
        }
        $path = untrailingslashit($parsed['path']);
        return ltrim($path, '/\\');
    }

    // Get complete home url (no trailing slash). Ie: "http://example.com/blog"
    public static function getHomeUrl()
    {
        if (!function_exists('get_home_url')) {
            // silence is golden?
        }
        return untrailingslashit(home_url());
    }

    /** Get home url, relative to domain. Ie "" or "blog"
     *  If home url is for example http://example.com/blog/, the result is "blog"
     */
    public static function getHomeUrlPath()
    {
        return self::getUrlPathFromUrl(self::getHomeUrl());
    }

    /**
     *  Get Url to plugin (this is in fact an incomplete URL, you need to append ie '/webp-on-demand.php' to get a full URL)
     */
    public static function getPluginUrl()
    {
        return untrailingslashit(plugins_url('', WEBPEXPRESS_PLUGIN));
    }

    public static function getPluginUrlPath()
    {
        return self::getUrlPathFromUrl(self::getPluginUrl());
    }

    public static function getWodUrlPath()
    {
        return self::getPluginUrlPath() . '/wod/webp-on-demand.php';
    }

    public static function getWebServiceUrl()
    {
        //return self::getPluginUrl() . '/wpc.php';
        //return self::getHomeUrl() . '/webp-express-server';
        return self::getHomeUrl() . '/webp-express-web-service';
    }

    /**
     *  Calculate path to existing image, excluding
     *  (relative to document root)
     *  Ie: "/webp-express-test/wordpress/wp-content/webp-express/webp-images/webp-express-test/wordpress/"
     *  This is needed for the .htaccess
     */
    public static function getPathToExisting()
    {
        return self::getCacheDirRel() . '/doc-root/' . self::getHomeDirRel();
    }

    public static function getUrlsAndPathsForTheJavascript()
    {
        return [
            'urls' => [
                'webpExpressRoot' => self::getPluginUrlPath(),
            ],
            'filePaths' => [
                'webpExpressRoot' => self::getWebPExpressPluginDirAbs(),
                'destinationRoot' => self::getCacheDirAbs(),
                'configRelToDocRoot' => self::getConfigDirRel()
            ]
        ];
    }

    /* Get complete url to admin (no trailing slash) */
    public static function getAdminUrl()
    {
        if (!function_exists('get_admin_url')) {
            require_once ABSPATH . 'wp-includes/link-template.php';
        }
        return untrailingslashit(get_admin_url());
    }

    public static function getSettingsUrl()
    {
        return self::getAdminUrl() . '/' . 'options-general.php?page=webp_express_settings_page';
    }

}
