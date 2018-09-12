<?php

namespace WebPExpress;

class FileHelper
{

    public static function fileExists($filename) {
        return @file_exists($filename);
    }

    /**
     *  Get file permission of a file (integer). Only get the last part, ie 0644
     */
    public static function filePerm($filename) {
        return octdec(substr(decoct(fileperms($filename)), -4));
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
        $existingPermission = self::filePerm($filename);
        if ($mode == $existingPermission) {
            return true;
        }
        if (@chmod($filename, $mode)) {
            // in some cases chmod returns true, even though it did not succeed!
            if (self::filePerm($filename) != $mode) {
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     *  Get directory part of filename.
     *  Ie '/var/www/.htaccess' => '/var/www'
     *  Also works with backslashes
     */
    public static function dirName($filename) {
        return preg_replace('/[\/\\\\][^\/\\\\]*$/', '', $filename);
    }

    public static function canEditOrCreateFileHere($filename) {
        if (@file_exists($filename)) {

            if (@is_writable($filename) && @is_readable($filename)) {
                return true;
            }

            // As a last desperate try, lets see if we can give ourself write permissions.
            // If possible, then it will also be possible when actually writing
            $existingPermission = self::filePerm($filename);
            if (self::chmod($filename, 0660)) {
                // change back
                self::chmod($filename, $existingPermission);
                return true;
            }

            // Idea: Perhaps we should also try to actually open the file for writing?

        } else {
            $dirName = self::dirName($filename);
            if (@is_writable($dirName) && @is_executable($dirName)) {
                return true;
            }
            $existingPermission = self::filePerm($dirName);
            if (self::chmod($dirName, 0770)) {
                // change back
                self::chmod($filename, $existingPermission);
                return true;
            }
        }
        return false;

    }

    /**
     *  Try to read from a file. Tries hard.
     *  Returns content, or false if read error.
     */
    public static function loadFile($filename) {
        $changedPermission = false;
        if (!@is_readable($filename)) {
            $existingPermission = self::filePerm($filename);
            $changedPermission = self::chmod($filename, 0660);
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
}
