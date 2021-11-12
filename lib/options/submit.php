<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use \WebPExpress\CacheMover;
use \WebPExpress\Config;
use \WebPExpress\ConvertersHelper;
use \WebPExpress\DismissableMessages;
use \WebPExpress\HTAccess;
use \WebPExpress\HTAccessRules;
use \WebPExpress\Messenger;
use \WebPExpress\PathHelper;
use \WebPExpress\Paths;

// TODO: Move this code to a class

check_admin_referer('webpexpress-save-settings-nonce');

DismissableMessages::dismissMessage('0.14.0/say-hello-to-vips');

DismissableMessages::dismissMessage('0.15.0/new-scope-setting-no-uploads');
DismissableMessages::dismissMessage('0.15.0/new-scope-setting-index');
DismissableMessages::dismissMessage('0.15.0/new-scope-setting-content');
DismissableMessages::dismissMessage('0.15.1/problems-with-mingled-set');

/*
--------------------------------
Custom functions for sanitizing
--------------------------------
*/

/**
 * Get sanitized text (NUL removed too)
 *
 * General purpose for getting textual values from $_POST.
 * If the POST value is not set, the fallback is returned
 *
 * For sanitizing, the wordpress function "sanitize_text_field" is used. However, before doing that, we
 * remove any NUL characters. NUL characters can be used to trick input validation, so we better get rid of those
 * right away
 *
 * @param  string  $keyInPOST            key in $_POST
 * @param  int     $fallback             value to return if the POST does not match any in the set, or it is not send at all
 * @param  array   $acceptableValues     the set of values that we have to choose between
 *
 * @return string  sanitized text, or fallback if value isn't set
 */
function webpexpress_getSanitizedText($keyInPOST, $fallbackValue = '') {
    if (!isset($_POST[$keyInPOST])) {
        return $fallbackValue;
    }
    $value = $_POST[$keyInPOST];

    // Keep in mind checking for NUL when dealing with user input
    // see https://st-g.de/2011/04/doing-filename-checks-securely-in-PHP
    $value = str_replace(chr(0), '', $value);

    return sanitize_text_field($value);
}

/**
 * Get sanitized value from a set of values.
 *
 * Only allows values in the set given. If the value does not match, the fallback will be returned.
 *
 * @param  string  $keyInPOST            key in $_POST
 * @param  int     $fallback             value to return if the POST does not match any in the set, or it is not send at all
 * @param  array   $acceptableValues     the set of values that we have to choose between
 *
 * @return mixed   one of the items in the set - or fallback (which is usually also one in the set)
 */
function webpexpress_getSanitizedChooseFromSet($keyInPOST, $fallbackValue, $acceptableValues) {
    $value = webpexpress_getSanitizedText($keyInPOST, $fallbackValue);
    if (in_array($value, $acceptableValues)) {
        return $value;
    }
    return $fallbackValue;
}

function webpexpress_getSanitizedCacheControlHeader($keyInPOST) {
    $value = webpexpress_getSanitizedText($keyInPOST);

    // Example of valid header: "public, max-age=31536000, stale-while-revalidate=604800, stale-if-error=604800"
    $value = strtolower($value);
    return preg_replace('#[^a-z0-9=,\s_\-]#', '', $value);
}

/**
 * Get sanitized integer
 *
 * @param  string  $keyInPOST  key in $_POST
 * @param  int     $fallback   fallback in case nothing in POST or if we cannot parse it as int
 *
 * @return int     the sanitized int value.
 */
function webpexpress_getSanitizedInt($keyInPOST, $fallback=0) {
    $value = webpexpress_getSanitizedText($keyInPOST, strval($fallback));

    // strip anything after and including comma
    $value = preg_replace('#[\.\,].*#', '', $value);

    // remove anything but digits
    $value = preg_replace('#[^0-9]#', '', $value);

    if ($value == '') {
        return $fallback;
    }

    return intval($value);
}

/**
 * Get sanitized quality (0-100).
 *
 * @param  string  $keyInPOST  key in $_POST
 *
 * @return int  quality (0-100)
 */
function webpexpress_getSanitizedQuality($keyInPOST, $fallback = 75) {
    $q = webpexpress_getSanitizedInt($keyInPOST, $fallback);
    // return value between 0-100
    return max(0, min($q, 100));
}

