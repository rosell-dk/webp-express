<?php

namespace WebPExpress;

use \WebPExpress\FileHelper;
use \WebPExpress\Multisite;
use \WebPExpress\PathHelper;

class Paths
{
    public static function areAllImageRootsWithinDocRoot() {
        if (!PathHelper::isDocRootAvailable()) {
            return false;
        }

        $roots = self::getImageRootIds();
        foreach ($roots as $dirId) {
            $dir = self::getAbsDirById($dirId);
            if (!PathHelper::canCalculateRelPathFromDocRootToDir($dir)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if we can use document root for calculating relative paths (which may not contain "/.." directory traversal)
     *
     * Note that this method allows document root to be outside open_basedir as long as document root is
     * non-empty AND it is possible to calculate relative paths to all image roots (including "index").
     * Here is a case when a relative CAN be calculated:
     * - Document root is configured to "/var/www/website" - which is also the absolute file path.
     * - open_basedir is set to "/var/www/website/wordpress"
     * - uploads is in "/var/www/website/wordpress/wp-content/uploads" (within open_basedir, as it should)
     * - "/wp-uploads" symlinks to "/var/www/website/wordpress")
     * - Wordpress has been configured to use "/wp-uploads" path for uploads.
     *
     * What happens?
     * First, it is tested if the configured upload path ("/wp-uploads") begins with the configured document root ("/var/www/website").
     * This fails.
     * Next, it is tested if the uploads path can be resolved. It can, as it is within the open_basedir.
     * Next, it is tested if the *resolved* the uploads path begins with the configured document root.
     * As "/var/www/website/wordpress/wp-content/uploads" begins with "/var/www/website", we have a match.
     * The relative path can be calculated to be "wordpress/wp-content/uploads".
     * Later, when the relative path is used, it will be used as $docRoot + "/" + $relPath, which
     * will be "/var/www/website/wordpress/wp-content/uploads". All is well.
     *
     * Here is a case where it CAN NOT be calculated:
     * - Document root is configured to "/the-website", which symlinks to "/var/www/website"
     * - open_basedir is set to "/var/www/website/wordpress"
     * - uploads is in "/var/www/website/wordpress/wp-content/uploads" and wordpress is configured to use that upload path.
     *
     * What happens?
     * First, it is tested if the configured upload path begins with the configured document root
     * "/var/www/website/wordpress/wp-content/uploads" does not begin with "/the-website", so it fails.
     * Next, it is tested if the *resolved* the uploads path begins with the configured document root.
     * The resolved uploads path is the same as the configured so it also fails.
     * Next, it is tested if Document root can be resolved. It can not, as the resolved path is not within open_basedir.
     * If it could, it would have been tested if the resolved path begins with the resolved document root and we would have
     * gotten a yes, and the relative path would have been "wordpress/wp-content/uploads" and it would work.
     * However: Document root could not be resolved and we could not get a result.
     * To sum the scenario up:
     * If document root is configured to a symlink which cannot be resolved then it will only be possible to get relative paths
     * when all other configured paths begins are relative to that symlink.
     */
    public static function canUseDocRootForRelPaths() {
        if (!PathHelper::isDocRootAvailable()) {
            return false;
        }
        return self::areAllImageRootsWithinDocRoot();
    }

    public static function canCalculateRelPathFromDocRootToDir($absPath) {
    }

    /**
     * Check if we can use document root for structuring the cache dir.
     *
     * In order to structure the images by doc-root, WebP Express needs all images to be within document root.
     * Does WebP Express in addition to this need to be able to resolve document root?
     * Short answer is yes.
     * The long answer is available as a comment inside ConvertHelperIndependent::getDestination()
     *
     */
    public static function canUseDocRootForStructuringCacheDir() {
        return (PathHelper::isDocRootAvailableAndResolvable() && self::canUseDocRootForRelPaths());
    }

    public static function docRootStatusText()
    {
        if (!PathHelper::isDocRootAvailable()) {
            if (!isset($_SERVER['DOCUMENT_ROOT'])) {
                return 'Unavailable (DOCUMENT_ROOT is not set in the global $_SERVER var)';
            }
            if ($_SERVER['DOCUMENT_ROOT'] == '') {
                return 'Unavailable (empty string)';
            }
            return 'Unavailable';
        }

        $imageRootsWithin = self::canUseDocRootForRelPaths();
        if (!PathHelper::isDocRootAvailableAndResolvable()) {
            $status = 'Available, but either non-existing or not within open_basedir.' .
                ($imageRootsWithin ? '' : ' And not all image roots are within that document root.');
        } elseif (!$imageRootsWithin) {
            $status = 'Available, but not all image roots are within that document root.';
        } else {
            $status = 'Available and its "realpath" is available too.';
        }
        if (self::canUseDocRootForStructuringCacheDir()) {
            $status .= ' Can be used for structuring cache dir.';
        } else {
            $status .= ' Cannot be used for structuring cache dir.';
        }
        return $status;
    }

    public static function getAbsDirId($absDir) {
        switch ($absDir) {
            case self::getContentDirAbs():
                return 'wp-content';
            case self::getIndexDirAbs():
                return 'index';
            case self::getHomeDirAbs():
                return 'home';
            case self::getPluginDirAbs():
                return 'plugins';
            case self::getUploadDirAbs():
                return 'uploads';
            case self::getThemesDirAbs():
                return 'themes';
            case self::getCacheDirAbs():
                return 'cache';
        }
        return false;
    }

    public static function getAbsDirById($dirId) {
        switch ($dirId) {
            case 'wp-content':
                return self::getContentDirAbs();
            case 'index':
                return self::getIndexDirAbs();
            case 'home':
                // "home" is still needed (used in PluginDeactivate.php)
                return self::getHomeDirAbs();
            case 'plugins':
                return self::getPluginDirAbs();
            case 'uploads':
                return self::getUploadDirAbs();
            case 'themes':
                return self::getThemesDirAbs();
            case 'cache':
                return self::getCacheDirAbs();
        }
        return false;
    }

    /**
     * Get ids for folders where SOURCE images may reside
     */
    public static function getImageRootIds() {
        return ['uploads', 'themes', 'plugins', 'wp-content', 'index'];
    }

    /**
     * Find which rootId a path belongs to.
     *
     * Note: If the root ids passed are ordered the way getImageRootIds() returns them, the root id
     * returned will be the "deepest"
     */
    public static function findImageRootOfPath($path, $rootIdsToSearch) {
        foreach ($rootIdsToSearch as $rootId) {
            if (PathHelper::isPathWithinExistingDirPath($path, self::getAbsDirById($rootId))) {
                return $rootId;
            }
        }
        return false;
    }

    public static function getImageRootsDefForSelectedIds($ids) {
        $canUseDocRootForRelPaths = self::canUseDocRootForRelPaths();

        $mapping = [];
        foreach ($ids as $rootId) {
            $obj = [
                'id' => $rootId,
            ];
            $absPath = self::getAbsDirById($rootId);
            if ($canUseDocRootForRelPaths) {
                $obj['rel-path'] = PathHelper::getRelPathFromDocRootToDirNoDirectoryTraversalAllowed($absPath);
            } else {
                $obj['abs-path'] = $absPath;
            }
            $obj['url'] = self::getUrlById($rootId);
            $mapping[] = $obj;
        }
        return $mapping;
    }

    public static function getImageRootsDef()
    {
        return self::getImageRootsDefForSelectedIds(self::getImageRootIds());
    }

    public static function filterOutSubRoots($rootIds)
    {
        // Get dirs of enabled roots
        $dirs = [];
        foreach ($rootIds as $rootId) {
            $dirs[] = self::getAbsDirById($rootId);
        }

        // Filter out dirs which are below other dirs
        $dirsToSkip = [];
        foreach ($dirs as $dirToExamine) {
            foreach ($dirs as $dirToCompareAgainst) {
                if ($dirToExamine == $dirToCompareAgainst) {
                    continue;
                }
                if (self::isDirInsideDir($dirToExamine, $dirToCompareAgainst)) {
                    $dirsToSkip[] = $dirToExamine;
                    break;
                }
            }
        }
        $dirs = array_diff($dirs, $dirsToSkip);

        // back to ids
        $result = [];
        foreach ($dirs as $dir) {
            $result[] = self::getAbsDirId($dir);
        }
        return $result;
    }

    public static function createDirIfMissing($dir)
    {
        if (!@file_exists($dir)) {
            // We use the wp_mkdir_p, because it takes care of setting folder
            // permissions to that of parent, and handles creating deep structures too
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

    /**
     *  Return absolute dir.
     *
     *  - Path is canonicalized (without resolving symlinks)
     *  - trailing dash is removed - we don't use that around here.
     *
     *  We do not resolve symlinks anymore. Information was lost that way.
     *  And in some cases we needed the unresolved path - for example in the .htaccess.
     */
    public static function getAbsDir($dir)
    {
        $dir = PathHelper::canonicalize($dir);
        return rtrim($dir, '/');
        /*
        $result = realpath($dir);
        if ($result === false) {
            $dir = PathHelper::canonicalize($dir);
        } else {
            $dir = $result;
        }*/

    }

    // ------------ Home Dir -------------

    // PS: Home dir is not the same as index dir.
    // For example, if Wordpress folder has been moved (method 2), the home dir could be below.
    public static function getHomeDirAbs()
    {
        if (!function_exists('get_home_path')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        return self::getAbsDir(get_home_path());
    }

    // ------------ Index Dir  (WP root dir) -------------
    // (The Wordpress installation dir- where index.php and wp-load.php resides)

    public static function getIndexDirAbs()
    {
        // We used to return self::getAbsDir(ABSPATH), which used realpath.
        // It has been changed now, as it seems we do not need realpath for ABSPATH, as it is defined
        // (in wp-load.php) as dirname(__FILE__) . "/" and according to this link, __FILE__ returns resolved paths:
        // https://stackoverflow.com/questions/3221771/how-do-you-get-php-symlinks-and-file-to-work-together-nicely
        // AND a user reported an open_basedir restriction problem thrown by realpath($_SERVER['DOCUMENT_ROOT']),
        // due to symlinking and opendir restriction (see #322)

        return rtrim(ABSPATH, '/');

        // TODO: read up on this, regarding realpath:
        // https://github.com/twigphp/Twig/issues/2707

    }

    // ------------ .htaccess dir -------------
    // (directory containing the relevant .htaccess)
    // (see https://github.com/rosell-dk/webp-express/issues/36)



    public static function canWriteHTAccessRulesHere($dirName) {
        return FileHelper::canEditOrCreateFileHere($dirName . '/.htaccess');
    }

    public static function canWriteHTAccessRulesInDir($dirId) {
        return self::canWriteHTAccessRulesHere(self::getAbsDirById($dirId));
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

    // ------------ Content Dir (the "WP" content dir) -------------

    public static function getContentDirAbs()
    {
        return self::getAbsDir(WP_CONTENT_DIR);
    }
    public static function getContentDirRel()
    {
        return PathHelper::getRelPathFromDocRootToDirNoDirectoryTraversalAllowed(self::getContentDirAbs());
    }
    public static function getContentDirRelToPluginDir()
    {
        return PathHelper::getRelDir(self::getPluginDirAbs(), self::getContentDirAbs());
    }
    public static function getContentDirRelToWebPExpressPluginDir()
    {
        return PathHelper::getRelDir(self::getWebPExpressPluginDirAbs(), self::getContentDirAbs());
    }


    public static function isWPContentDirMoved()
    {
        return (self::getContentDirAbs() != (ABSPATH . 'wp-content'));
    }

    public static function isWPContentDirMovedOutOfAbsPath()
    {
        return !(self::isDirInsideDir(self::getContentDirAbs(), ABSPATH));
    }

    // ------------ Themes Dir -------------

    public static function getThemesDirAbs()
    {
        return self::getContentDirAbs() . '/themes';
    }

    // ------------ WebPExpress Content Dir -------------
    // (the "webp-express" directory inside wp-content)

    public static function getWebPExpressContentDirAbs()
    {
        return self::getContentDirAbs() . '/webp-express';
    }

    public static function getWebPExpressContentDirRel()
    {
        return PathHelper::getRelPathFromDocRootToDirNoDirectoryTraversalAllowed(self::getWebPExpressContentDirAbs());
    }

    public static function createContentDirIfMissing()
    {
        return self::createDirIfMissing(self::getWebPExpressContentDirAbs());
    }

    // ------------ Upload Dir -------------
    public static function getUploadDirAbs()
    {
        $upload_dir = wp_upload_dir(null, false);
        return self::getAbsDir($upload_dir['basedir']);
    }
    public static function getUploadDirRel()
    {
        return PathHelper::getRelPathFromDocRootToDirNoDirectoryTraversalAllowed(self::getUploadDirAbs());
    }

    /*
    public static function getUploadDirAbs()
    {
        if ( defined( 'UPLOADS' ) ) {
            return ABSPATH . rtrim(UPLOADS, '/');
        } else {
            return self::getContentDirAbs() . '/uploads';
        }
    }*/

    public static function isUploadDirMovedOutOfWPContentDir()
    {
        return !(self::isDirInsideDir(self::getUploadDirAbs(), self::getContentDirAbs()));
    }

    public static function isUploadDirMovedOutOfAbsPath()
    {
        return !(self::isDirInsideDir(self::getUploadDirAbs(), ABSPATH));
    }

    // ------------ Config Dir -------------

    public static function getConfigDirAbs()
    {
        return self::getWebPExpressContentDirAbs() . '/config';
    }

    public static function getConfigDirRel()
    {
        return PathHelper::getRelPathFromDocRootToDirNoDirectoryTraversalAllowed(self::getConfigDirAbs());
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
        return self::getWebPExpressContentDirAbs() . '/webp-images';
    }

    public static function getCacheDirRelToDocRoot()
    {
        return PathHelper::getRelPathFromDocRootToDirNoDirectoryTraversalAllowed(self::getCacheDirAbs());
    }

    public static function getCacheDirForImageRoot($destinationFolder, $destinationStructure, $imageRootId)
    {
        if (($destinationFolder == 'mingled') && ($imageRootId == 'uploads')) {
            return self::getUploadDirAbs();
        }

        if ($destinationStructure == 'doc-root') {
            $relPath = PathHelper::getRelPathFromDocRootToDirNoDirectoryTraversalAllowed(
                self::getAbsDirById($imageRootId)
            );
            return self::getCacheDirAbs() . '/doc-root/' . $relPath;
        } else {
            return self::getCacheDirAbs() . '/' . $imageRootId;
        }
    }

    public static function createCacheDirIfMissing()
    {
        return self::createDirIfMissing(self::getCacheDirAbs());
    }

    // ------------ Log Dir -------------

    public static function getLogDirAbs()
    {
        return self::getWebPExpressContentDirAbs() . '/log';
    }

    // ------------ Bigger-than-source  dir -------------

    public static function getBiggerThanSourceDirAbs()
    {
        return self::getWebPExpressContentDirAbs() . '/webp-images-bigger-than-source';
    }

    // ------------ Plugin Dir (all plugins) -------------

    public static function getPluginDirAbs()
    {
        return self::getAbsDir(WP_PLUGIN_DIR);
    }


    public static function isPluginDirMovedOutOfAbsPath()
    {
        return !(self::isDirInsideDir(self::getPluginDirAbs(), ABSPATH));
    }

    public static function isPluginDirMovedOutOfWpContent()
    {
        return !(self::isDirInsideDir(self::getPluginDirAbs(), self::getContentDirAbs()));
    }

    // ------------ WebP Express Plugin Dir -------------

    public static function getWebPExpressPluginDirAbs()
    {
        return self::getAbsDir(WEBPEXPRESS_PLUGIN_DIR);
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

    public static function getUrlById($dirId) {
        switch ($dirId) {
            case 'wp-content':
                return self::getContentUrl();
            case 'index':
                return self::getHomeUrl();
            case 'home':
                return self::getHomeUrl();
            case 'plugins':
                return self::getPluginsUrl();
            case 'uploads':
                return self::getUploadUrl();
            case 'themes':
                return self::getThemesUrl();
        }
        return false;
    }

    /**
     * Get destination root url and path, provided rootId and some configuration options
     *
     * This method kind of establishes the overall structure of the cache dir.
     * (but not quite, as the logic is also in ConverterHelperIndependent::getDestination).
     *
     * @param  string  $rootId
     * @param  DestinationOptions  $destinationOptions
     *
     * @return array   url and abs-path of destination root
     */
    public static function destinationRoot($rootId, $destinationOptions)
    {
        if (($destinationOptions->mingled) && ($rootId == 'uploads')) {
            return [
                'url' => self::getUrlById('uploads'),
                'abs-path' => self::getUploadDirAbs()
            ];
        } else {

            // Its within these bases:
            $destUrl = self::getUrlById('wp-content') . '/webp-express/webp-images';
            $destPath = self::getAbsDirById('wp-content') . '/webp-express/webp-images';

            if (($destinationOptions->useDocRoot) && self::canUseDocRootForStructuringCacheDir()) {
                $relPathFromDocRootToSourceImageRoot = PathHelper::getRelPathFromDocRootToDirNoDirectoryTraversalAllowed(
                    self::getAbsDirById($rootId)
                );
                return [
                    'url' => $destUrl . '/doc-root/' . $relPathFromDocRootToSourceImageRoot,
                    'abs-path' => $destPath  . '/doc-root/' . $relPathFromDocRootToSourceImageRoot
                ];
            } else {
                $extraPath = '';
                if (is_multisite() && (get_current_blog_id() != 1)) {
                    $extraPath = '/sites/' . get_current_blog_id();   // #510
                }
                return [
                    'url' => $destUrl . '/' . $rootId . $extraPath,
                    'abs-path' => $destPath  . '/' . $rootId . $extraPath
                ];
            }
        }
    }

    public static function getRootAndRelPathForDestination($destinationPath, $imageRoots) {
        foreach ($imageRoots->getArray() as $i => $imageRoot) {
            $rootPath = $imageRoot->getAbsPath();
            if (strpos($destinationPath, realpath($rootPath)) !== false) {
                $relPath = substr($destinationPath, strlen(realpath($rootPath)) + 1);
                return [$imageRoot->id, $relPath];
            }
        }
        return ['', ''];
    }



    // PST:
    // appendOrSetExtension() have been copied from ConvertHelperIndependent.
    // TODO: I should complete the move ASAP.

    /**
     * Append ".webp" to path or replace extension with "webp", depending on what is appropriate.
     *
     * If destination-folder is set to mingled and destination-extension is set to "set" and
     * the path is inside upload folder, the appropriate thing is to SET the extension.
     * Otherwise, it is to APPEND.
     *
     * @param  string  $path
     * @param  string  $destinationFolder
     * @param  string  $destinationExt
     * @param  boolean $inUploadFolder
     */
    public static function appendOrSetExtension($path, $destinationFolder, $destinationExt, $inUploadFolder)
    {
        if (($destinationFolder == 'mingled') && ($destinationExt == 'set') && $inUploadFolder) {
            return preg_replace('/\\.(jpe?g|png)$/i', '', $path) . '.webp';
        } else {
            return $path . '.webp';
        }
    }

    /**
     * Get destination root url and path, provided rootId and some configuration options
     *
     * This method kind of establishes the overall structure of the cache dir.
     * (but not quite, as the logic is also in ConverterHelperIndependent::getDestination).
     *
     * @param  string  $rootId
     * @param  string  $relPath
     * @param  string  $destinationFolder     ("mingled" or "separate")
     * @param  string  $destinationExt        ('append' or 'set')
     * @param  string  $destinationStructure  ("doc-root" or "image-roots")
     *
     * @return array   url and abs-path of destination
     */
   /*
    public static function destinationPath($rootId, $relPath, $destinationFolder, $destinationExt, $destinationStructure) {

        // TODO: Current logic will not do!
        // We must use ConvertHelper::getDestination for the abs path.
        // And we must use logic from AlterHtmlHelper to get the URL
        // Perhaps this method must be abandonned

        $root = self::destinationRoot($rootId, $destinationFolder, $destinationStructure);
        $inUploadFolder = ($rootId == 'upload');
        $relPath = ConvertHelperIndependent::appendOrSetExtension($relPath, $destinationFolder, $destinationExt, $inUploadFolder);

        return [
            'abs-path' => $root['abs-path'] . '/' . $relPath,
            'url' => $root['url'] . '/' . $relPath,
        ];
    }

    public static function destinationPathConvenience($rootId, $relPath, $config) {
        return self::destinationPath(
            $rootId,
            $relPath,
            $config['destination-folder'],
            $config['destination-extension'],
            $config['destination-structure']
        );
    }*/

    public static function getDestinationPathCorrespondingToSource($source, $destinationOptions) {
        return Destination::getDestinationPathCorrespondingToSource(
            $source,
            Paths::getWebPExpressContentDirAbs(),
            Paths::getUploadDirAbs(),
            $destinationOptions,
            new ImageRoots(self::getImageRootsDef())
        );
    }

    public static function getUrlPathById($dirId) {
        return self::getUrlPathFromUrl(self::getUrlById($dirId));
    }

    public static function getHostNameOfUrl($url) {
        $urlComponents = parse_url($url);
        /* ie:
        (
            [scheme] => http
            [host] => we0
            [path] => /wordpress/uploads-moved
        )*/

        if (!isset($urlComponents['host'])) {
            return '';
        } else {
            return $urlComponents['host'];
        }
    }

    // Get complete home url (no trailing slash). Ie: "http://example.com/blog"
    public static function getHomeUrl()
    {
        if (!function_exists('home_url')) {
            // silence is golden?
            // bad joke. Need to handle this...
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


    public static function getUploadUrl()
    {
        $uploadDir = wp_upload_dir(null, false);
        return untrailingslashit($uploadDir['baseurl']);
    }

    public static function getUploadUrlPath()
    {
        return self::getUrlPathFromUrl(self::getUploadUrl());
    }

    public static function getContentUrl()
    {
        return untrailingslashit(content_url());
    }

    public static function getContentUrlPath()
    {
        return self::getUrlPathFromUrl(self::getContentUrl());
    }

    public static function getThemesUrl()
    {
        return self::getContentUrl() . '/themes';
    }

    /**
     *  Get Url to plugins (base)
     */
    public static function getPluginsUrl()
    {
        return untrailingslashit(plugins_url());
    }

    /**
     *  Get Url to WebP Express plugin (this is in fact an incomplete URL, you need to append ie '/webp-on-demand.php' to get a full URL)
     */
    public static function getWebPExpressPluginUrl()
    {
        return untrailingslashit(plugins_url(null, WEBPEXPRESS_PLUGIN));
    }

    public static function getWebPExpressPluginUrlPath()
    {
        return self::getUrlPathFromUrl(self::getWebPExpressPluginUrl());
    }

    public static function getWodFolderUrlPath()
    {
        return
            self::getWebPExpressPluginUrlPath() .
            '/wod';
    }

    public static function getWod2FolderUrlPath()
    {
        return
            self::getWebPExpressPluginUrlPath() .
            '/wod2';
    }

    public static function getWodUrlPath()
    {
        return
            self::getWodFolderUrlPath() .
            '/webp-on-demand.php';
    }

    public static function getWod2UrlPath()
    {
        return
            self::getWod2FolderUrlPath() .
            '/webp-on-demand.php';
    }

    public static function getWebPRealizerUrlPath()
    {
        return
            self::getWodFolderUrlPath() .
            '/webp-realizer.php';
    }

    public static function getWebPRealizer2UrlPath()
    {
        return
            self::getWod2FolderUrlPath()  .
            '/webp-realizer.php';
    }

    public static function getWebServiceUrl()
    {
        //return self::getWebPExpressPluginUrl() . '/wpc.php';
        //return self::getHomeUrl() . '/webp-express-server';
        return self::getHomeUrl() . '/webp-express-web-service';
    }

    public static function getUrlsAndPathsForTheJavascript()
    {
        return [
            'urls' => [
                'webpExpressRoot' => self::getWebPExpressPluginUrlPath(),
                'content' => self::getContentUrlPath(),
            ],
            'filePaths' => [
                'webpExpressRoot' => self::getWebPExpressPluginDirAbs(),
                'destinationRoot' => self::getCacheDirAbs(),
            ]
        ];
    }

    public static function getSettingsUrl()
    {
        if (!function_exists('admin_url')) {
            require_once ABSPATH . 'wp-includes/link-template.php';
        }
        if (Multisite::isNetworkActivated()) {
            // network_admin_url is also defined in link-template.php.
            return network_admin_url('settings.php?page=webp_express_settings_page');
        } else {
            return admin_url('options-general.php?page=webp_express_settings_page');
        }
    }

}
