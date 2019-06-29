<?php

namespace WebPExpress;

use \WebPExpress\ConvertersHelper;
use \WebPExpress\ValidateException;
use \WebPExpress\SanityCheck;

class Validate
{

    public static function postHasKey($key)
    {
        if (!isset($_POST[$key])) {
            throw new ValidateException('Expected parameter in POST missing: ' . $key);
        }
    }

    public static function isConverterId($converterId, $errorMsg = 'Not a valid converter id')
    {
        SanityCheck::pregMatch('#^[a-z]+$#', $converterId, $errorMsg);
        if (!in_array($converterId, ConvertersHelper::getDefaultConverterNames())) {
            throw new ValidateException($errorMsg);
        }
    }

}
