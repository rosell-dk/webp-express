<?php

namespace FileUtil;

use FileUtil\FileExists;

/**
 *
 *
 * @package    FileUtil
 * @author     Bjørn Rosell <it@rosell.dk>
 */
class PathValidator
{
    /**
     * Check if path looks valid and doesn't contain suspecious patterns.

     * The path must meet the following criteria:
     *
     * - It must be a string
     * - No NUL character
     * - No control characters between 0-20
     * - No phar stream wrapper
     * - No php stream wrapper
     * - No glob stream wrapper
     * - Not empty path
     *
     * @throws \Exception  In case the path doesn't meet all criteria
     */
    public static function checkPath($path)
    {
        if (gettype($path) !== 'string') {
            throw new \Exception('File path must be string');
        }
        if (strpos($path, chr(0)) !== false) {
            throw new \Exception('NUL character is not allowed in file path!');
        }
        if (preg_match('#[\x{0}-\x{1f}]#', $path)) {
            // prevents line feed, new line, tab, charater return, tab, ets.
            throw new \Exception('Control characters #0-#20 not allowed in file path!');
        }
        // Prevent phar stream wrappers (security threat)
        if (preg_match('#^phar://#', $path)) {
            throw new \Exception('phar stream wrappers are not allowed in file path');
        }
        if (preg_match('#^(php|glob)://#', $path)) {
            throw new \Exception('php and glob stream wrappers are not allowed in file path');
        }
        if (empty($path)) {
            throw new \Exception('File path is empty!');
        }
    }

    /**
     * Check if path points to a regular file (and doesnt match suspecious patterns).
     *
     * @throws \Exception  In case the path doesn't point to a regular file or matches suspecious patterns
     */
    public static function checkFilePathIsRegularFile($path)
    {
        self::checkPath($path);

        if (!FileExists::fileExists($path)) {
            throw new \Exception('File does not exist');
        }
        if (@is_dir($path)) {
            throw new \Exception('Expected a regular file, not a dir');
        }
    }
}
