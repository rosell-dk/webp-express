<?php

namespace WebPExpress;

use \WebPExpress\PathHelper;
use \WebPExpress\Sanitize;
use \WebPExpress\SanityException;

class SanityCheck
{

    private static function fail($errorMsg, $input)
    {
        // sanitize input before calling error_log(), it might be sent to file, mail, syslog etc.
        //error_log($errorMsg . '. input:' . Sanitize::removeNUL($input) . 'backtrace: ' . print_r(debug_backtrace(), true));
        error_log($errorMsg . '. input:' . Sanitize::removeNUL($input));

        //error_log(get_magic_quotes_gpc() ? 'on' :'off');
        throw new SanityException($errorMsg);   //  . '. Check debug.log for details (and make sure debugging is enabled)'
    }


    /**
     *
     *  @param  string  $input  string to test for NUL char
     */
    public static function mustBeString($input, $errorMsg = 'String expected')
    {
        if (gettype($input) !== 'string') {
            self::fail($errorMsg, $input);
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
            self::fail($errorMsg, $input);
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
    public static function noControlChars($input, $errorMsg = 'Control characters are not allowed')
    {
        self::mustBeString($input);
        self::noNUL($input);
        if (preg_match('#[\x{0}-\x{1f}]#', $input)) {
            self::fail($errorMsg, $input);
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
            self::fail($errorMsg, '');
        }
        return $input;
    }



    public static function noDirectoryTraversal($input, $errorMsg = 'Directory traversal is not allowed')
    {
        self::mustBeString($input);
        self::noControlChars($input);
        if (preg_match('#\.\.\/#', $input)) {
            self::fail($errorMsg, $input);
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
            self::fail($errorMsg, $input);
        }
        return $input;
    }

    public static function pathDirectoryTraversalAllowed($input)
    {
        self::notEmpty($input);
        self::mustBeString($input);
        self::noControlChars($input);
        self::noStreamWrappers($input);

        // PS: The following sanitize has no effect, as we have just tested that there are no NUL and
        // no stream wrappers. It is here to avoid false positives on coderisk.com
        $input = Sanitize::path($input);

        return $input;
    }

    public static function pathWithoutDirectoryTraversal($input)
    {
        self::pathDirectoryTraversalAllowed($input);
        self::noDirectoryTraversal($input);
        $input = Sanitize::path($input);

        return $input;
    }

    public static function path($input)
    {
        return self::pathWithoutDirectoryTraversal($input);
    }


    /**
     *  Beware: This does not take symlinks into account.
     *  I should make one that does. Until then, you should probably not call this method from outside this class
     */
    private static function pathBeginsWith($input, $beginsWith, $errorMsg = 'Path is outside allowed path')
    {
        self::path($input);
        if (!(strpos($input, $beginsWith) === 0)) {
            self::fail($errorMsg, $input);
        }
        return $input;
    }

    private static function pathBeginsWithSymLinksExpanded($input, $beginsWith, $errorMsg = 'Path is outside allowed path') {
        $closestExistingFolder = PathHelper::findClosestExistingFolderSymLinksExpanded($input);
        self::pathBeginsWith($closestExistingFolder, $beginsWith, $errorMsg);
    }

    private static function absPathMicrosoftStyle($input, $errorMsg = 'Not an fully qualified Windows path')
    {
        // On microsoft we allow [drive letter]:\
        if (!preg_match("#^[A-Z]:\\\\|/#", $input)) {
            self::fail($errorMsg, $input);
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
                self::fail($errorMsg, $input);
            }
        }
        return $input;
    }



    public static function absPathInOneOfTheseRoots()
    {

    }


    /**
     * Look if filepath is within a dir path.
     * Also tries expanding symlinks
     *
     * @param  string  $filePath   Path to file. It may be non-existing.
     * @param  string  $dirPath    Path to dir. It must exist in order for symlinks to be expanded.
     */
    private static function isFilePathWithinExistingDirPath($filePath, $dirPath)
    {
        // sanity-check input. It must be a valid absolute filepath. It is allowed to be non-existing
        self::absPath($filePath);

        // sanity-check dir and that it exists.
        self::absPathExistsAndIsDir($dirPath);

        return PathHelper::isFilePathWithinDirPath($filePath, $dirPath);
    }

    /**
     * Look if filepath is within multiple dir paths.
     * Also tries expanding symlinks
     *
     * @param  string  $input    Path to file. It may be non-existing.
     * @param  array   $roots    Allowed root dirs. Note that they must exist in order for symlinks to be expanded.
     */
    public static function filePathWithinOneOfTheseRoots($input, $roots, $errorMsg = 'The path is outside allowed roots.')
    {
        self::absPath($input);

        foreach ($roots as $root) {
            if (self::isFilePathWithinExistingDirPath($input, $root)) {
                return $input;
            }
        }
        self::fail($errorMsg, $input);
    }

    /*
    public static function sourcePath($input, $errorMsg = 'The source path is outside allowed roots. It is only allowed to convert images that resides in: home dir, content path, upload dir and plugin dir.')
    {
        $validPaths = [
            Paths::getHomeDirAbs(),
            Paths::getIndexDirAbs(),
            Paths::getContentDirAbs(),
            Paths::getUploadDirAbs(),
            Paths::getPluginDirAbs()
        ];
        return self::filePathWithinOneOfTheseRoots($input, $validPaths, $errorMsg);
    }

    public static function destinationPath($input, $errorMsg = 'The destination path is outside allowed roots. The webps may only be stored in the upload folder and in the folder that WebP Express stores converted images in')
    {
        self::absPath($input);

        // Webp Express only store converted images in upload folder and in its "webp-images" folder
        // Check that destination path is within one of these.
        $validPaths = [
            '/var/www/webp-express-tests/we1'
            //Paths::getUploadDirAbs(),
            //Paths::getWebPExpressContentDirRel() . '/webp-images'
        ];
        return self::filePathWithinOneOfTheseRoots($input, $validPaths, $errorMsg);
    }*/


    /**
     * Test that path is an absolute path and it is in document root.
     *
     * If DOCUMENT_ROOT is not available, then only the absPath check will be done.
     *
     * TODO: Instead of this method, we shoud check
     *
     *
     * It is acceptable if the absolute path does not exist
     */
    public static function absPathIsInDocRoot($input, $errorMsg = 'Path is outside document root')
    {
        self::absPath($input);

        if (!isset($_SERVER["DOCUMENT_ROOT"])) {
            return $input;
        }
        if ($_SERVER["DOCUMENT_ROOT"] == '') {
            return $input;
        }

        $docRoot = self::absPath($_SERVER["DOCUMENT_ROOT"]);
        $docRoot = rtrim($docRoot, '/');

        try {
            $docRoot = self::absPathExistsAndIsDir($docRoot);
        } catch (SanityException $e) {
            return $input;
        }

        // Use realpath to expand symbolic links and check if it exists
        $docRootSymLinksExpanded = @realpath($docRoot);
        if ($docRootSymLinksExpanded === false) {
            // probably outside open basedir restriction.
            //$errorMsg = 'Cannot resolve document root';
            //self::fail($errorMsg, $input);

            // Cannot resolve document root, so cannot test if in document root
            return $input;
        }

        // See if $filePath begins with the realpath of the $docRoot + '/'. If it does, we are done and OK!
        // (pull #429)
        if (strpos($input, $docRootSymLinksExpanded . '/') === 0) {
            return $input;
        }

        $docRootSymLinksExpanded = rtrim($docRootSymLinksExpanded, '\\/');
        $docRootSymLinksExpanded = self::absPathExists($docRootSymLinksExpanded, 'Document root does not exist!');
        $docRootSymLinksExpanded = self::absPathExistsAndIsDir($docRootSymLinksExpanded, 'Document root is not a directory!');

        $directorySeparator = self::isOnMicrosoft() ? '\\' : '/';
        $errorMsg = 'Path is outside resolved document root (' . $docRootSymLinksExpanded . ')';
        self::pathBeginsWithSymLinksExpanded($input, $docRootSymLinksExpanded . $directorySeparator, $errorMsg);

        return $input;
    }

    public static function absPathExists($input, $errorMsg = 'Path does not exist or it is outside restricted basedir')
    {
        self::absPath($input);
        if (@!file_exists($input)) {
            // TODO: We might be able to detect if the problem is that the path does not exist or if the problem
            // is that it is outside restricted basedir.
            // ie by creating an error handler or inspecting the php ini "open_basedir" setting
            self::fail($errorMsg, $input);
        }
        return $input;
    }

    public static function absPathExistsAndIsDir(
        $input,
        $errorMsg = 'Path points to a file (it should point to a directory)'
    ) {
        self::absPathExists($input, 'Directory does not exist or is outside restricted basedir');
        if (!is_dir($input)) {
            self::fail($errorMsg, $input);
        }
        return $input;
    }

    public static function absPathExistsAndIsFile(
        $input,
        $errorMsg = 'Path points to a directory (it should not do that)'
    ) {
        self::absPathExists($input, 'File does not exist or is outside restricted basedir');
        if (@is_dir($input)) {
            self::fail($errorMsg, $input);
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
            self::fail($errorMsg, $input);
        }
        return $input;
    }

    public static function isJSONArray($input, $errorMsg = 'Not a JSON array')
    {
        self::noNUL($input);
        self::mustBeString($input);
        self::notEmpty($input);
        if ((strpos($input, '[') !== 0) || (!is_array(json_decode($input)))) {
            self::fail($errorMsg, $input);
        }
        return $input;
    }

    public static function isJSONObject($input, $errorMsg = 'Not a JSON object')
    {
        self::noNUL($input);
        self::mustBeString($input);
        self::notEmpty($input);
        if ((strpos($input, '{') !== 0) || (!is_object(json_decode($input)))) {
            self::fail($errorMsg, $input);
        }
        return $input;
    }

}
