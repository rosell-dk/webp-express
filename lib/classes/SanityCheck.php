<?php

namespace WebPExpress;

use \WebPExpress\Sanitize;
use \WebPExpress\SanityException;

class SanityCheck
{

    /**
     *
     *  @param  string  $input  string to test for NUL char
     */
    public static function mustBeString($input, $errorMsg = 'String expected')
    {
        if (gettype($input) !== 'string') {
            throw new SanityException($errorMsg);
        }
        return $input;
    }

    /**
     *  The NUL character is a demon, because it can be used to bypass other tests
     *  See https://st-g.de/2011/04/doing-filename-checks-securely-in-PHP.
     *
     *  @param  string  $input  string to test for NUL char
     */
    public static function noNUL($input, $errorMsg = 'NUL character is not allowed')
    {
        self::mustBeString($input);
        if (strpos($input, chr(0)) !== false) {
            throw new SanityException($errorMsg);
        }
        return $input;
    }

    /**
     *  Prevent control chararters (#00 - #20).
     *
     *  This prevents line feed, new line, tab, charater return, tab, ets.
     *  https://www.rapidtables.com/code/text/ascii-table.html
     *
     *  @param  string  $input  string to test for control characters
     */
    public static function noControlChars($input)
    {
        self::mustBeString($input);
        self::noNUL($input);
        if (preg_match('#[\x{0}-\x{1f}]#', $input)) {
            throw new SanityException('Control characters are not allowed');
        }
        return $input;
    }


    /**
     *
     *  @param  mixed  $input  something that may not be empty
     */
    public static function notEmpty($input, $errorMsg = 'Must be non-empty')
    {
        if (empty($input)) {
            throw new SanityException($input);
        }
        return $input;
    }



    public static function noDirectoryTraversal($input, $errorMsg = 'Directory traversal is not allowed')
    {
        self::mustBeString($input);
        self::noControlChars($input);
        if (preg_match('#\.\.\/#', $input)) {
            throw new SanityException($errorMsg);
        }
        return $input;
    }

    public static function noStreamWrappers($input, $errorMsg = 'Stream wrappers are not allowed')
    {
        self::mustBeString($input);
        self::noControlChars($input);

        // Prevent stream wrappers ("phar://", "php://" and the like)
        // https://www.php.net/manual/en/wrappers.phar.php
        if (preg_match('#^\\w+://#', Sanitize::removeNUL($input))) {
            throw new SanityException($errorMsg);
        }
        return $input;
    }

    public static function path($input)
    {
        self::notEmpty($input);
        self::mustBeString($input);
        self::noControlChars($input);
        self::noDirectoryTraversal($input);
        self::noStreamWrappers($input);

        // PS: The following sanitize has no effect, as we have just tested that there are no NUL and
        // no stream wrappers. It is here to avoid false positives on coderisk.com
        $input = Sanitize::path($input);

        return $input;
    }

    public static function pathWithoutDirectoryTraversal($input)
    {
        return self::path($input);
    }

    /**
     *  Beware: This does not take symlinks into account.
     *  I should make one that does. Until then, you should probably not call this method from outside this class
     */
    public static function pathBeginsWith($input, $beginsWith, $errorMsg = 'Path is outside allowed path')
    {
        self::path($input);
        if (!(strpos($input, $beginsWith) === 0)) {
            throw new SanityException($errorMsg);
        }
        return $input;
    }

    public static function pathBeginsWithSymLinksExpanded($input, $beginsWith, $errorMsg = 'Path is outside allowed path') {
        $closestExistingFolder = self::findClosestExistingFolderSymLinksExpanded($input);
        self::pathBeginsWith($closestExistingFolder, $beginsWith, $errorMsg);
    }

    public static function absPathMicrosoftStyle($input, $errorMsg = 'Not an fully qualified Windows path')
    {
        // On microsoft we allow [drive letter]:\
        if (!preg_match("#^[A-Z]:\\\\|/#", $input)) {
            throw new SanityException($errorMsg);
        }
        return $input;
    }

