<?php

namespace WebPExpress;

class PathHelper
{

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