function webpexpress_getSanitizedScope() {
    $scopeText = webpexpress_getSanitizedText('scope');
    if ($scopeText == '') {
        $scopeText = 'uploads';
    }
    $scope = explode(',', $scopeText);
    $allowed = Paths::getImageRootIds();
    $result = [];
    foreach ($scope as $imageRootId) {
        if (in_array($imageRootId, $allowed)) {
            $result[] = $imageRootId;
        }
    }
    return $result;
}

/**
 * Get sanitized whitelist
 *
 * @return array  Sanitized array of the whitelist json array received in $_POST
 */
function webpexpress_getSanitizedWhitelist() {
    $whitelistPosted = (isset($_POST['whitelist']) ? $_POST['whitelist'] : '[]');

    $whitelistPosted = json_decode(wp_unslash($whitelistPosted), true);
    // TODO: check for json decode error

    $whitelistSanitized = [];

    // Sanitize whitelist
    foreach ($whitelistPosted as $whitelist) {
        if (
            isset($whitelist['label']) &&
            isset($whitelist['ip'])
            // note: api-key is not neccessarily set
        ) {
            $obj = [
                'label' => sanitize_text_field($whitelist['label']),
                'ip' => sanitize_text_field($whitelist['ip']),
            ];
            if (isset($whitelist['new-api-key'])) {
                $obj['new-api-key'] = sanitize_text_field($whitelist['new-api-key']);
            }
            if (isset($whitelist['uid'])) {
                $obj['uid'] = sanitize_text_field($whitelist['uid']);
            }
            if (isset($whitelist['require-api-key-to-be-crypted-in-transfer'])) {
                $obj['require-api-key-to-be-crypted-in-transfer'] = ($whitelist['require-api-key-to-be-crypted-in-transfer'] === true);
            }

            $whitelistSanitized[] = $obj;
        }
    }
    return $whitelistSanitized;
}

/**
 * Get sanitized converters.
 *
 * @return array  Sanitized array of the converters json array received in $_POST
 */
function webpexpress_getSanitizedConverters() {
    $convertersPosted = (isset($_POST['converters']) ? $_POST['converters'] : '[]');
    $convertersPosted = json_decode(wp_unslash($convertersPosted), true); // holy moly! Wordpress automatically adds slashes to the global POST vars- https://stackoverflow.com/questions/2496455/why-are-post-variables-getting-escaped-in-php

    $convertersSanitized = [];

    // Get list of possible converter ids.
    $availableConverterIDs = ConvertersHelper::getDefaultConverterNames();

    // Add converters one at the time.
    foreach ($convertersPosted as $unsanitizedConverter) {
        if (!isset($unsanitizedConverter['converter'])) {
            continue;
        }

        // Only add converter if its ID is a known converter.
        if (!in_array($unsanitizedConverter['converter'], $availableConverterIDs)) {
            continue;
        }

        $sanitizedConverter = [];
        $sanitizedConverter['converter'] = $unsanitizedConverter['converter'];

        // Sanitize and add expected fields ("options", "working", "deactivated" and "error")

        // "options"
        if (isset($unsanitizedConverter['options'])) {
            $sanitizedConverter['options'] = [];

            // Sanitize all (string) options individually
            foreach ($unsanitizedConverter['options'] as $optionName => $unsanitizedOptionValue) {

                $acceptedOptions = [
                    // vips
                    'smart-subsample' => 'boolean',
                    'preset' => 'string',

                    // gd
                    'skip-pngs' => 'boolean',

                    // in multiple
                    "use-nice" => 'boolean',

                    // cwebp
                    "try-common-system-paths" => 'boolean',
                    "try-supplied-binary-for-os" => 'boolean',
                    "skip-these-precompiled-binaries" => 'string',
                    "method" => 'integer',  // 0-6,
                    "size-in-percentage" => 'integer',  // 0-100
                    "low-memory" => 'boolean',
                    "command-line-options" => 'string',     // webp-convert takes care of sanitizing this very carefully!
                    "set-size" => 'boolean',

                    // wpc
                    "api-url" => 'string',
                    "api-version" => 'integer',
                    "crypt-api-key-in-transfer" => 'boolean',
                    "new-api-key" => 'string',

	                  //ewww
                    "api-key" => 'string',
                    "api-key-2" => 'string',
                ];

                // check that it is an accepted option name
                if (!isset($acceptedOptions[$optionName])) {
                    continue;
                }

                // check that type is as expected
                $expectedType = $acceptedOptions[$optionName];
                if (gettype($unsanitizedOptionValue) != $expectedType) {
                    continue;
                }
                if ($expectedType == 'string') {
                    $sanitizedOptionValue = sanitize_text_field($unsanitizedOptionValue);
                } else {
                    // integer and boolean are completely safe!
                    $sanitizedOptionValue = $unsanitizedOptionValue;
                }
                if (($optionName == "size-in-percentage") && ($sanitizedOptionValue == '')) {
                    continue;
                }
                $sanitizedConverter['options'][$optionName] = $sanitizedOptionValue;

            }
        }

        // "working" (bool)
        if (isset($unsanitizedConverter['working'])) {
            $sanitizedConverter['working'] = ($unsanitizedConverter['working'] === true);
        }

        // "deactivated" (bool)
        if (isset($unsanitizedConverter['deactivated'])) {
            $sanitizedConverter['deactivated'] = ($unsanitizedConverter['deactivated'] === true);
        }

        $convertersSanitized[] = $sanitizedConverter;
    }

    return $convertersSanitized;
}

