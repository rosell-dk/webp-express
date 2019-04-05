<?php

namespace WebPExpress;

use \WebPExpress\ConvertersHelper;
use \WebPExpress\FileHelper;
use \WebPExpress\HTAccess;
use \WebPExpress\Messenger;
use \WebPExpress\Paths;
use \WebPExpress\State;
use \WebPExpress\TestRun;
use \WebPExpress\Option;

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
        $result = @file_put_contents($filename, json_encode($obj, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT));
        /*if ($result === false) {
            echo 'COULD NOT' . $filename;
        }*/
        return ($result !== false);
    }


    public static function loadConfig()
    {
        return self::loadJSONOptions(Paths::getConfigFileName());
    }

    public static function getDefaultConfig($skipQualityAuto = false) {
        if ($skipQualityAuto) {
            $qualityAuto = null;
        } else {
            $qualityAuto = TestRun::isLocalQualityDetectionWorking();
        }

        return [

            'operation-mode' => 'varied-image-responses',

            // general
            'image-types' => 1,
            'destination-folder' => 'separate',
            'destination-extension' => 'append',
            'cache-control' => 'no-header',     /* can be "no-header", "set" or "custom" */
            'cache-control-custom' => 'public, max-age=31536000, stale-while-revalidate=604800, stale-if-error=604800',
            'cache-control-max-age' => 'one-week',
            'cache-control-public' => false,

            // redirection rules
            'enable-redirection-to-converter' => true,
            'only-redirect-to-converter-on-cache-miss' => false,
            'only-redirect-to-converter-for-webp-enabled-browsers' => true,
            'do-not-pass-source-in-query-string' => false,      // In 0.13 we can remove this. migration7.php depends on it
            'redirect-to-existing-in-htaccess' => true,
            'forward-query-string' => false,
            'enable-redirection-to-webp-realizer' => true,

            // conversion options
            'converters' => [],
            'quality-auto' => $qualityAuto,
            'max-quality' => 80,
            'quality-specific' => 70,
            'metadata' => 'none',
            'convert-on-upload' => true,

            // serve options
            'fail' => 'original',
            'success-response' => 'converted',

            // alter html options
            'alter-html' => [
                'enabled' => false,
                'replacement' => 'picture',          // "picture" or "url"
                'hooks' => 'ob',             // "content-hooks" or "ob"
                'only-for-webp-enabled-browsers' => true,     // If true, there will be two HTML versions of each page
                'only-for-webps-that-exists' => false,
                'alter-html-add-picturefill-js' => true,
            ],

            // web service
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
    }

    /**
     *   Apply operation mode (set the hidden defaults that comes along with the mode)
     *   @return An altered configuration array
     */
    public static function applyOperationMode($config)
    {
        if (!isset($config['operation-mode'])) {
            $config['operation-mode'] = 'varied-image-responses';
        }

        if ($config['operation-mode'] == 'varied-image-responses') {
            $config = array_merge($config, [
                'enable-redirection-to-converter' => true,
                'only-redirect-to-converter-for-webp-enabled-browsers' => true,
                'only-redirect-to-converter-on-cache-miss' => false,
                'do-not-pass-source-in-query-string' => true,       // Will be removed in 0.13
                //'redirect-to-existing-in-htaccess' => true,
                'fail' => 'original',
                'success-response' => 'converted',
            ]);
        } elseif ($config['operation-mode'] == 'cdn-friendly') {
            $config = array_merge($config, [
                'only-redirect-to-converter-for-webp-enabled-browsers' => false,
                'only-redirect-to-converter-on-cache-miss' => true,
                'do-not-pass-source-in-query-string' => true,       // Will be removed in 0.13
                'redirect-to-existing-in-htaccess' => false,
                'fail' => 'original',
                'success-response' => 'original',
                // cache-control => 'no-header' (we do not need this, as it is not important what it is set to in cdn-friendly mode, and we dont the value to be lost when switching operation mode)
            ]);
        } elseif ($config['operation-mode'] == 'no-conversion') {

            // TODO: Go through these...

            $config = array_merge($config, [
                'enable-redirection-to-converter' => false,
                'destination-folder' => 'mingled',
                'enable-redirection-to-webp-realizer' => false,
            ]);
            $config['alter-html']['only-for-webps-that-exists'] = true;
            $config['web-service']['enabled'] = false;

        }

        return $config;
    }

    public static function fix($config, $checkQualityDetection = true)
    {
        if ($config === false) {
            $config = self::getDefaultConfig(!$checkQualityDetection);
        } else {
            if ($checkQualityDetection) {
                if (isset($config['quality-auto']) && ($config['quality-auto'])) {
                    $qualityDetectionWorking = TestRun::isLocalQualityDetectionWorking();
                    if (!TestRun::isLocalQualityDetectionWorking()) {
                        $config['quality-auto'] = false;
                    }
                }
            }
            $defaultConfig = self::getDefaultConfig(true);
            $config = array_merge($defaultConfig, $config);

            $config['alter-html'] = array_replace_recursive($defaultConfig['alter-html'], $config['alter-html']);
        }

        $config = self::applyOperationMode($config);

        if (!isset($config['web-service'])) {
            $config['web-service'] = [
                'enabled' => false
            ];
        }
        if (!is_array($config['web-service']['whitelist'])) {
            $config['web-service']['whitelist'] = [];
        }
        // remove whitelist entries without required fields (label, ip)
        $config['web-service']['whitelist'] = array_filter($config['web-service']['whitelist'], function($var) {
            return (isset($var['label']) && (isset($var['ip'])));
        });

        if (($config['cache-control'] == 'set') && ($config['cache-control-max-age'] == '')) {
            $config['cache-control-max-age'] = 'one-week';
        }

        if (!is_array($config['converters'])) {
            $config['converters'] = [];
        }

        if (count($config['converters']) > 0) {
            // merge missing converters in
            $config['converters'] = ConvertersHelper::mergeConverters(
                $config['converters'],
                ConvertersHelper::$defaultConverters
            );
        } else {

            // This is first time visit!
            // We must add converters.
            // We want to order them according to which ones that are working,
            // so we run
            $testResult = TestRun::getConverterStatus();
            $workingConverters = [];
            if ($testResult) {
                $workingConverters = $testResult['workingConverters'];
                //print_r($testResult);
            }

            $defaultConverters = ConvertersHelper::$defaultConverters;

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
        }


        return $config;
    }


    public static function runAndStoreCapabilityTests(&$config)
    {
        $config['base-htaccess-on-these-capability-tests'] = [
            'passThroughHeaderWorking' => CapabilityTest::passThroughHeaderWorking(),
            'passThroughEnvWorking' => CapabilityTest::passThroughEnvWorking(),
            'modHeaderWorking' => CapabilityTest::modHeaderWorking(),
        ];
    }

    /**
     *   Loads Config (if available), fills in the rest with defaults
     *   also applies operation mode.
     */
    public static function loadConfigAndFix($checkQualityDetection = true)
    {
        return self::fix(Config::loadConfig(), $checkQualityDetection);
    }


    public static $configForOptionsPage = null;     // cache the result (called twice, - also in enqueue_scripts)
    public static function getConfigForOptionsPage()
    {
        if (isset(self::$configForOptionsPage)) {
            return self::$configForOptionsPage;
        }


        $config = self::loadConfigAndFix();

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

        if ($config['operation-mode'] != 'no-conversion') {
            // Test converters
            $testResult = TestRun::getConverterStatus();

            // Set "working" and "error" properties
            if ($testResult) {
                foreach ($config['converters'] as &$converter) {
                    $converterId = $converter['converter'];
                    $hasError = isset($testResult['errors'][$converterId]);
                    $working = !$hasError;

                    /*
                    Don't print this stuff here. It can end up in the head tag.
                    TODO: Move it somewhere
                    if (isset($converter['working']) && ($converter['working'] != $working)) {

                        // TODO: webpexpress_converterName($converterId)
                        if ($working) {
                            Messenger::printMessage(
                                'info',
                                'Hurray! - The <i>' . $converterId . '</i> conversion method is working now!'
                            );
                        } else {
                            Messenger::printMessage(
                                'warning',
                                'Sad news. The <i>' . $converterId . '</i> conversion method is not working anymore. What happened?'
                            );
                        }
                    }
                    */
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

    /**
     *  Some of the options in config needs to be quickly accessible
     *  These are stored in wordpress autoloaded options
     */
    public static function updateAutoloadedOptions($config)
    {
        $config = self::fix($config, false);

        Option::updateOption('webp-express-alter-html', $config['alter-html']['enabled'], true);
        Option::updateOption('webp-express-alter-html-hooks', $config['alter-html']['hooks'], true);
        Option::updateOption('webp-express-alter-html-replacement', $config['alter-html']['replacement'], true);
        Option::updateOption('webp-express-alter-html-add-picturefill-js', (($config['alter-html']['replacement'] == 'picture') && (isset($config['alter-html']['alter-html-add-picturefill-js']) && $config['alter-html']['alter-html-add-picturefill-js'])), true);


        //Option::updateOption('webp-express-alter-html', $config['alter-html']['enabled'], true);

        $obj = $config['alter-html'];
        unset($obj['enabled']);
        $obj['destination-folder'] = $config['destination-folder'];
        $obj['destination-extension'] = $config['destination-extension'];
        $obj['bases'] = [
            'uploads' => [
                Paths::getUploadDirAbs(),
                Paths::getUploadUrl()
            ],
            'content' => [
                Paths::getContentDirAbs(),
                Paths::getContentUrl()
            ],
        ];
        $obj['image-types'] = $config['image-types'];   // 0=none,1=jpg, 2=png, 3=both

        Option::updateOption(
            'webp-express-alter-html-options',
            json_encode($obj, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK),
            true
        );
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
                self::updateAutoloadedOptions($config);
            }

            return $success;
        }
        return false;
    }

    public static function getCacheControlHeader($config) {
        $cacheControl = $config['cache-control'];
        switch ($cacheControl) {
            case 'custom':
                return $config['cache-control-custom'];
            case 'no-header':
                return '';
            default:
                $public = (isset($config['cache-control-public']) ? $config['cache-control-public'] : true);
                $maxAge = (isset($config['cache-control-max-age']) ? $config['cache-control-max-age'] : $cacheControl);
                $maxAgeOptions = [
                    '' => 'max-age=604800',      // it has happened, but I don't think it can happen again...
                    'one-second' => 'max-age=1',
                    'one-minute' => 'max-age=60',
                    'one-hour' => 'max-age=3600',
                    'one-day' => 'max-age=86400',
                    'one-week' => 'max-age=604800',
                    'one-month' => 'max-age=2592000',
                    'one-year' => 'max-age=31536000',
                ];
                return ($public ? 'public, ' : 'private, ') . $maxAgeOptions[$maxAge];
        }

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
            $options['cache-control-header'] = self::getCacheControlHeader($config);
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
        unset($options['cache-control-public']);
        unset($options['cache-control-max-age']);
        unset($options['paths-used-in-htaccess']);
        unset($options['web-service']);
        unset($options['alter-html']);
        unset($options['enable-redirection-to-converter']);
        unset($options['operation-mode']);
        unset($options['only-redirect-to-converter-for-webp-enabled-browsers']);
        unset($options['only-redirect-to-converter-on-cache-miss']);
        unset($options['enable-redirection-to-webp-realizer']);
        unset($options['convert-on-upload']);


        //unset($options['']);
        //unset($options['']);
        //unset($options['']);



        //unset($options['forward-query-string']);  // It is used in webp-on-demand.php, so do not unset!
        unset($options['do-not-pass-source-in-query-string']);
        unset($options['redirect-to-existing-in-htaccess']);

        $options['paths'] = [
            'uploadDirRel' => Paths::getUploadDirRel()
        ];

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

    public static function getConverterByName($config, $converterName)
    {
        foreach ($config['converters'] as $i => $converter) {
            if ($converter['converter'] == $converterName) {
                return $converter;
            }
        }
    }

}
