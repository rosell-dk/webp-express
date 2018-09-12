<?php

namespace WebPExpress;

include_once "FileHelper.php";
use \WebPExpress\FileHelper;

include_once "Paths.php";
use \WebPExpress\Paths;

include_once "State.php";
use \WebPExpress\State;

class Config
{

    /**
     *  Return object or false, if config file does not exist, or read error
     */
    public static function loadJSONOptions($filename)
    {
        $json = FileHelper::loadFile($filename);
        if ($json === false) {
            return false;
        }

        $options = json_decode($json, true);
        if ($options === null) {
            return false;
        }
        return $options;
    }

    public static function saveJSONOptions($filename, $obj)
    {
        $result = @file_put_contents($filename, json_encode($obj, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
        /*if ($result === false) {
            echo 'COULD NOT' . $filename;
        }*/
        return ($result !== false);
    }


    public static function loadConfig()
    {
        return self::loadJSONOptions(Paths::getConfigFileName());
    }

    public static function isConfigFileThere()
    {
        return (FileHelper::fileExists(Paths::getConfigFileName()));
    }

    public static function isConfigFileThereAndOk()
    {
        return (self::loadConfig() !== false);
    }

    public static function loadWodOptions()
    {
        return self::loadJSONOptions(Paths::getWodOptionsFileName());
    }

    public static function saveConfigurationFile($config)
    {
        $config['paths-used-in-htaccess'] = [
            'existing' => Paths::getPathToExisting(),
            'wod-url-path' => Paths::getWodUrlPath(),
            'config-dir-rel' => Paths::getConfigDirRel()
        ];

        if (Paths::createConfigDirIfMissing()) {
            $success = self::saveJSONOptions(Paths::getConfigFileName(), $config);
            if ($success) {
                State::setState('configured', true);
            }
            return $success;
        }
        return false;
    }

    public static function generateWodOptionsFromConfigObj($config)
    {
        $options = $config;
        $options['converters'] = [];
        foreach ($config['converters'] as $converter) {
            if (isset($converter['deactivated'])) continue;

            $options['converters'][] = $converter;
        }
        foreach ($options['converters'] as &$c) {
            unset ($c['id']);
            if (!isset($c['options'])) {
                $c = $c['converter'];
            }
        }

        unset($options['image-types']);
        return $options;
    }

    public static function saveWodOptionsFile($options)
    {
        if (Paths::createConfigDirIfMissing()) {
            return self::saveJSONOptions(Paths::getWodOptionsFileName(), $options);
        }
        return false;
    }


    /**
     *  - Saves configuration file
     *   AND generates WOD options, and saves them too
     *   AND saves .htaccess
     */
     /*
    public static function saveConfiguration($config)
    {
        if (self::saveConfigurationFile($config)) {
            $options = self::generateWodOptionsFromConfigObj($config);
            if (self::saveWodOptionsFile($options)) {
                $rules = self::generateHTAccessRulesFromConfigObj($config);
                return self::saveHTAccessRules($rules);

            }
        } else {
            return false;
        }
    }*/

}