/**
 * Get sanitized converters.
 *
 * @return array  Sanitized array of the converters json array received in $_POST
 */
function webpexpress_getSanitizedAlterHtmlHostnameAliases() {
    $index = 0;

    $result = [];
    while (isset($_POST['alter-html-hostname-alias-' . $index])) {
        $alias = webpexpress_getSanitizedText('alter-html-hostname-alias-' . $index, '');
        $alias = preg_replace('#^https?\\:\\/\\/#', '', $alias);
        //$alias .= 'hm';
        if ($alias != '') {
            $result[] = $alias;
        }
        $index++;
    }
    return $result;
}

/*
------------------------------------------------------

Create a sanitized object from the POST data
It reflects the POST data - it has same keys and values - except that the values have been sanitized.

After this, there must be no more references to $_POST
------------------------------------------------------
*/


// Sanitizing
$sanitized = [
    // Force htaccess rules
    'force' => isset($_POST['force']),


    // Operation mode
    // --------------
    // Note that "operation-mode" is actually the old mode. The new mode is posted in "change-operation-mode"
    'operation-mode' => webpexpress_getSanitizedChooseFromSet('operation-mode', 'varied-image-responses', [
        'varied-image-responses',
        'cdn-friendly',
        'no-conversion',
        'tweaked'
    ]),
    'change-operation-mode' => webpexpress_getSanitizedChooseFromSet('change-operation-mode', 'varied-image-responses', [
        'varied-image-responses',
        'cdn-friendly',
        'no-conversion',
        'tweaked'
    ]),


    // General
    // --------
    'image-types' => intval(webpexpress_getSanitizedChooseFromSet('image-types', '3', [
        '0',
        '1',
        '2',
        '3'
    ])),
    'scope' => webpexpress_getSanitizedScope(),
    'destination-folder' => webpexpress_getSanitizedChooseFromSet('destination-folder', 'separate', [
        'separate',
        'mingled',
    ]),
    'destination-extension' => webpexpress_getSanitizedChooseFromSet('destination-extension', 'append', [
        'append',
        'set',
    ]),
    'destination-structure' => webpexpress_getSanitizedChooseFromSet('destination-structure', 'doc-root', [
        'doc-root',
        'image-roots',
    ]),
    'cache-control' => webpexpress_getSanitizedChooseFromSet('cache-control', 'no-header', [
        'no-header',
        'set',
        'custom'
    ]),
    'cache-control-max-age' => webpexpress_getSanitizedChooseFromSet('cache-control-max-age', 'one-hour', [
        'one-second',
        'one-minute',
        'one-hour',
        'one-day',
        'one-week',
        'one-month',
        'one-year',
    ]),
    'cache-control-public' => webpexpress_getSanitizedChooseFromSet('cache-control-public', 'public', [
        'public',
        'private',
    ]),
    'cache-control-custom' => webpexpress_getSanitizedCacheControlHeader('cache-control-custom'),
    'prevent-using-webps-larger-than-original' => isset($_POST['prevent-using-webps-larger-than-original']),


    // Redirection rules
    // -----------------
    'redirect-to-existing-in-htaccess' => isset($_POST['redirect-to-existing-in-htaccess']),
    'enable-redirection-to-converter' => isset($_POST['enable-redirection-to-converter']),
    'only-redirect-to-converter-for-webp-enabled-browsers' => isset($_POST['only-redirect-to-converter-for-webp-enabled-browsers']),
    'only-redirect-to-converter-on-cache-miss' => isset($_POST['only-redirect-to-converter-on-cache-miss']),
    'do-not-pass-source-in-query-string' => isset($_POST['do-not-pass-source-in-query-string']),
    'enable-redirection-to-webp-realizer' => isset($_POST['enable-redirection-to-webp-realizer']),


    // Conversion options
    // ------------------
    'metadata' => webpexpress_getSanitizedChooseFromSet('metadata', 'none', [
        'none',
        'all'
    ]),
    'jpeg-encoding' => webpexpress_getSanitizedChooseFromSet('jpeg-encoding', 'auto', [
        'lossy',
        'auto'
    ]),
    'jpeg-enable-near-lossless' => webpexpress_getSanitizedChooseFromSet('jpeg-enable-near-lossless', 'on', [
        'on',
        'off'
    ]),
    'quality-auto' => webpexpress_getSanitizedChooseFromSet('quality-auto', 'auto_on', [
        'auto_on',
        'auto_off'
    ]),
    'max-quality' => webpexpress_getSanitizedQuality('max-quality', 80),
    'jpeg-near-lossless' => webpexpress_getSanitizedQuality('jpeg-near-lossless', 60),
    'quality-specific' => webpexpress_getSanitizedQuality('quality-specific', 70),
    'quality-fallback' => webpexpress_getSanitizedQuality('quality-fallback', 70),
    'png-near-lossless' => webpexpress_getSanitizedQuality('png-near-lossless', 60),
    'png-enable-near-lossless' => webpexpress_getSanitizedChooseFromSet('png-enable-near-lossless', 'on', [
        'on',
        'off'
    ]),
    'png-quality' => webpexpress_getSanitizedQuality('png-quality', 85),
    'png-encoding' => webpexpress_getSanitizedChooseFromSet('png-encoding', 'auto', [
        'lossless',
        'auto'
    ]),
    'alpha-quality' => webpexpress_getSanitizedQuality('alpha-quality', 80),
    'convert-on-upload' => isset($_POST['convert-on-upload']),
    'enable-logging' => isset($_POST['enable-logging']),
    'converters' => webpexpress_getSanitizedConverters(),


    // Serve options
    // ---------------
    'fail' => webpexpress_getSanitizedChooseFromSet('fail', 'original', [
        'original',
        '404',
        'report'
    ]),
    'success-response' => webpexpress_getSanitizedChooseFromSet('success-response', 'original', [
        'original',
        'converted',
    ]),


    // Alter html
    // ----------
    'alter-html-enabled' => isset($_POST['alter-html-enabled']),
    'alter-html-only-for-webp-enabled-browsers' => isset($_POST['alter-html-only-for-webp-enabled-browsers']),
    'alter-html-add-picturefill-js' => isset($_POST['alter-html-add-picturefill-js']),
    'alter-html-for-webps-that-has-yet-to-exist' => isset($_POST['alter-html-for-webps-that-has-yet-to-exist']),
    'alter-html-replacement' => webpexpress_getSanitizedChooseFromSet('alter-html-replacement', 'picture', [
        'picture',
        'url'
    ]),
    'alter-html-hooks' => webpexpress_getSanitizedChooseFromSet('alter-html-hooks', 'content-hooks', [
        'content-hooks',
        'ob'
    ]),
    'alter-html-hostname-aliases' => webpexpress_getSanitizedAlterHtmlHostnameAliases(),


    // Web service
    // ------------
    'web-service-enabled' => isset($_POST['web-service-enabled']),
    'whitelist' => webpexpress_getSanitizedWhitelist(),

];

