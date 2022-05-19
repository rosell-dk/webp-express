<?php

namespace FileUtil;

use ExecWithFallback\ExecWithFallback;

/**
 * A fileExist implementation using exec()
 *
 * @package    FileUtil
 * @author     Bjørn Rosell <it@rosell.dk>
 */
class FileExistsUsingExec
{

    /**
     * A fileExist based on an exec call.
     *
     * @throws \Exception  If exec cannot be called
     * @return boolean|null  True if file exists. False if it doesn't.
     */
    public static function fileExists($path)
    {
        if (!ExecWithFallback::anyAvailable()) {
            throw new \Exception(
                'cannot determine if file exists using exec() or similar - the function is unavailable'
            );
        }

        // Lets try to find out by executing "ls path/to/cwebp"
        ExecWithFallback::exec('ls ' . $path, $output, $returnCode);
        if (($returnCode == 0) && (isset($output[0]))) {
            return true;
        }

        // We assume that "ls" command is general available!
        // As that failed, we can conclude the file does not exist.
        return false;
    }
}