    private static function isOnMicrosoft()
    {
        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            if (strpos(strtolower($_SERVER['SERVER_SOFTWARE']), 'microsoft') !== false) {
                return true;
            }
        }
        switch (PHP_OS) {
            case "WINNT":
            case "WIN32":
            case "INTERIX":
            case "UWIN":
            case "UWIN-W7":
                return true;
                break;
        }
        return false;
    }

    public static function absPath($input, $errorMsg = 'Not an absolute path')
    {
        // first make sure there are no nasty things like control chars, phar wrappers, etc.
        // - and no directory traversal either.
        self::path($input);

        // For non-windows, we require that an absolute path begins with "/"
        // On windows, we also accept that a path starts with a drive letter, ie "C:\"
        if ((strpos($input, '/') !== 0)) {
            if (self::isOnMicrosoft()) {
                self::absPathMicrosoftStyle($input);
            } else {
                throw new SanityException($errorMsg);
            }
        }
        return $input;
    }

    private static function findClosestExistingFolderSymLinksExpanded($input) {
        // Get closest existing folder with symlinks expanded.
        // this is a bit complicated, as the input path may not yet exist.
        // in case of realpath failure, we must try with one folder pealed off at the time

        $levelsUp = 1;
        while (true) {
            $dir = dirname($input, $levelsUp);
            $realPathResult = realpath($dir);
            if ($realPathResult !== false) {
                return $realPathResult;
            }
            if (($dir == '/') || (strlen($dir) < 4)) {
                return $dir;
            }
            $levelsUp++;
        }
    }

    /**
     * Test that absolute path is in document root.
     *
     * It is acceptable if the absolute path does not exist
     */
    public static function absPathIsInDocRoot($input, $errorMsg = 'Path is outside document root')
    {
        self::absPath($input);

        $docRoot = self::absPath($_SERVER["DOCUMENT_ROOT"]);
        $docRoot = rtrim($docRoot, '/');
        $docRoot = self::absPathExistsAndIsDir($docRoot);

        // Use realpath to expand symbolic links and check if it exists
        $docRootSymLinksExpanded = realpath($docRoot);
        if ($docRootSymLinksExpanded === false) {
            throw new SanityException('Cannot find document root');
        }
        $docRootSymLinksExpanded = rtrim($docRootSymLinksExpanded, '/');
        $docRootSymLinksExpanded = self::absPathExists($docRootSymLinksExpanded, 'Document root does not exist!');
        $docRootSymLinksExpanded = self::absPathExistsAndIsDir($docRootSymLinksExpanded, 'Document root is not a directory!');

        try {
            // try without symlinks expanded
            self::pathBeginsWith($input, $docRoot . '/', $errorMsg);
        } catch (SanityException $e) {
            self::pathBeginsWithSymLinksExpanded($input, $docRootSymLinksExpanded . '/', $errorMsg);
        }

        return $input;
    }

    public static function absPathExists($input, $errorMsg = 'Path does not exist')
    {
        self::absPath($input);
        if (@!file_exists($input)) {
            throw new SanityException($errorMsg);
        }
        return $input;
    }

    public static function absPathExistsAndIsDir(
        $input,
        $errorMsg = 'Path points to a file (it should point to a directory)'
    ) {
        self::absPathExists($input);
        if (!is_dir($input)) {
            throw new SanityException($errorMsg);
        }
        return $input;
    }

    public static function absPathExistsAndIsFile(
        $input,
        $errorMsg = 'Path points to a directory (it should not do that)'
    ) {
        self::absPathExists($input, 'File does not exist');
        if (@is_dir($input)) {
            throw new SanityException($errorMsg);
        }
        return $input;
    }

    public static function absPathExistsAndIsFileInDocRoot($input)
    {
        self::absPathExistsAndIsFile($input);
        self::absPathIsInDocRoot($input);
        return $input;
    }

    public static function absPathExistsAndIsNotDir(
        $input,
        $errorMsg = 'Path points to a directory (it should point to a file)'
    ) {
        self::absPathExistsAndIsFile($input, $errorMsg);
        return $input;
    }


    public static function pregMatch($pattern, $input, $errorMsg = 'Does not match expected pattern')
    {
        self::noNUL($input);
        self::mustBeString($input);
        if (!preg_match($pattern, $input)) {
            throw new SanityException($errorMsg);
        }
        return $input;
    }

    public static function isJSONArray($input, $errorMsg = 'Not a JSON array')
    {
        self::noNUL($input);
        self::mustBeString($input);
        self::notEmpty($input);
        if ((strpos($input, '[') !== 0) || (!is_array(json_decode($input)))) {
            throw new SanityException($errorMsg);
        }
        return $input;
    }

    public static function isJSONObject($input, $errorMsg = 'Not a JSON object')
    {
        self::noNUL($input);
        self::mustBeString($input);
        self::notEmpty($input);
        if ((strpos($input, '{') !== 0) || (!is_object(json_decode($input)))) {
            throw new SanityException($errorMsg);
        }
        return $input;
    }

}