if (!Paths::canUseDocRootForRelPaths()) {
    $sanitized['destination-structure'] = 'image-roots';
}

/*
------------------------------------------------------

Lets begin working on the data.
Remember: Use $sanitized instead of $_POST

------------------------------------------------------
*/

$config = Config::loadConfigAndFix(false);  // false, because we do not need to test if quality detection is working
$oldConfig = $config;

// Set options that are available in all operation modes
$config = array_merge($config, [
    'operation-mode' => $sanitized['operation-mode'],

    'scope' => $sanitized['scope'],
    'image-types' => $sanitized['image-types'],
    'forward-query-string' => true,
]);

// Set options that are available in ALL operation modes
$config['cache-control'] = $sanitized['cache-control'];
switch ($sanitized['cache-control']) {
    case 'no-header':
        break;
    case 'set':
        $config['cache-control-max-age'] =  $sanitized['cache-control-max-age'];
        $config['cache-control-public'] = ($sanitized['cache-control-public'] == 'public');
        break;
    case 'custom':
        $config['cache-control-custom'] = $sanitized['cache-control-custom'];
        break;
}
$config['prevent-using-webps-larger-than-original'] = $sanitized['prevent-using-webps-larger-than-original'];


// Alter HTML
$config['alter-html'] = [];
$config['alter-html']['enabled'] = $sanitized['alter-html-enabled'];
if ($sanitized['alter-html-replacement'] == 'url') {
    $config['alter-html']['only-for-webp-enabled-browsers'] = $sanitized['alter-html-only-for-webp-enabled-browsers'];
} else {
    $config['alter-html']['only-for-webp-enabled-browsers'] = false;
}
if ($sanitized['alter-html-replacement'] == 'picture') {
    $config['alter-html']['alter-html-add-picturefill-js'] = $sanitized['alter-html-add-picturefill-js'];
}
if ($sanitized['operation-mode'] != 'no-conversion') {
    $config['alter-html']['only-for-webps-that-exists'] = (!$sanitized['alter-html-for-webps-that-has-yet-to-exist']);
} else {
    $config['alter-html']['only-for-webps-that-exists'] = true;
}

