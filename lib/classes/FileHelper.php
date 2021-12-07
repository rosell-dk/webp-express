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
        $perm = self::filePerm($filename);
        if ($perm === false) {
            return $fallback;
        }
        return $perm;
    }

    public static function humanReadableFilePerm($mode) {
        return substr(decoct($mode), -4);
    }

    public static function humanReadableFilePermOfFile($filename) {
        return self::humanReadableFilePerm(self::filePerm($filename));
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

    public static function chmod_r($dir, $dirPerm = null, $filePerm = null, $uid = null, $gid = null, $regexFileMatchPattern = null, $regexDirMatchPattern = null) {
        if (!@file_exists($dir) || (!@is_dir($dir))) {
            return;
        }
        $fileIterator = new \FilesystemIterator($dir);

        while ($fileIterator->valid()) {
            $filename = $fileIterator->getFilename();
            $filepath = $dir . "/" . $filename;

//            echo $filepath . "\n";

            $isDir = @is_dir($filepath);

            if ((!$isDir && (is_null($regexFileMatchPattern) || preg_match($regexFileMatchPattern, $filename))) ||
                    ($isDir && (is_null($regexDirMatchPattern) || preg_match($regexDirMatchPattern, $filename)))) {
                // chmod
                if ($isDir) {
                    if (!is_null($dirPerm)) {
                        self::chmod($filepath, $dirPerm);
                        //echo '. chmod dir to:' . self::humanReadableFilePerm($dirPerm) . '. result:' . self::humanReadableFilePermOfFile($filepath) . "\n";
                    }
                } else {
                    if (!is_null($filePerm)) {
                        self::chmod($filepath, $filePerm);
                        //echo '. chmod file to:' . self::humanReadableFilePerm($filePerm) . '. result:' . self::humanReadableFilePermOfFile($filepath) . "\n";
                    }

                }

                // chown
                if (!is_null($uid)) {
                    @chown($filepath, $uid);
                }

                // chgrp
                if (!is_null($gid)) {
                    @chgrp($filepath, $gid);

                }
            }

            // recurse
            if ($isDir) {
                self::chmod_r($filepath, $dirPerm, $filePerm, $uid, $gid, $regexFileMatchPattern, $regexDirMatchPattern);
            }

            // next!
            $fileIterator->next();
        }
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
        if (@is_writable($dirName) && @is_executable($dirName) || self::isWindows() ) {
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
        try {
            $handle = @fopen($filename, "r");
        } catch (\ErrorException $exception) {
            $handle = false;
            error_log($exception->getMessage());
        }
        if ($handle !== false) {
            // Return value is either file content or false
            if (filesize($filename) == 0) {
              $return = '';
            } else {
              $return = @fread($handle, filesize($filename));
            }
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


    /**
     *  Copy dir and all its files.
     *  Existing files are overwritten.
     *
     *  @return $success
     */
    public static function cpdir($sourceDir, $destinationDir)
    {
        if (!@is_dir($sourceDir)) {
            return false;
        }
        if (!@file_exists($destinationDir)) {
            if (!@mkdir($destinationDir)) {
                return false;
            }
        }

        $fileIterator = new \FilesystemIterator($sourceDir);
        $success = true;

        while ($fileIterator->valid()) {
            $filename = $fileIterator->getFilename();

            if (($filename != ".") && ($filename != "..")) {
                //$filePerm = FileHelper::filePermWithFallback($filename, 0777);

                if (@is_dir($sourceDir . "/" . $filename)) {
                    if (!self::cpdir($sourceDir . "/" . $filename, $destinationDir . "/" . $filename)) {
                        $success = false;
                    }
                } else {
                    // its a file.
                    if (!copy($sourceDir . "/" . $filename, $destinationDir . "/" . $filename)) {
                        $success = false;
                    }
                }
            }
            $fileIterator->next();
        }
        return $success;
    }

    /**
     *  Remove empty subfolders.
     *
     *  Got it here: https://stackoverflow.com/a/1833681/842756
     *
     *  @return  boolean  If folder is (was) empty
     */
    public static function removeEmptySubFolders($path, $removeEmptySelfToo = false)
    {
        if (!file_exists($path)) {
            return;
        }
        $empty = true;
        foreach (scandir($path) as $file) {
            if (($file == '.') || ($file == '..')) {
                continue;
            }
            $file = $path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($file)) {
                if (!self::removeEmptySubFolders($file, true)) {
                    $empty=false;
                }
            } else {
                $empty=false;
            }
        }
        if ($empty && $removeEmptySelfToo) {
            rmdir($path);
        }
        return $empty;
    }

    /**
     *  Verify if OS is Windows
     *
     *
     *  @return true if windows; false if not.
     */
    public static function isWindows(){
        return preg_match('/^win/i', PHP_OS);
    }


     /**
     *  Normalize separators of directory paths
     *
     *
     *  @return $normalized_path
     */
    public static function normalizeSeparator($path, $newSeparator = DIRECTORY_SEPARATOR){
        return preg_replace("#[\\\/]+#", $newSeparator, $path);
    }

    /**
     *  @return  object|false   Returns parsed file the file exists and can be read. Otherwise it returns false
     */
    public static function loadJSONOptions($filename)
    {
        $json = self::loadFile($filename);
        if ($json === false) {
            return false;
        }

        $options = json_decode($json, true);
        if ($options === null) {
            return false;
        }
        return $options;
    }

    public static function saveJSONOptions($filename, $obj)
    {
        $result = @file_put_contents(
            $filename,
            json_encode($obj, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT)
        );
        /*if ($result === false) {
            echo 'COULD NOT' . $filename;
        }*/
        return ($result !== false);
    }

}
