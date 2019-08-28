<?php

namespace WebPExpress;

class PathHelper
{

    public static function isDocRootAvailable() {

        //return false;

        if (!isset($_SERVER['DOCUMENT_ROOT'])) {
            return false;
        }
        if ($_SERVER['DOCUMENT_ROOT'] == '') {
            return false;
        }
        return true;
    }

    /**
     * Test if a path exists as is resolvable (will be unless it is outside open_basedir)
     *
     * @param  string  $absPath  The path to test (must be absolute. symlinks allowed)
     * @return  boolean  The result
     */
    public static function pathExistsAndIsResolvable($absPath) {
        if (!@realpath($absPath)) {
            return false;
        }
        return true;
    }

    /**
     * Test if document root is available, exists and symlinks are resolvable (resolved path is within open basedir)
     *
     * @return  boolean  The result
     */
    public static function isDocRootAvailableAndResolvable() {
        return (
            self::isDocRootAvailable() &&
            self::pathExistsAndIsResolvable($_SERVER['DOCUMENT_ROOT'])
        );
    }

    /**
     * Calculate relative path from document root to a given absolute path (must exist and be resolvable) - if possible AND
     * if it can be done without directory traversal.
     *
     * The function is designed with the usual folders in mind (index, uploads, wp-content, plugins), which all presumably
     * exists and are within open_basedir.
     *
     * @param  string  $dir  An absolute path (may contain symlinks). The path must exist and be resolvable.
     * @throws \Exception    If it is not possible to get such path (ie if doc-root is unavailable or the dir is outside doc-root)
     * @return string  Relative path to document root or empty string if document root is unavailable
     */
    public static function getRelPathFromDocRootToDirNoDirectoryTraversalAllowed($dir)
    {
        if (!self::isDocRootAvailable()) {
            throw new \Exception('Cannot calculate relative path from document root to dir, as document root is not available');
        }
        if (self::isDocRootAvailableAndResolvable()) {

            if (!self::pathExistsAndIsResolvable($dir)) {
                throw new \Exception('Cannot calculate relative path from document root to dir. The path given is not resolvable (realpath fails)');
            }
            $relPath = self::getRelDir(realpath($_SERVER['DOCUMENT_ROOT']), realpath($dir));

            // Check if relPath starts with "../" (it may not)
            if (strpos($relPath, '../') === 0) {
                throw new \Exception('Cannot calculate relative path from document root to dir. The path given is not within document root');
            }
            return $relPath;
        } else {
            // We cannot get the resolved doc-root.
            // This might be ok as long as the (resolved) path we are examining begins with the configured doc-root.
            $relPath = self::getRelDir($_SERVER['DOCUMENT_ROOT'], $dir);

            // Check if relPath starts with "../" (it may not)
            if (strpos($relPath, '../') === 0) {

                // Well, that did not work. We can try the resolved path instead.
                if (!self::pathExistsAndIsResolvable($dir)) {
                    throw new \Exception('Cannot calculate relative path from document root to dir. The path given is not resolvable (realpath fails)');
                }

                $relPath = self::getRelDir($_SERVER['DOCUMENT_ROOT'], realpath($dir));
                if (strpos($relPath, '../') === 0) {

                    // That failed too.
                    // Either it is in fact outside document root or it is because of a special setup.
                    throw new \Exception(
                        'Cannot calculate relative path from document root to dir. Either the path given is not within the configured document root or ' .
                        'it is because of a special setup. The document root is outside open_basedir. If it is also symlinked, but the other Wordpress paths ' .
                        'are not using that same symlink, it will not be possible to calculate the relative path.'
                    );
                }
            }
            return $relPath;
        }
    }

