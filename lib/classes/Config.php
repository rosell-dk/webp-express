<?php

namespace WebPExpress;

include_once "ConvertersHelper.php";
use \WebPExpress\ConvertersHelper;

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

include_once "TestRun.php";
use \WebPExpress\TestRun;

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

    public static $configForOptionsPage = null;     // cache the result (called twice, - also in enqueue_scripts)
    public static function getConfigForOptionsPage()
    {
        if (isset(self::$configForOptionsPage)) {
            return self::$configForOptionsPage;
        }
        // Test converters
        $testResult = TestRun::getConverterStatus();
        $workingConverters = [];
        if ($testResult) {
            $workingConverters = $testResult['workingConverters'];
            //print_r($testResult);
        }

        $canDetectQuality = TestRun::isLocalQualityDetectionWorking();
        $defaultConfig = [
            'cache-control' => 'no-header',
            'cache-control-custom' => 'public, max-age:3600',
            'converters' => [],
            'fail' => 'original',
            'forward-query-string' => true,
            'image-types' => 1,
            'quality-auto' => $canDetectQuality,
            'max-quality' => 80,
            'quality-specific' => 70,
            'metadata' => 'none',
            'do-not-pass-source-in-query-string' => false,
            'redirect-to-existing-in-htaccess' => false,
            'destination-folder' => 'separate',
            'destination-extension' => 'append',
            'success-response' => 'converted',
            'web-service' => [
                'enabled' => false,
                'whitelist' => [
                    /*[
                    'uid' => '',       // for internal purposes
                    'label' => '',     // ie website name. It is just for display
                    'ip' => '',        // restrict to these ips. * pattern is allowed.
                    'api-key' => '',   // Api key for the entry. Not neccessarily unique for the entry
                    //'quota' => 60
                    ]
                    */
                ]

            ]
        ];

        $defaultConverters = ConvertersHelper::$defaultConverters;

        $config = self::loadConfig();
        //echo '<pre>' . print_r($config, true) . '</pre>';
        if (!$config) {
            $config = [];
        }
        //$config = [];

        $config = array_merge($defaultConfig, $config);
        if ($config['converters'] == null) {
            $config['converters'] = [];
        }
        if (!isset($config['web-service'])) {
            $config['web-service'] = [];
        }
        if (!isset($config['web-service']['whitelist'])) {
            $config['web-service']['whitelist'] = [];
        }

        // Remove keys in whitelist (so they cannot easily be picked up by examining the html)
        foreach ($config['web-service']['whitelist'] as &$whitelistEntry) {
            unset($whitelistEntry['api-key']);
        }

        // Remove keys from WPC converters
        foreach ($config['converters'] as &$converter) {
            if (isset($converter['converter']) && ($converter['converter'] == 'wpc')) {
                if (isset($converter['options']['api-key'])) {
                    if ($converter['options']['api-key'] != '') {
                        $converter['options']['_api-key-non-empty'] = true;
                    }
                    unset($converter['options']['api-key']);
                }
            }
        }

        if (count($config['converters']) == 0) {
            // This is first time visit!

            if (count($workingConverters) == 0) {
                // No converters are working
                // Send ewww converter to top
                $resultPart1 = [];
                $resultPart2 = [];
                foreach ($defaultConverters as $converter) {
                    $converterId = $converter['converter'];
                    if ($converterId == 'ewww') {
                        $resultPart1[] = $converter;
                    } else {
                        $resultPart2[] = $converter;
                    }
                }
                $config['converters'] = array_merge($resultPart1, $resultPart2);
            } else {
                // Send converters not working to the bottom
                // - and also deactivate them..
                $resultPart1 = [];
                $resultPart2 = [];
                foreach ($defaultConverters as $converter) {
                    $converterId = $converter['converter'];
                    if (in_array($converterId, $workingConverters)) {
                        $resultPart1[] = $converter;
                    } else {
                        $converter['deactivated'] = true;
                        $resultPart2[] = $converter;
                    }
                }
                $config['converters'] = array_merge($resultPart1, $resultPart2);
            }

            // $workingConverters
            //echo '<pre>' . print_r($converters, true) . '</pre>';
        } else {
            // not first time visit...
            // merge missing converters in
            $config['converters'] = ConvertersHelper::mergeConverters($config['converters'], ConvertersHelper::$defaultConverters);
        }


        // Set "working" and "error" properties
        if ($testResult) {
            foreach ($config['converters'] as &$converter) {
                $converterId = $converter['converter'];
                $hasError = isset($testResult['errors'][$converterId]);
                $working = !$hasError;
                if (isset($converter['working']) && ($converter['working'] != $working)) {
                    if ($working) {
                        Messenger::printMessage(
                            'info',
                            'Hurray! - The <i>' . webpexpress_converterName($converterId) . '</i> conversion method is working now!'
                        );
                    } else {
                        Messenger::printMessage(
                            'warning',
                            'Sad news. The <i>' . webpexpress_converterName($converterId) . '</i> conversion method is not working anymore. What happened?'
                        );
                    }
                }
                $converter['working'] = $working;
                if ($hasError) {
                    $error = $testResult['errors'][$converterId];
                    if ($converterId == 'wpc') {
                        if (preg_match('/Missing URL/', $error)) {
                            $error = 'Not configured';
                        }
                        if ($error == 'No remote host has been set up') {
                            $error = 'Not configured';
                        }

                        if (preg_match('/cloud service is not enabled/', $error)) {
                            $error = 'The server is not enabled. Click the "Enable web service" on WebP Express settings on the site you are trying to connect to.';
                        }
                    }
                    $converter['error'] = $error;
                } else {
                    unset($converter['error']);
                }
            }
        }
        self::$configForOptionsPage = $config;  // cache the result
        return $config;
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
            if (isset($converter['deactivated']) && ($converter['deactivated'])) continue;

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

            if (isset($c['options']['quality']) && ($c['options']['quality'] == 'inherit')) {
                unset ($c['options']['quality']);
            }
            if (!isset($c['options'])) {
                $c = $c['converter'];
            }
        }

        if (isset($options['cache-control'])) {
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
        //unset($options['forward-query-string']);  // It is used in webp-on-demand.php, so do not unset!
        unset($options['do-not-pass-source-in-query-string']);
        unset($options['redirect-to-existing-in-htaccess']);

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
     *  Save both configuration files, but do not update htaccess
     *  Returns success (boolean)
     */
    public static function saveConfigurationFileAndWodOptions($config)
    {
        if (!(self::saveConfigurationFile($config))) {
            return false;
        }
        $options = self::generateWodOptionsFromConfigObj($config);
        return (self::saveWodOptionsFile($options));
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
