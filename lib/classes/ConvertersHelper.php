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
            'low-memory' => true,
            'command-line-options' => '',
        ]],
        ['converter' => 'vips', 'options' => [
            'smart-subsample' => false,
            'preset' => 'none'
        ]],
        ['converter' => 'imagemagick', 'options' => [
            'use-nice' => true,
        ]],
        ['converter' => 'graphicsmagick', 'options' => [
            'use-nice' => true,
        ]],
        ['converter' => 'ffmpeg', 'options' => [
            'use-nice' => true,
            'method' => 4,
        ]],
        ['converter' => 'wpc', 'options' => []],     // we should not set api-version default - it is handled in the javascript
        ['converter' => 'ewww', 'options' => []],
        ['converter' => 'imagick', 'options' => []],
        ['converter' => 'gmagick', 'options' => []],
        ['converter' => 'gd', 'options' => [
            'skip-pngs' => false,
        ]],
    ];

    public static function getDefaultConverterNames()
    {
        $availableConverterIDs = [];
        foreach (self::$defaultConverters as $converter) {
            $availableConverterIDs[] = $converter['converter'];
        }
        return $availableConverterIDs;

        // PS: In a couple of years:
        //return array_column(self::$defaultConverters, 'converter');
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
     *  Those converters in second array, but not in first will be appended to first
     */
    public static function mergeConverters($first, $second)
    {
        $namesInFirst = self::getConverterNames($first);
        $second = self::normalize($second);

        foreach ($second as $converter) {
            // migrate9 and this functionality could create two converters.
            // so, for a while, skip graphicsmagick and imagemagick

            if ($converter['converter'] == 'graphicsmagick') {
                if (in_array('gmagickbinary', $namesInFirst)) {
                    continue;
                }
            }
            if ($converter['converter'] == 'imagemagick') {
                if (in_array('imagickbinary', $namesInFirst)) {
                    continue;
                }
            }
            if (!in_array($converter['converter'], $namesInFirst)) {
                $first[] = $converter;
            }
        }
        return $first;
    }

    /**
     * Get converter by id
     *
     * @param  object  $config
     * @return  array|false  converter object
     */
    public static function getConverterById($config, $id) {
        if (!isset($config['converters'])) {
            return false;
        }
        $converters = $config['converters'];

        if (!is_array($converters)) {
            return false;
        }

        foreach ($converters as $c) {
            if (!isset($c['converter'])) {
                continue;
            }
            if ($c['converter'] == $id) {
                return $c;
            }
        }
        return false;
    }

    /**
     * Get working converters.
     *
     * @param  object  $config
     * @return  array
     */
    public static function getWorkingConverters($config) {
        if (!isset($config['converters'])) {
            return [];
        }
        $converters = $config['converters'];

        if (!is_array($converters)) {
            return [];
        }

        $result = [];

        foreach ($converters as $c) {
            if (isset($c['working']) && !$c['working']) {
                continue;
            }
            $result[] = $c;
        }
        return $result;
    }

    /**
     *  Get array of working converter ids. Same order as configured.
     */
    public static function getWorkingConverterIds($config)
    {
        $converters = self::getWorkingConverters($config);
        $result = [];
        foreach ($converters as $converter) {
            $result[] = $converter['converter'];
        }
        return $result;
    }

    /**
     * Get working and active converters.
     *
     * @param  object  $config
     * @return  array  Array of converter objects
     */
    public static function getWorkingAndActiveConverters($config)
    {
        if (!isset($config['converters'])) {
            return [];
        }
        $converters = $config['converters'];

        if (!is_array($converters)) {
            return [];
        }

        $result = [];

        foreach ($converters as $c) {
            if (isset($c['deactivated']) && $c['deactivated']) {
                continue;
            }
            if (isset($c['working']) && !$c['working']) {
                continue;
            }
            $result[] = $c;
        }
        return $result;
    }

    /**
     * Get active converters.
     *
     * @param  object  $config
     * @return  array  Array of converter objects
     */
    public static function getActiveConverters($config)
    {
        if (!isset($config['converters'])) {
            return [];
        }
        $converters = $config['converters'];
        if (!is_array($converters)) {
            return [];
        }
        $result = [];
        foreach ($converters as $c) {
            if (isset($c['deactivated']) && $c['deactivated']) {
                continue;
            }
            $result[] = $c;
        }
        return $result;
    }

    public static function getWorkingAndActiveConverterIds($config)
    {
        $converters = self::getWorkingAndActiveConverters($config);
        $result = [];
        foreach ($converters as $converter) {
            $result[] = $converter['converter'];
        }
        return $result;
    }

    public static function getActiveConverterIds($config)
    {
        $converters = self::getActiveConverters($config);
        $result = [];
        foreach ($converters as $converter) {
            $result[] = $converter['converter'];
        }
        return $result;
    }

    /**
     * Get converter id by converter object
     *
     * @param  object  $converter
     * @return  string  converter name, or empty string if not set (it should always be set, however)
     */
    public static function getConverterId($converter) {
        if (!isset($converter['converter'])) {
            return '';
        }
        return $converter['converter'];
    }

    /**
     * Get first working and active converter.
     *
     * @param  object  $config
     * @return  object|false
     */
    public static function getFirstWorkingAndActiveConverter($config) {

        $workingConverters = self::getWorkingAndActiveConverters($config);

        if (count($workingConverters) == 0) {
            return false;
        }
        return $workingConverters[0];
    }

    /**
     * Get first working and active converter (name)
     *
     * @param  object  $config
     * @return  string|false    id of converter, or false if no converter is working and active
     */
     public static function getFirstWorkingAndActiveConverterId($config) {
         $c = self::getFirstWorkingAndActiveConverter($config);
         if ($c === false) {
             return false;
         }
         if (!isset($c['converter'])) {
             return false;
         }
         return $c['converter'];
     }

}
