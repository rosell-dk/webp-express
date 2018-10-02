<?php

namespace WebPExpress;

include_once "FileHelper.php";
use \WebPExpress\FileHelper;

include_once "HTAccess.php";
use \WebPExpress\HTAccess;

include_once "Messenger.php";
use \WebPExpress\Messenger;

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
            if ($c['converter'] == 'cwebp') {
                if (isset($c['options']['set-size']) && $c['options']['set-size']) {
                    unset($c['options']['set-size']);
                } else {
                    unset($c['options']['set-size']);
                    unset($c['options']['size-in-percentage']);
                }
            }
            unset ($c['id']);
            unset($c['working']);
            unset($c['error']);
            if (!isset($c['options'])) {
                $c = $c['converter'];
            }
        }

        $cacheControl = $options['cache-control'];
        $cacheControlOptions = [
            'no-header' => '',
            'one-second' => 'public, max-age=1',
            'one-minute' => 'public, max-age=60',
            'one-hour' => 'public, max-age=3600',
            'one-day' => 'public, max-age=86400',
            'one-week' => 'public, max-age=604800',
            'one-month' => 'public, max-age=2592000',
            'one-year' => 'public, max-age=31536000',
        ];

        if (isset($cacheControlOptions[$cacheControl])) {
            $options['cache-control-header'] = $cacheControlOptions[$cacheControl];
        } else {
            $options['cache-control-header'] = $options['cache-control-custom'];
        }

        $auto = (isset($options['quality-auto']) && $options['quality-auto']);
        $qualitySpecific = (isset($options['quality-specific']) ? $options['quality-specific'] : 70);
        if ($auto) {
            $options['quality'] = 'auto';
        } else {
            $options['quality'] = $qualitySpecific;
            unset ($options['max-quality']);
        }
        unset($options['quality-auto']);
        unset($options['quality-specific']);

        unset($options['image-types']);
        unset($options['cache-control']);
        unset($options['cache-control-custom']);

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
     *
     *  $rewriteRulesNeedsUpdate:
     */
    public static function saveConfigurationAndHTAccess($config, $forceRuleUpdating = false)
    {
        // Important to do this check before saving config, because the method
        // compares against existing config.

        if ($forceRuleUpdating) {
            $rewriteRulesNeedsUpdate = true;
        } else {
            $rewriteRulesNeedsUpdate = HTAccess::doesRewriteRulesNeedUpdate($config);
        }

        if (self::saveConfigurationFile($config)) {
            $options = self::generateWodOptionsFromConfigObj($config);
            if (self::saveWodOptionsFile($options)) {
                if ($rewriteRulesNeedsUpdate) {
                    $rulesResult = HTAccess::saveRules($config);
                    return [
                        'saved-both-config' => true,
                        'saved-main-config' => true,
                        'rules-needed-update' => true,
                        'htaccess-result' => $rulesResult
                    ];
                }
                else {
                    $rulesResult = HTAccess::saveRules($config);
                    return [
                        'saved-both-config' => true,
                        'saved-main-config' => true,
                        'rules-needed-update' => false,
                        'htaccess-result' => $rulesResult
                    ];
                }
            } else {
                return [
                    'saved-both-config' => false,
                    'saved-main-config' => true,
                ];
            }
        } else {
            return [
                'saved-both-config' => false,
                'saved-main-config' => false,
            ];
        }
    }
}