$config['alter-html']['replacement'] = $sanitized['alter-html-replacement'];
$config['alter-html']['hooks'] = $sanitized['alter-html-hooks'];
$config['alter-html']['hostname-aliases'] = $sanitized['alter-html-hostname-aliases'];


// Set options that are available in all operation modes, except the "no-conversion" mode
if ($sanitized['operation-mode'] != 'no-conversion') {

    $config['enable-redirection-to-webp-realizer'] = $sanitized['enable-redirection-to-webp-realizer'];

    // Metadata
    // --------
    $config['metadata'] = $sanitized['metadata'];

    // Jpeg
    // --------
    $config['jpeg-encoding'] = $sanitized['jpeg-encoding'];

    $auto = ($sanitized['quality-auto'] == 'auto_on');
    $config['quality-auto'] = $auto;
    if ($auto) {
        $config['max-quality'] = $sanitized['max-quality'];
        $config['quality-specific'] = $sanitized['quality-fallback'];
    } else {
        $config['max-quality'] = 80;
        $config['quality-specific'] = $sanitized['quality-specific'];
    }

    $config['jpeg-enable-near-lossless'] = ($sanitized['jpeg-enable-near-lossless'] == 'on');
    $config['jpeg-near-lossless'] = $sanitized['jpeg-near-lossless'];


    // Png
    // --------
    $config['png-encoding'] = $sanitized['png-encoding'];
    $config['png-quality'] = $sanitized['png-quality'];
    $config['png-enable-near-lossless'] = ($sanitized['png-enable-near-lossless'] == 'on');
    $config['png-near-lossless'] = $sanitized['png-near-lossless'];
    $config['alpha-quality'] = $sanitized['alpha-quality'];

    // Other conversion options
    $config['convert-on-upload'] = $sanitized['convert-on-upload'];
    $config['enable-logging'] = $sanitized['enable-logging'];


    // Web Service
    // -------------

    $config['web-service'] = [
        'enabled' => $sanitized['web-service-enabled'],
        'whitelist' => $sanitized['whitelist']
    ];

    // Set existing api keys in web service (we removed them from the json array, for security purposes)
    if (isset($oldConfig['web-service']['whitelist'])) {
        foreach ($oldConfig['web-service']['whitelist'] as $existingWhitelistEntry) {
            foreach ($config['web-service']['whitelist'] as &$whitelistEntry) {
                if ($whitelistEntry['uid'] == $existingWhitelistEntry['uid']) {
                    $whitelistEntry['api-key'] = $existingWhitelistEntry['api-key'];
                }
            }
        }
    }

    // Set changed api keys
    foreach ($config['web-service']['whitelist'] as &$whitelistEntry) {
        if (!empty($whitelistEntry['new-api-key'])) {
            $whitelistEntry['api-key'] = $whitelistEntry['new-api-key'];
            unset($whitelistEntry['new-api-key']);
        }
    }

    // Converters
    // -------------

    $config['converters'] = $sanitized['converters'];

    // remove converter ids
    foreach ($config['converters'] as &$converter) {
        unset ($converter['id']);
    }

    // Get existing wpc api key from old config
    $existingWpcApiKey = '';
    foreach ($oldConfig['converters'] as &$converter) {
        if (isset($converter['converter']) && ($converter['converter'] == 'wpc')) {
            if (isset($converter['options']['api-key'])) {
                $existingWpcApiKey = $converter['options']['api-key'];
            }
        }
    }

    // Set wpc api key in new config
    // - either to the existing, or to a new
    foreach ($config['converters'] as &$converter) {
        if (isset($converter['converter']) && ($converter['converter'] == 'wpc')) {
            unset($converter['options']['_api-key-non-empty']);
            if (isset($converter['options']['new-api-key'])) {
                $converter['options']['api-key'] = $converter['options']['new-api-key'];
                unset($converter['options']['new-api-key']);
            } else {
                $converter['options']['api-key'] = $existingWpcApiKey;
            }
        }
    }
}

