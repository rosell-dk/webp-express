<?php

namespace WebPExpress;

class Config
{



    /**
     *  @return  object|false   Returns config object if config file exists and can be read. Otherwise it returns false
     */
    public static function loadConfig()
    {
        return FileHelper::loadJSONOptions(Paths::getConfigFileName());
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
            'image-types' => 3,
            'destination-folder' => 'separate',
            'destination-extension' => 'append',
            'destination-structure' => (PlatformInfo::isNginx() ? 'doc-root' : 'image-roots'),
            'cache-control' => 'no-header',     /* can be "no-header", "set" or "custom" */
            'cache-control-custom' => 'public, max-age=31536000, stale-while-revalidate=604800, stale-if-error=604800',
            'cache-control-max-age' => 'one-week',
            'cache-control-public' => false,
            'scope' => ['themes', 'uploads'],
            'enable-logging' => false,
            'prevent-using-webps-larger-than-original' => true,

            // redirection rules
            'enable-redirection-to-converter' => true,
            'only-redirect-to-converter-on-cache-miss' => false,
            'only-redirect-to-converter-for-webp-enabled-browsers' => true,
            'do-not-pass-source-in-query-string' => false,      // In 0.13 we can remove this. migration7.php depends on it
            'redirect-to-existing-in-htaccess' => true,
            'forward-query-string' => false,
            'enable-redirection-to-webp-realizer' => true,

            // conversion options
            'jpeg-encoding' => 'auto',
            'jpeg-enable-near-lossless' => true,
            'jpeg-near-lossless' => 60,
            'quality-auto' => $qualityAuto,
            'max-quality' => 80,
            'quality-specific' => 70,

            'png-encoding' => 'auto',
            'png-enable-near-lossless' => true,
            'png-near-lossless' => 60,
            'png-quality' => 85,
            'alpha-quality' => 80,

            'converters' => [],
            'metadata' => 'none',
            //'log-call-arguments' => true,
            'convert-on-upload' => false,

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
                'hostname-aliases' => []
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
            ],

