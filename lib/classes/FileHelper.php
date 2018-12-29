<?php

namespace WebPExpress;

class FileHelper
{

    public static function fileExists($filename) {
        return @file_exists($filename);
    }

    /**
     *  Get file permission of a file (integer). Only get the last part, ie 0644
     *  If failure, it returns false
     */
    public static function filePerm($filename) {
        if (!self::fileExists($filename)) {
            return false;
        }

        // fileperms can still fail. In that case, it returns false
        $perm = @fileperms($filename);
        if ($perm === false) {
            return false;
        }

        return octdec(substr(decoct($perm), -4));
    }


    /**
     *  Get file permission of a file (integer). Only get the last part, ie 0644
     *  If failure, it returns $fallback
     */
    public static function filePermWithFallback($filename, $fallback) {
        $perm = self::filePerm();
        if ($perm === false) {
            return $fallback;
        }
        return $perm;
    }

    public static function humanReadableFilePerm($mode) {
        return substr(decoct($mode), -4);
    }

    public static function humanReadableFilePermOfFile($filename) {
        return self::readableFilePerm(self::filePerm($filename));
    }

    /**
     *  As the return value of the PHP function isn't reliable,
     *  we have our own chmod.
     */
    public static function chmod($filename, $mode) {
        // In case someone carelessly passed the result of a filePerm call, which was false:
        if ($mode === false) {
            return false;
        }
        $existingPermission = self::filePerm($filename);
        if ($mode === $existingPermission) {
            return true;
        }
        if (@chmod($filename, $mode)) {
            // in some cases chmod returns true, even though it did not succeed!
            // - so we test if our operation had the desired effect.
            if (self::filePerm($filename) !== $mode) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     *  Create a dir using same permissions as parent.
     *  If
     */
     /*
    public static function mkdirSamePermissionsAsParent($pathname) {

    }*/

    /**
     *  Get directory part of filename.
     *  Ie '/var/www/.htaccess' => '/var/www'
     *  Also works with backslashes
     */
    public static function dirName($filename) {
        return preg_replace('/[\/\\\\][^\/\\\\]*$/', '', $filename);
    }

    /**
     *  Determines if a file can be created.
     *  BEWARE: It requires that the containing folder already exists
     */
    public static function canCreateFile($filename) {
        $dirName = self::dirName($filename);
        if (!@file_exists($dirName)) {
            return false;
        }
        if (@is_writable($dirName) && @is_executable($dirName)) {
            return true;
        }

        $existingPermission = self::filePerm($dirName);

        // we need to make sure we got the existing permission, so we can revert correctly later
        if ($existingPermission !== false) {
            if (self::chmod($dirName, 0775)) {
                // change back
                self::chmod($filename, $existingPermission);
                return true;
            }
        }
        return false;
    }

    /**
     *  Note: Do not use for directories
     */
    public static function canEditFile($filename) {
        if (!@file_exists($filename)) {
            return false;
        }
        if (@is_writable($filename) && @is_readable($filename)) {
            return true;
        }

        // As a last desperate try, lets see if we can give ourself write permissions.
        // If possible, then it will also be possible when actually writing
        $existingPermission = self::filePerm($filename);

        // we need to make sure we got the existing permission, so we can revert correctly later
        if ($existingPermission !== false) {
            if (self::chmod($filename, 0664)) {
                // change back
                self::chmod($filename, $existingPermission);
                return true;
            }
        }
        return false;

        // Idea: Perhaps we should also try to actually open the file for writing?

    }

    public static function canEditOrCreateFileHere($filename) {
        if (@file_exists($filename)) {
            return self::canEditFile($filename);
        } else {
            return self::canCreateFile($filename);
        }
    }

    /**
     *  Try to read from a file. Tries hard.
     *  Returns content, or false if read error.
     */
    public static function loadFile($filename) {
        $changedPermission = false;
        if (!@is_readable($filename)) {
            $existingPermission = self::filePerm($filename);

            // we need to make sure we got the existing permission, so we can revert correctly later
            if ($existingPermission !== false) {
                $changedPermission = self::chmod($filename, 0664);
            }
        }

        $return = false;
        $handle = @fopen($filename, "r");
        if ($handle !== false) {
            // Return value is either file content or false
            $return = @fread($handle, filesize($filename));
            fclose($handle);
        }

        if ($changedPermission) {
            // change back
            self::chmod($filename, $existingPermission);
        }
        return $return;
    }


    /* Remove dir and files in it recursively.
       No warnings
       returns $success
    */
    public static function rrmdir($dir) {
        if (@is_dir($dir)) {
            $objects = @scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (@is_dir($dir . "/" . $object))
                        self::rrmdir($dir . "/" . $object);
                    else
                        @unlink($dir . "/" . $object);
                }
            }
            return @rmdir($dir);
        } else {
            return false;
        }
    }

}
