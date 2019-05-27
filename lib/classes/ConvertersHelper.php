<?php

namespace WebPExpress;

class ConvertersHelper
{
    public static $defaultConverters = [
        ['converter' => 'cwebp', 'options' => [
            'use-nice' => true,
            'try-common-system-paths' => true,
            'try-supplied-binary-for-os' => true,
            'method' => 6,
            'size-in-percentage' => null,
            'low-memory' => true,
            'command-line-options' => '',
        ]],
        ['converter' => 'vips', 'options' => [
            'smart-subsample' => false,
            'preset' => null
        ]],
        ['converter' => 'imagickbinary', 'options' => [
            'use-nice' => true,
        ]],
        ['converter' => 'gmagickbinary', 'options' => [
            'use-nice' => true,
        ]],
        ['converter' => 'wpc'],     // we should not set api-version default - it is handled in the javascript
        ['converter' => 'ewww'],
        ['converter' => 'imagick'],
        ['converter' => 'gmagick'],
        ['converter' => 'gd', 'options' => [
            'skip-pngs' => false,
            'preset' => 'disable'
        ]],
    ];

    public static function getDefaultConverterNames()
    {
        return array_column(self::$defaultConverters, 'converter');
    }

    public static function getConverterNames($converters)
    {
        return array_column(self::normalize($converters), 'converter');
    }

    public static function normalize($converters)
    {
        foreach ($converters as &$converter) {
            if (!isset($converter['converter'])) {
                $converter = ['converter' => $converter];
            }
            if (!isset($converter['options'])) {
                $converter['options'] = [];
            }
        }
        return $converters;
    }

    /**
     *  Those converters in second, but not in first will be appended to first
     */
    public static function mergeConverters($first, $second)
    {
        $namesInFirst = self::getConverterNames($first);
        $second = self::normalize($second);

        foreach ($second as $converter) {
            if (!in_array($converter['converter'], $namesInFirst)) {
                $first[] = $converter;
            }
        }
        return $first;
    }

}