    public static function canCalculateRelPathFromDocRootToDir($dir)
    {
        try {
            $relPath = self::getRelPathFromDocRootToDirNoDirectoryTraversalAllowed($dir);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     *  Find closest existing folder with symlinks expandend, using realpath.
     *
     *  Note that if the input or the closest existing folder is outside open_basedir, no folder will
     *  be found and an empty string will be returned.
     *
     *  @return  string  closest existing path or empty string if none found (due to open_basedir restriction)
     */
    public static function findClosestExistingFolderSymLinksExpanded($input) {

        // The strategy is to first try the supplied directory. If it fails, try the parent, etc.
        $dir = $input;

        // We count the levels up to avoid infinite loop - as good practice. It ought not to get that far
        $levelsUp = 0;

        while ($levelsUp < 100) {
            // We suppress warning because we are aware that we might get a
            // open_basedir restriction warning.
            $realPathResult = @realpath($dir);
            if ($realPathResult !== false) {
                return $realPathResult;
            }
            // Stop at root. This will happen if the original path is outside basedir.
            if (($dir == '/') || (strlen($dir) < 4)) {
                return '';
            }
            // Peal off one directory
            $dir = @dirname($dir);
            $levelsUp++;
        }
        return '';
    }

    /**
     * Look if filepath is within a dir path (both by string matching and by using realpath, see notes).
     *
     * Note that the naive string match does not resolve '..'. You might want to call ::canonicalize first.
     * Note that the realpath match requires:  1. that the dir exist and is within open_basedir
     *                                         2. that the closest existing folder within filepath is within open_basedir
     *
     * @param  string  $filePath      Path to file. It may be non-existing.
     * @param  string  $dirPath       Path to dir. It must exist and be within open_basedir in order for the realpath match to execute.
     */
    public static function isFilePathWithinDirPath($filePath, $dirPath)
    {
        // See if $filePath begins with $dirPath + '/'.
        if (strpos($filePath, $dirPath . '/') === 0) {
            return true;
        }

        if (strpos(self::canonicalize($filePath), self::canonicalize($dirPath) . '/') === 0) {
            return true;
        }


        // Also try with symlinks expanded.
        // As symlinks can only be retrieved with realpath and realpath fails with non-existing paths,
        // we settle with checking if closest existing folder in the filepath is within the dir.
        // If that is the case, then surely, the complete filepath is also within the dir.
        // Note however that it might be that the closest existing folder is not within the dir, while the
        // file would be (if it existed)
        // For WebP Express, we are pretty sure that the dirs we are checking against (uploads folder,
        // wp-content, plugins folder) exists. So getting the closest existing folder should be sufficient.
        // but could it be that these are outside open_basedir on some setups? Perhaps on a few systems.
        if (self::pathExistsAndIsResolvable($dirPath)) {
            $closestExistingDirOfFile = PathHelper::findClosestExistingFolderSymLinksExpanded($filePath);
            if (strpos($closestExistingDirOfFile,  realpath($dirPath) . '/') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Look if path is within a dir path. Also tries expanding symlinks
     *
     * @param  string  $path          Path to examine. It may be non-existing.
     * @param  string  $dirPath       Path to dir. It must exist in order for symlinks to be expanded.
     */
    public static function isPathWithinExistingDirPath($path, $dirPath)
    {
        if ($path == $dirPath) {
            return true;
        }
        // See if $filePath begins with $dirPath + '/'.
        if (strpos($path, $dirPath . '/') === 0) {
            return true;
        }

        // Also try with symlinks expanded (see comments in ::isFilePathWithinDirPath())
        $closestExistingDir = PathHelper::findClosestExistingFolderSymLinksExpanded($path);
        if (strpos($closestExistingDir . '/',  $dirPath . '/') === 0) {
            return true;
        }
        return false;
    }

    public static function frontslasher($str)
    {
        // TODO: replace backslash with frontslash
        return $str;
    }

    /**
     *  Replace double slash with single slash. ie '/var//www/' => '/var/www/'
     *  This allows you to lazely concatenate paths with '/' and then call this method to clean up afterwards.
     *  Also removes triple slash etc.
     */
    public static function fixDoubleSlash($str)
    {
        return preg_replace('/\/\/+/', '/', $str);
    }

    /**
     *  Remove trailing slash, if any
     */
    public static function untrailSlash($str)
    {
        return rtrim($str, '/');
        //return preg_replace('/\/$/', '', $str);
    }

    // Canonicalize a path by resolving '../' and './'
    // Got it from a comment here: http://php.net/manual/en/function.realpath.php
    // But fixed it (it could not handle './../')
    public static function canonicalize($path) {
      $parts = explode('/', $path);

      // Remove parts containing just '.' (and the empty holes afterwards)
      $parts = array_values(array_filter($parts, function($var) {
        return ($var != '.');
      }));

      // Remove parts containing '..' and the preceding
      $keys = array_keys($parts, '..');
      foreach($keys as $keypos => $key) {
        array_splice($parts, $key - ($keypos * 2 + 1), 2);
      }
      return implode('/', $parts);
    }

    public static function dirname($path) {
        return self::canonicalize($path . '/..');
    }

    /**
     * Get base name of a path (the last component of a path - ie the filename).
     *
     * This function operates natively on the string and is not locale aware.
     * It only works with "/" path separators.
     *
     * @return  string  the last component of a path
     */
    public static function basename($path) {
        $parts = explode('/', $path);
        return array_pop($parts);
    }

    /**
     *  Returns absolute path from a relative path and root
     *  The result is canonicalized (dots and double-dots are resolved)
     *
     *  @param $path       Absolute path or relative path
     *  @param $root       What the path is relative to, if its relative
     */
    public static function relPathToAbsPath($path, $root)
    {
        return self::canonicalize(self::fixDoubleSlash($root . '/' . $path));
    }

    /**
     *  isAbsPath
     *  If path starts with '/', it is considered an absolute path (no Windows support)
     *
     *  @param $path       Path to inspect
     */
    public static function isAbsPath($path)
    {
        return (substr($path, 0, 1) == '/');
    }

    /**
     *  Returns absolute path from a path which can either be absolute or relative to second argument.
     *  If path starts with '/', it is considered an absolute path.
     *  The result is canonicalized (dots and double-dots are resolved)
     *
     *  @param $path       Absolute path or relative path
     *  @param $root       What the path is relative to, if its relative
     */
    public static function pathToAbsPath($path, $root)
    {
        if (self::isAbsPath($path)) {
            // path is already absolute
            return $path;
        } else {
            return self::relPathToAbsPath($path, $root);
        }
    }

    /**
     *  Get relative path between two absolute paths
     *  Examples:
     *      from '/var/www' to 'var/ddd'. Result: '../ddd'
     *      from '/var/www' to 'var/www/images'. Result: 'images'
     *      from '/var/www' to 'var/www'. Result: '.'
     */
    public static function getRelDir($fromPath, $toPath)
    {
        $fromDirParts = explode('/', str_replace('\\', '/', self::canonicalize(self::untrailSlash($fromPath))));
        $toDirParts = explode('/', str_replace('\\', '/', self::canonicalize(self::untrailSlash($toPath))));
        $i = 0;
        while (($i < count($fromDirParts)) && ($i < count($toDirParts)) && ($fromDirParts[$i] == $toDirParts[$i])) {
            $i++;
        }
        $rel = "";
        for ($j = $i; $j < count($fromDirParts); $j++) {
            $rel .= "../";
        }

        for ($j = $i; $j < count($toDirParts); $j++) {
            $rel .= $toDirParts[$j];
            if ($j < count($toDirParts)-1) {
                $rel .= '/';
            }
        }
        if ($rel == '') {
            $rel = '.';
        }
        return $rel;
    }

}