$config['destination-structure'] = $sanitized['destination-structure'];

switch ($sanitized['operation-mode']) {
    case 'varied-image-responses':
        $config = array_merge($config, [
            'redirect-to-existing-in-htaccess' => $sanitized['redirect-to-existing-in-htaccess'],
            'destination-folder' => $sanitized['destination-folder'],
            'destination-extension' => (($sanitized['destination-folder'] == 'mingled') ? $sanitized['destination-extension'] : 'append'),
            'enable-redirection-to-converter' => $sanitized['enable-redirection-to-converter'],
        ]);
        break;
    case 'cdn-friendly':
        $config = array_merge($config, [
            'destination-folder' => $sanitized['destination-folder'],
            'destination-extension' => (($sanitized['destination-folder'] == 'mingled') ? $sanitized['destination-extension'] : 'append'),
            'enable-redirection-to-converter' => $sanitized['enable-redirection-to-converter'],  // PS: its called "autoconvert" in this mode
        ]);
        break;
    case 'no-conversion':
        $config = array_merge($config, [
            'redirect-to-existing-in-htaccess' => $sanitized['redirect-to-existing-in-htaccess'],
            'destination-extension' => $sanitized['destination-extension'],
        ]);
        break;
    case 'tweaked':
        $config = array_merge($config, [
            'enable-redirection-to-converter' => $sanitized['enable-redirection-to-converter'],
            'only-redirect-to-converter-for-webp-enabled-browsers' => $sanitized['only-redirect-to-converter-for-webp-enabled-browsers'],
            'only-redirect-to-converter-on-cache-miss' => $sanitized['only-redirect-to-converter-on-cache-miss'],
            'do-not-pass-source-in-query-string' => $sanitized['do-not-pass-source-in-query-string'],
            'redirect-to-existing-in-htaccess' => $sanitized['redirect-to-existing-in-htaccess'],
            'destination-folder' => $sanitized['destination-folder'],
            'destination-extension' => (($sanitized['destination-folder'] == 'mingled') ? $sanitized['destination-extension'] : 'append'),
            'fail' => $sanitized['fail'],
            'success-response' => $sanitized['success-response'],
        ]);
        break;
}

