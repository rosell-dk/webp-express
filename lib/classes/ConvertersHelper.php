<?php

namespace WebPExpress;

class ConvertersHelper
{
    public static $defaultConverters = [
        ['converter' => 'gd', 'options' => ['skip-pngs' => true]],
        ['converter' => 'cwebp', 'options' => [
            'use-nice' => true,
            'try-common-system-paths' => true,
            'try-supplied-binary-for-os' => true,
            'method' => 6,
            'size-in-percentage' => 45,
            'low-memory' => false,
            'command-line-options' => '-low_memory',
        ]],
        ['converter' => 'imagick'],
        ['converter' => 'gmagick'],
        ['converter' => 'wpc'],     // we should not set api-version default - it is handled in the javascript
        ['converter' => 'ewww'],
        ['converter' => 'imagickbinary', 'options' => [
            'use-nice' => true,
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
