<?php

namespace WebPExpress;

require_once "PathHelper.php";

use \WebPExpress\PathHelper;

class Paths
{

    public static function createDirIfMissing($dir)
    {
        if (!file_exists($dir)) {
          wp_mkdir_p($dir);
        }
        return file_exists($dir);
    }

    // ------------ Home Dir -------------
    // (directory containing the .htaccess)

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

    // ------------ Content Dir -------------
    // (the "webp-express" directory inside wp-content)

    public static function getContentDirAbs()
    {
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

    // ------------ Plugin Dir -------------

    public static function getPluginDirAbs()
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
        $path = untrailingslashit(parse_url($url)['path']);
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

    /**
     *  Calculate path to existing image, excluding
     *  (relative to document root)
     *  Ie: "/webp-express-test/wordpress/wp-content/webp-express/webp-images/webp-express-test/wordpress/"
     *  This is needed for the .htaccess
     */
    public static function getPathToExisting()
    {
        return self::getCacheDirRel() . '/' . self::getHomeDirRel();
    }

    public static function getUrlsAndPathsForTheJavascript()
    {
        return [
            'urls' => [
                'webpExpressRoot' => self::getPluginUrlPath(),
                /*
                'webpExpressRoot' => untrailingslashit($converterUrlPath),
                'convert' => untrailingslashit($converterUrlPath) . 'convert.php',
                'destinationRoot' => untrailingslashit($destinationRootUrlPath),
                'wpRoot' => untrailingslashit($wpUrlPath),  // ie "/blog" or ""
                'converterUrlPathRelativeToSiteUrl' => $converterUrlPathRelativeToSiteUrl,
                'siteUrlPathRelativeToConverterPath' => $siteUrlPathRelativeToConverterPath
                */
            ],
            'filePaths' => [
                'webpExpressRoot' => self::getPluginDirAbs(),
                'destinationRoot' => self::getCacheDirAbs()
                /*
                'wordpressRoot' => $wordpressRoot,
                'destinationRoot' => $destinationRoot,
                'destinationRootRelativeToWebExpressRoot' => $destinationRootRelativeToWebExpressRoot,
                'destinationRootRelativeToWordpressRoot' => $destinationRootRelativeToWordpressRoot
                */
            ]
        ];
    }

    // Old
    public static function calculateUrlsAndPathsOld()
    {
      // Calculate URL's
      $upload_dir = wp_upload_dir();
      $destinationRootUrlPathAbs = $upload_dir['baseurl'] . '/' . 'webp-express';

      // Calculate url path to directory of image converter
      $pluginUrlAbs = plugins_url('', WEBPEXPRESS_PLUGIN);

      $converterUrlPath = parse_url($pluginUrlAbs)['path'];

      // Calculate destination root url path
      $destinationRootUrlPath = parse_url($destinationRootUrlPathAbs)['path'];

      // Calculate Wordpress url path
      // If site for example is accessed example.com/blog/, then the url path is "blog"
      $wpUrlPath = trailingslashit(parse_url(site_url())['path']);    // ie "/blog/" or "/"

      // TODO: WHOOPSIE! Shouldn't we use the home_url rather than site_url ?

      $converterUrlPathRelativeToSiteUrl = WebPExpressHelpers::get_rel_dir(untrailingslashit($wpUrlPath), $converterUrlPath);
      $siteUrlPathRelativeToConverterPath = WebPExpressHelpers::get_rel_dir($converterUrlPath, untrailingslashit($wpUrlPath));


      // Calculate file dirs
      // --------------------

      $destinationRoot = trailingslashit($upload_dir['basedir']) . 'webp-express';

      $WebExpressRoot = untrailingslashit(WEBPEXPRESS_PLUGIN_DIR);

      $destinationRootRelativeToWebExpressRoot = untrailingslashit(WebPExpressHelpers::get_rel_dir($WebExpressRoot, $destinationRoot));

      // $destinationRoot is ie '/webp-express-test/wordpress/wp-content/uploads/webp-express/'
      // $wordpressRoot is ie '/mnt/Work/playground/webp-express-test/wordpress/'
      $wordpressRoot = untrailingslashit(ABSPATH);
      $destinationRootRelativeToWordpressRoot = untrailingslashit(WebPExpressHelpers::get_rel_dir($wordpressRoot . '/', $destinationRoot));

      return [
          'urls' => [
              'webpExpressRoot' => untrailingslashit($converterUrlPath),
              'convert' => untrailingslashit($converterUrlPath) . 'convert.php',
              'destinationRoot' => untrailingslashit($destinationRootUrlPath),
              'wpRoot' => untrailingslashit($wpUrlPath),  // ie "/blog" or ""
              'converterUrlPathRelativeToSiteUrl' => $converterUrlPathRelativeToSiteUrl,
              'siteUrlPathRelativeToConverterPath' => $siteUrlPathRelativeToConverterPath
          ],
          'filePaths' => [
              'wordpressRoot' => $wordpressRoot,
              'destinationRoot' => $destinationRoot,
              'webpExpressRoot' => $WebExpressRoot,
              'destinationRootRelativeToWebExpressRoot' => $destinationRootRelativeToWebExpressRoot,
              'destinationRootRelativeToWordpressRoot' => $destinationRootRelativeToWordpressRoot
          ],
          // TODO: read up on this, and make complete tests
          // https://wordpress.stackexchange.com/questions/188448/whats-the-difference-between-get-home-path-and-abspath
          'pathsForHtaccess' => [
              //$basePath, $destinationRoot, $scriptPath
              'basePath' => untrailingslashit(WebPExpressHelpers::get_rel_dir($_SERVER['DOCUMENT_ROOT'], untrailingslashit(ABSPATH))),
              'destinationRoot' => $destinationRootRelativeToWordpressRoot,   //  Where to place converted files, relative to the base path.
              'scriptPath' => untrailingslashit($converterUrlPathRelativeToSiteUrl),

              //'abspath' => ABSPATH,
              //'dr' => $_SERVER['DOCUMENT_ROOT'],
              //'bp' => str_replace($_SERVER['DOCUMENT_ROOT'] . '/', '', untrailingslashit(ABSPATH)),
          ]
      ];
    }
}
