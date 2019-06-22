<?php

namespace WebPExpress;

use \WebPExpress\Sanitize;
use \WebPExpress\ValidateException;

class Validate
{

    public static function postHasKey($key)
    {
        if (!isset($_POST[$key])) {
            throw new ValidateException('Expected parameter in POST missing: ' . $key);
        }
    }

    /**
     *  The NUL character is a demon, because it can be used to bypass other tests
     *  See https://st-g.de/2011/04/doing-filename-checks-securely-in-PHP.
     *
     *  @param  string  $string  string to test for NUL char
     */
    public static function noNUL($string)
    {
        if (strpos($string, chr(0)) !== false) {
            throw new ValidateException('NUL character is not allowed');
        }
    }

    /**
     *  Non printable characters are seldom needed... This also prevents NUL
     *
     *  @param  string  $string  string to test for non-printable chars in
     */
    public static function noNonPrintable($string)
    {
        if (!ctype_print($string)) {
            throw new ValidateException('Non-printable characters are not allowed');
        }
    }

    /**
     *
     *  @param  mixed  $mixed  something that may not be empty
     */
    public static function notEmpty($mixed)
    {
        if (empty($mixed)) {
            throw new ValidateException('May not be empty');
        }
    }

    public static function noDirectoryTraversal($path)
    {
        if (preg_match('#\.\.\/#', Sanitize::removeNUL($path))) {
            throw new ValidateException('Directory traversal is not allowed');
        }
    }

    public static function noStreamWrappers($path)
    {
        // Prevent stream wrappers ("phar://", "php://" and the like)
        // https://www.php.net/manual/en/wrappers.phar.php
        if (preg_match('#^\\w+://#', Sanitize::removeNUL($path))) {
            throw new ValidateException('Stream wrappers are not allowed');
        }
    }

    public static function pathLooksSane($path)
    {
        self::notEmpty($path);
        self::noNonPrintable($path);
        self::noDirectoryTraversal($path);
        self::noStreamWrappers($path);
    }

    public static function absPathLooksSane($path)
    {
        self::pathLooksSane($path);        
    }


    public static function absPathLooksSaneAndExists($path, $errorMsg = 'Path does not exist')
    {
        self::absPathLooksSane($path);
        if (@!file_exists($path)) {
            throw new ValidateException($errorMsg);
        }
    }

    public static function absPathLooksSaneExistsAndIsDir($path, $errorMsg = 'Path points to a file (it should point to a directory)')
    {
        self::absPathLooksSaneAndExists($path);
        if (!is_dir($path)) {
            throw new ValidateException($errorMsg);
        }
    }

    public static function absPathLooksSaneExistsAndIsFile($path, $errorMsg = 'Path points to a directory (it should not do that)')
    {
        self::absPathLooksSaneAndExists($path, 'File does not exist');
        if (@is_dir($path)) {
            throw new ValidateException($errorMsg);
        }
    }

    public static function absPathLooksSaneExistsAndIsNotDir($path, $errorMsg = 'Path points to a directory (it should point to a file)')
    {
        self::absPathLooksSaneExistsAndIsFile($path, $errorMsg);
    }


    public static function isString($string, $errorMsg = 'Not a string')
    {
        if (!is_string($string)) {
            throw new ValidateException($errorMsg);
        }
    }

    public static function pregMatch($pattern, $subject, $errorMsg = 'Does not match expected pattern')
    {
        self::noNUL($subject);
        self::isString($subject);
        if (!preg_match($pattern, $subject)) {
            throw new ValidateException($errorMsg);
        }
    }

    public static function isJSONArray($json, $errorMsg = 'Not a JSON array')
    {
        self::noNUL($json);
        self::isString($json);
        self::notEmpty($json);
        if ((strpos($json, '[') !== 0) || (!is_array(json_decode($json)))) {
            throw new ValidateException($errorMsg);
        }
    }

    public static function isJSONObject($json, $errorMsg = 'Not a JSON object')
    {
        self::noNUL($json);
        self::isString($json);
        self::notEmpty($json);
        if ((strpos($json, '{') !== 0) || (!is_object(json_decode($json)))) {
            throw new ValidateException($errorMsg);
        }
    }

    public static function isConverterId($converterId, $errorMsg = 'Not a valid converter id')
    {
        self::pregMatch('#^[a-z]+$#', $converterId, $errorMsg);
        if (!in_array($converterId, \WebPExpress\ConvertersHelper::getDefaultConverterNames())) {
            throw new ValidateException($errorMsg);
        }
    }

}