if ($sanitized['operation-mode'] != $sanitized['change-operation-mode']) {

    // Operation mode changed!
    $config['operation-mode'] = $sanitized['change-operation-mode'];
    $config = Config::applyOperationMode($config);

    if ($config['operation-mode'] == 'varied-image-responses') {
        // changing to "varied image responses" mode should enable
        // the redirect-to-existing-in-htaccess option
        $config['redirect-to-existing-in-htaccess'] = true;
    }

    if ($config['operation-mode'] == 'no-conversion') {
        // No conversion probably means that there are webps in the system not generated by
        // webp express. Schedule a task to mark those that are bigger than originals
        wp_schedule_single_event(time() + 30, 'webp_express_task_bulk_update_dummy_files');
    }
}

// If we are going to save .htaccess, run and store capability tests first
// (we should only store results when .htaccess is updated as well)
if ($sanitized['force'] || HTAccessRules::doesRewriteRulesNeedUpdate($config)) {
    Config::runAndStoreCapabilityTests($config);
}


$config['environment-when-config-was-saved'] = [
    'doc-root-available' => PathHelper::isDocRootAvailable(),
    'doc-root-resolvable' => PathHelper::isDocRootAvailableAndResolvable(),
    'doc-root-usable-for-structuring' => Paths::canUseDocRootForRelPaths(),
    'image-roots' => Paths::getImageRootsDef(),
    'document-root' => null,
];

if (PathHelper::isDocRootAvailable()) {
    $config['document-root'] = $_SERVER['DOCUMENT_ROOT'];
}

// SAVE!
// -----
$result = Config::saveConfigurationAndHTAccess($config, $sanitized['force']);

// Handle results
// ---------------

if (!$result['saved-both-config']) {
    if (!$result['saved-main-config']) {
        Messenger::addMessage(
            'error',
            'Failed saving configuration file.<br>' .
                'Current file permissions are preventing WebP Express to save configuration to: "' . Paths::getConfigFileName() . '"'
        );
    } else {
        Messenger::addMessage(
            'error',
            'Failed saving options file. Check file permissions<br>' .
                'Tried to save to: "' . Paths::getWodOptionsFileName() . '"'
        );

    }
} else {
    $changeFolder = ($config['destination-folder'] != $oldConfig['destination-folder']);
    $changeExtension = ($config['destination-extension'] != $oldConfig['destination-extension']);
    $changeStructure = ($config['destination-structure'] != $oldConfig['destination-structure']);

    if ($changeFolder || $changeExtension || $changeStructure) {

        $relocate = $changeFolder || $changeStructure;
        $rename = $changeExtension;

        $actionPastTense = '';
        if ($rename && $relocate) {
            $actionPastTense = 'relocated and renamed';
            $actionPresentTense = 'relocate and rename';
        } else {
            if ($rename) {
                $actionPastTense = 'renamed';
                $actionPresentTense = 'rename';
            } else {
                $actionPastTense = 'relocated';
                $actionPresentTense = 'relocate';
            }
        }

        list($numFilesMoved, $numFilesFailedMoving) = CacheMover::move($config, $oldConfig);
        if ($numFilesFailedMoving == 0) {
            if ($numFilesMoved == 0) {
                Messenger::addMessage(
                    'notice',
                    'No cached webp files needed to be ' . $actionPastTense
                );

            } else {
                Messenger::addMessage(
                    'success',
                    'The webp files was ' . $actionPastTense . ' (' . $actionPastTense . ' ' . $numFilesMoved . ' images)'
                );
            }
        } else {
            if ($numFilesMoved == 0) {
                Messenger::addMessage(
                    'warning',
                    'No webp files could not be ' . $actionPastTense . ' (failed to ' . $actionPresentTense . ' ' . $numFilesFailedMoving . ' images)'
                );
            } else {
                Messenger::addMessage(
                    'warning',
                    'Some webp files could not be ' . $actionPastTense . ' (failed to ' . $actionPresentTense . ' ' . $numFilesFailedMoving . ' images, but successfully ' . $actionPastTense . ' ' . $numFilesMoved . ' images)'
                );

            }
        }
    }

    if (!$result['rules-needed-update']) {
        Messenger::addMessage(
            'success',
            'Configuration saved. Rewrite rules did not need to be updated. ' . HTAccess::testLinks($config)
        );
    } else {
        Messenger::addMessage(
            'success',
            'Configuration was saved.'
        );
        HTAccess::showSaveRulesMessages($result['htaccess-result']);
    }
}

wp_redirect(Paths::getSettingsUrl());

exit();
