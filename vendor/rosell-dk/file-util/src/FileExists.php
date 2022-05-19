<?php

namespace FileUtil;

use FileUtil\FileExistsUsingExec;

/**
 * A fileExist function free of deception
 *
 * @package    FileUtil
 * @author     Bjørn Rosell <it@rosell.dk>
 */
class FileExists
{

    private static $lastWarning;

    /**
     * A warning handler that registers that a warning has occured and suppresses it.
     *
     * The function is a callback used with "set_error_handler".
     * It is declared public because it needs to be accessible from the point where the warning is triggered.
     *
     * @param  integer  $errno
     * @param  string   $errstr
     * @param  string   $errfile
     * @param  integer  $errline
     *
     * @return void
     */
    public static function warningHandler($errno, $errstr, $errfile, $errline)
    {
        self::$lastWarning = [$errstr, $errno];

        // Suppress the warning by returning void
        return;
    }

    /**
     * A well behaved replacement for file_exist that throws upon failure rather than emitting a warning.
     *
     * @throws \Exception Throws an exception in case file_exists emits a warning
     * @return boolean  True if file exists. False if it doesn't.
     */
    public static function fileExists($path)
    {
        // There is a challenges here:
        // We want to suppress warnings, but at the same time we want to know that it happened.
        // We achieve this by registering an error handler
        set_error_handler(
            array('FileUtil\FileExists', "warningHandler"),
            E_WARNING | E_USER_WARNING | E_NOTICE | E_USER_NOTICE
        );
        self::$lastWarning = null;
        $found = @file_exists($path);

        // restore previous error handler immediately
        restore_error_handler();

        // If file_exists returns true, we can rely on there being a file there
        if ($found) {
            return true;
        }

        // file_exists returned false.
        // this result is only trustworthy if no warning was emitted.
        if (is_null(self::$lastWarning)) {
            return false;
        }

        list($errstr, $errno) = self::$lastWarning;
        throw new \Exception($errstr, $errno);
    }

    /**
     * A fileExist doing the best it can.
     *
     * @throws \Exception  If it cannot be determined if the file exists
     * @return boolean|null  True if file exists. False if it doesn't.
     */
    public static function fileExistsTryHarder($path)
    {
        try {
            $result = self::fileExists($path);
        } catch (\Exception $e) {
            try {
                $result = FileExistsUsingExec::fileExists($path);
            } catch (\Exception $e) {
                throw new \Exception('Cannot determine if file exists or not');
            } catch (\Throwable $e) {
                throw new \Exception('Cannot determine if file exists or not');
            }
        }
        return $result;
    }
}