            'environment-when-config-was-saved' => [
                'doc-root-available' => null, // null means unavailable
                'doc-root-resolvable' => null,
                'doc-root-usable-for-structuring' => null,
                'image-roots' => null,
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
                //'redirect-to-existing-in-htaccess' => true,   // this can now be configured, so do not apply
                //'enable-redirection-to-converter' => true,  // this can now be configured, so do not apply
                'only-redirect-to-converter-for-webp-enabled-browsers' => true,
                'only-redirect-to-converter-on-cache-miss' => false,
                'do-not-pass-source-in-query-string' => true,       // Will be removed in 0.13
                'fail' => 'original',
                'success-response' => 'converted',
            ]);
        } elseif ($config['operation-mode'] == 'cdn-friendly') {
            $config = array_merge($config, [
                'redirect-to-existing-in-htaccess' => false,
                'enable-redirection-to-converter' => false,
                /*
                'only-redirect-to-converter-for-webp-enabled-browsers' => false,
                'only-redirect-to-converter-on-cache-miss' => true,
                */
                'do-not-pass-source-in-query-string' => true,       // Will be removed in 0.13
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
            $config['scope'] = ['uploads'];

        }

        return $config;
    }

    /**
     *  Fix config.
     *
     *  Among other things, the config is merged with default config, to ensure all options are present
     *
     */
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

            // Make sure new defaults below "alter-html" are added into the existing array
            // (note that this will not remove old unused properties, if some key should become obsolete)
            $config['alter-html'] = array_replace_recursive($defaultConfig['alter-html'], $config['alter-html']);

            // Make sure new defaults below "environment-when-config-was-saved" are added into the existing array
            $config['environment-when-config-was-saved'] = array_replace_recursive($defaultConfig['environment-when-config-was-saved'], $config['environment-when-config-was-saved']);
        }

        if (!isset($config['base-htaccess-on-these-capability-tests'])) {
            self::runAndStoreCapabilityTests($config);
        }

        // Apparently, migrate7 did not fix old "operation-mode" values for all.
        // So fix here
        if ($config['operation-mode'] == 'just-redirect') {
            $config['operation-mode'] = 'no-conversion';
        }
        if ($config['operation-mode'] == 'no-varied-responses') {
            $config['operation-mode'] = 'cdn-friendly';
        }
        if ($config['operation-mode'] == 'varied-responses') {
            $config['operation-mode'] = 'varied-image-responses';
        }

        // In case doc root no longer can be used, use image-roots
        // Or? No, changing here will not fix it for WebPOnDemand.php.
        // An invalid setting requires that config is saved again and .htaccess files regenerated.
        /*
        if (($config['operation-mode'] == 'doc-root') && (!Paths::canUseDocRootForRelPaths())) {
            $config['destination-structure'] = 'image-roots';
        }*/

        $config = self::applyOperationMode($config);

        // Fix scope: Remove invalid and put in correct order
        $fixedScope = [];
        foreach (Paths::getImageRootIds() as $rootId) {
            if (in_array($rootId, $config['scope'])) {
                $fixedScope[] = $rootId;
            }
        }
        $config['scope'] = $fixedScope;

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

        /*if (is_null($config['alter-html']['hostname-aliases'])) {
            $config['alter-html']['hostname-aliases'] = [];
        }*/

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
            $config['converters'] = ConvertersHelper::$defaultConverters;
        }


        return $config;
    }


    public static function runAndStoreCapabilityTests(&$config)
    {
        $config['base-htaccess-on-these-capability-tests'] = [
            'passThroughHeaderWorking' => HTAccessCapabilityTestRunner::passThroughHeaderWorking(),
            'passThroughEnvWorking' => HTAccessCapabilityTestRunner::passThroughEnvWorking(),
            'modHeaderWorking' => HTAccessCapabilityTestRunner::modHeaderWorking(),
            //'grantAllAllowed' => HTAccessCapabilityTestRunner::grantAllAllowed(),
            'canRunTestScriptInWOD' => HTAccessCapabilityTestRunner::canRunTestScriptInWOD(),
            'canRunTestScriptInWOD2' => HTAccessCapabilityTestRunner::canRunTestScriptInWOD2(),
        ];
    }

    /**
     *   Loads Config (if available), fills in the rest with defaults
     *   also applies operation mode.
     *   If config is not saved yet, the default config will be returned
     */
    public static function loadConfigAndFix($checkQualityDetection = true)
    {
        // PS: Yes, loadConfig may return false. "fix" handles this by returning default config
        return self::fix(Config::loadConfig(), $checkQualityDetection);
    }

    /**
     * Run a fresh test on all converters and update their statuses in the config.
     *
     * @param  object  config to be updated
     * @return object  Updated config
     */
    public static function updateConverterStatusWithFreshTest($config) {
        // Test converters
        $testResult = TestRun::getConverterStatus();

        // Set "working" and "error" properties
        if ($testResult) {
            foreach ($config['converters'] as &$converter) {
                $converterId = $converter['converter'];
                $hasError = isset($testResult['errors'][$converterId]);
                $hasWarning = isset($testResult['warnings'][$converterId]);
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
                if ($hasWarning) {
                    $converter['warnings'] = $testResult['warnings'][$converterId];
                }
            }
        }
        return $config;
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
            $config = self::updateConverterStatusWithFreshTest($config);
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
        return FileHelper::loadJSONOptions(Paths::getWodOptionsFileName());
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
        $obj['destination-structure'] = $config['destination-structure'];
        $obj['scope'] = $config['scope'];
        $obj['image-types'] = $config['image-types'];   // 0=none,1=jpg, 2=png, 3=both
        $obj['prevent-using-webps-larger-than-original'] = $config['prevent-using-webps-larger-than-original'];

        Option::updateOption(
            'webp-express-alter-html-options',
            json_encode($obj, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK),
            true
        );
    }

    /**
     * Save configuration file. Also updates autoloaded options (such as alter html options)
     */
    public static function saveConfigurationFile($config)
    {
        $config['paths-used-in-htaccess'] = [
            'wod-url-path' => Paths::getWodUrlPath(),
        ];

        if (Paths::createConfigDirIfMissing()) {
            $success = FileHelper::saveJSONOptions(Paths::getConfigFileName(), $config);
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

        // WebP convert options
        // --------------------
        $wc = [
            'converters' => []
        ];

        // Add active converters
        foreach ($config['converters'] as $converter) {
            if (isset($converter['deactivated']) && ($converter['deactivated'])) {
                continue;
            }
            $wc['converters'][] = $converter;
        }

        // Clean the converter options from junk
        foreach ($wc['converters'] as &$c) {

            // In cwebp converter options (here in webp express), we have a checkbox "set size"
            // - there is no such option in webp-convert - so remove.
            if ($c['converter'] == 'cwebp') {
                if (isset($c['options']['set-size']) && $c['options']['set-size']) {
                    unset($c['options']['set-size']);
                } else {
                    unset($c['options']['set-size']);
                    unset($c['options']['size-in-percentage']);
                }
            }

            if ($c['converter'] == 'ewww') {
                $c['options']['check-key-status-before-converting'] = false;
            }

            // 'id', 'working' and 'error' attributes are used internally in webp-express,
            // no need to have it in the wod configuration file.
            unset ($c['id']);
            unset($c['working']);
            unset($c['error']);

            if (isset($c['options']['quality']) && ($c['options']['quality'] == 'inherit')) {
                unset ($c['options']['quality']);
            }
            /*
            if (!isset($c['options'])) {
                $c = $c['converter'];
            }*/
        }

        // Create jpeg options
        // https://github.com/rosell-dk/webp-convert/blob/master/docs/v2.0/converting/introduction-for-converting.md#png-og-jpeg-specific-options

        $auto = (isset($config['quality-auto']) && $config['quality-auto']);
        $wc['jpeg'] = [
            'encoding' => $config['jpeg-encoding'],
            'quality' => ($auto ? 'auto' : $config['quality-specific']),
        ];
        if ($auto) {
            $wc['jpeg']['default-quality'] = $config['quality-specific'];
            $wc['jpeg']['max-quality'] = $config['max-quality'];
        }
        if ($config['jpeg-encoding'] != 'lossy') {
            if ($config['jpeg-enable-near-lossless']) {
                $wc['jpeg']['near-lossless'] = $config['jpeg-near-lossless'];
            } else {
                $wc['jpeg']['near-lossless'] = 100;
            }
        }

        // Create png options
        // ---
        $wc['png'] = [
            'encoding' => $config['png-encoding'],
            'quality' => $config['png-quality'],
        ];
        if ($config['png-encoding'] != 'lossy') {
            if ($config['png-enable-near-lossless']) {
                $wc['png']['near-lossless'] = $config['png-near-lossless'];
            } else {
                $wc['png']['near-lossless'] = 100;
            }
        }
        if ($config['png-encoding'] != 'lossless') {
            // Only relevant for pngs, and only for "lossy" (and thus also "auto")
            $wc['png']['alpha-quality'] = $config['alpha-quality'];
        }

        // Other convert options
        $wc['metadata'] = $config['metadata'];
        $wc['log-call-arguments'] = true; // $config['log-call-arguments'];

        // Serve options
        // -------------
        $serve = [
            'serve-image' => [
                'headers' => [
                    'cache-control' => false,
                    'content-length' => true,
                    'content-type' => true,
                    'expires' => false,
                    'last-modified' => true,
                    //'vary-accept' => false        // This must be different for webp-on-demand and webp-realizer
                ]
            ]
        ];
        if ($config['cache-control'] != 'no-header') {
            $serve['serve-image']['cache-control-header'] = self::getCacheControlHeader($config);
            $serve['serve-image']['headers']['cache-control'] = true;
            $serve['serve-image']['headers']['expires'] = true;
        }
        $serve['fail'] = $config['fail'];


        // WOD options
        // -------------
        $wod = [
            'enable-logging' => $config['enable-logging'],
            'enable-redirection-to-converter' => $config['enable-redirection-to-converter'],
            'enable-redirection-to-webp-realizer' => $config['enable-redirection-to-webp-realizer'],
            'base-htaccess-on-these-capability-tests' => $config['base-htaccess-on-these-capability-tests'],
            'destination-extension' => $config['destination-extension'],
            'destination-folder' => $config['destination-folder'],
            'forward-query-string' => $config['forward-query-string'],
            //'method-for-passing-source' => $config['method-for-passing-source'],
            'image-roots' => Paths::getImageRootsDef(),
            'success-response' => $config['success-response'],
        ];


        // Put it all together
        // -------------

        //$options = array_merge($wc, $serve, $wod);

        // I'd like to put the webp-convert options in its own key,
        // but it requires some work. Postponing it to another day that I can uncomment the two next lines (and remove the one above)
        //$wc = array_merge($wc, $serve);
        //$options = array_merge($wod, ['webp-convert' => $wc]);

        //$options = array_merge($wod, array_merge($serve, ['conversion' => $wc]));

        $options = [
            'wod' => $wod,
            'webp-convert' => array_merge($serve, ['convert' => $wc])
        ];


        return $options;
    }

    public static function saveWodOptionsFile($options)
    {
        if (Paths::createConfigDirIfMissing()) {
            return FileHelper::saveJSONOptions(Paths::getWodOptionsFileName(), $options);
        }
        return false;
    }


    /**
     *  Save both configuration files, but do not update htaccess
     *  Returns success (boolean)
     */
    public static function saveConfigurationFileAndWodOptions($config)
    {
        if (!isset($config['base-htaccess-on-these-capability-tests'])) {
            self::runAndStoreCapabilityTests($config);
        }
        if (!(self::saveConfigurationFile($config))) {
            return false;
        }
        $options = self::generateWodOptionsFromConfigObj($config);
        return (self::saveWodOptionsFile($options));
    }

    /**
     * Regenerate config and .htaccess files
     *
     * It will only happen if configuration file exists. So the method is meant for updating - ie upon migration.
     * It updates:
     * - config files (both) - and ensures that capability tests have been run
     * - autoloaded options (such as alter html options)
     * - .htaccess files (all)
     */
    public static function regenerateConfigAndHtaccessFiles() {
        self::regenerateConfig(true);
    }

    /**
     * Regenerate config and .htaccess files
     *
     * It will only happen if configuration file exists. So the method is meant for updating - ie upon migration.
     * It updates:
     * - config files (both) - and ensures that capability tests have been run
     * - autoloaded options (such as alter html options)
     * - .htaccess files - but only if needed due to configuration changes
     */
    public static function regenerateConfig($forceRuleUpdating = false) {
        if (!self::isConfigFileThere()) {
            return;
        }
        $config = self::loadConfig();
        $config = self::fix($config, false);    // fix. We do not need examining if quality detection is working
        if ($config === false) {
            return;
        }
        self::saveConfigurationAndHTAccess($config, $forceRuleUpdating);
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
            $rewriteRulesNeedsUpdate = HTAccessRules::doesRewriteRulesNeedUpdate($config);
        }

        if (!isset($config['base-htaccess-on-these-capability-tests']) || $rewriteRulesNeedsUpdate) {
            self::runAndStoreCapabilityTests($config);
        }

        if (self::saveConfigurationFile($config)) {
            $options = self::generateWodOptionsFromConfigObj($config);
            if (self::saveWodOptionsFile($options)) {
                if ($rewriteRulesNeedsUpdate) {
                    $rulesResult = HTAccess::saveRules($config, false);
                    return [
                        'saved-both-config' => true,
                        'saved-main-config' => true,
                        'rules-needed-update' => true,
                        'htaccess-result' => $rulesResult
                    ];
                }
                else {
                    $rulesResult = HTAccess::saveRules($config, false);
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
