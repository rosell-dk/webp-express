<?php

include_once __DIR__ . '/../classes/CacheMover.php';
use \WebPExpress\CacheMover;

include_once __DIR__ . '/../classes/Config.php';
use \WebPExpress\Config;

include_once __DIR__ . '/../classes/HTAccess.php';
use \WebPExpress\HTAccess;

include_once __DIR__ . '/../classes/Messenger.php';
use \WebPExpress\Messenger;

include_once __DIR__ . '/../classes/Paths.php';
use \WebPExpress\Paths;

// https://premium.wpmudev.org/blog/handling-form-submissions/
// checkout https://codex.wordpress.org/Function_Reference/sanitize_meta

/* We want an integer value between 0-100. We round "77.5" to 78. */
function webp_express_sanitize_quality_field($text) {
    $text = str_replace(',', '.', $text);
    $q = floatval(sanitize_text_field($text));
    $q = round($q);
    return max(0, min($q, 100));
}

$config = Config::loadConfigAndFix();
$oldConfig = $config;

// Set options that are available in all operation modes
$config = array_merge($config, [
    'operation-mode' => $_POST['operation-mode'],

    // redirection rules
    'image-types' => sanitize_text_field($_POST['image-types']),
    'forward-query-string' => true,

    // serve options
    'cache-control' => sanitize_text_field($_POST['cache-control']),
    'cache-control-custom' => sanitize_text_field($_POST['cache-control-custom']),
]);

$cacheControl = sanitize_text_field($_POST['cache-control']);
switch ($cacheControl) {
    case 'no-header':
        break;
    case 'set':
        $config['cache-control-max-age'] =  sanitize_text_field($_POST['cache-control-max-age']);
        $config['cache-control-public'] =  (sanitize_text_field($_POST['cache-control-public']) == 'public');
        break;
    case 'custom':
        $config['cache-control-custom'] = sanitize_text_field($_POST['cache-control-custom']);
        break;
}

// Set options that are available in all operation modes, except the "just-redirect" mode
if ($_POST['operation-mode'] != 'just-redirect') {

    // Metadata
    // --------
    $config['metadata'] = sanitize_text_field($_POST['metadata']);

    // Quality
    // --------
    $auto = (isset($_POST['quality-auto']) && $_POST['quality-auto'] == 'auto_on');
    $config['quality-auto'] = $auto;

    if ($auto) {
        $config['max-quality'] = webp_express_sanitize_quality_field($_POST['max-quality']);
        $config['quality-specific'] = 70;
    } else {
        $config['max-quality'] = 80;
        $config['quality-specific'] = webp_express_sanitize_quality_field($_POST['quality-specific']);
    }

    // Web Service
    // -------------

    $config['web-service'] = [
        'enabled' => isset($_POST['web-service-enabled']),
        'whitelist' => json_decode(wp_unslash($_POST['whitelist']), true)
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

    // Set new api keys in web service
    foreach ($config['web-service']['whitelist'] as &$whitelistEntry) {
        if (!empty($whitelistEntry['new-api-key'])) {
            $whitelistEntry['api-key'] = $whitelistEntry['new-api-key'];
            unset($whitelistEntry['new-api-key']);
        }
    }

    // Converters
    // -------------

    $config['converters'] = json_decode(wp_unslash($_POST['converters']), true); // holy moly! - https://stackoverflow.com/questions/2496455/why-are-post-variables-getting-escaped-in-php

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

switch ($_POST['operation-mode']) {
    case 'standard':
        $config = array_merge($config, [
            'redirect-to-existing-in-htaccess' => isset($_POST['redirect-to-existing-in-htaccess']),
        ]);
        break;
    case 'just-convert':
        $config = array_merge($config, [
            'destination-extension' => $_POST['destination-extension'],
            'enable-redirection-to-converter' => isset($_POST['enable-redirection-to-converter']),  // PS: its called "autoconvert" in this mode
        ]);
        break;
    case 'tweaked':
        $config = array_merge($config, [
            'enable-redirection-to-converter' => isset($_POST['enable-redirection-to-converter']),
            'only-redirect-to-converter-for-webp-enabled-browsers' => isset($_POST['only-redirect-to-converter-for-webp-enabled-browsers']),
            'only-redirect-to-converter-on-cache-miss' => isset($_POST['only-redirect-to-converter-on-cache-miss']),
            'do-not-pass-source-in-query-string' => isset($_POST['do-not-pass-source-in-query-string']),
            'redirect-to-existing-in-htaccess' => isset($_POST['redirect-to-existing-in-htaccess']),
            'destination-folder' => $_POST['destination-folder'],
            'destination-extension' => (($_POST['destination-folder'] == 'mingled') ? $_POST['destination-extension'] : 'append'),
            'fail' => sanitize_text_field($_POST['fail']),
            'success-response' => sanitize_text_field($_POST['success-response']),
        ]);
        break;
}

//echo '<pre>' . print_r($_POST, true) . '</pre>'; exit;
if ($_POST['operation-mode'] != $_POST['change-operation-mode']) {
    $config['operation-mode'] = $_POST['change-operation-mode'];
    $config = Config::applyOperationMode($config);
}

// SAVE!
// -----
$result = Config::saveConfigurationAndHTAccess($config, isset($_POST['force']));


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
    if (($config['destination-folder'] != $oldConfig['destination-folder']) || ($config['destination-extension'] != $oldConfig['destination-extension'])) {
        $whatShouldIt = '';
        if ($config['destination-folder'] == $oldConfig['destination-folder']) {
            $whatShouldIt = 'renamed';
            $whatShouldIt2 = 'rename';
        } else {
            if ($config['destination-extension'] == $oldConfig['destination-extension']) {
                $whatShouldIt = 'relocated';
                $whatShouldIt2 = 'relocate';
            } else {
                $whatShouldIt = 'relocated and renamed';
                $whatShouldIt2 = 'relocate and rename';
            }
        }

        list($numFilesMoved, $numFilesFailedMoving) = CacheMover::move($config, $oldConfig);
        if ($numFilesFailedMoving == 0) {
            if ($numFilesMoved == 0) {
                Messenger::addMessage(
                    'notice',
                    'No cached webp files needed to be ' . $whatShouldIt
                );

            } else {
                Messenger::addMessage(
                    'success',
                    'The webp files was ' . $whatShouldIt . ' (' . $whatShouldIt . ' ' . $numFilesMoved . ' images)'
                );
            }
        } else {
            if ($numFilesMoved == 0) {
                Messenger::addMessage(
                    'warning',
                    'No webp files could not be ' . $whatShouldIt . ' (failed to ' . $whatShouldIt2 . ' ' . $numFilesFailedMoving . ' images)'
                );
            } else {
                Messenger::addMessage(
                    'warning',
                    'Some webp files could not be ' . $whatShouldIt . ' (failed to ' . $whatShouldIt2 . ' ' . $numFilesFailedMoving . ' images, but successfully ' . $whatShouldIt . ' ' . $numFilesMoved . ' images)'
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
        $rulesResult = $result['htaccess-result'];
        /*
        'mainResult'        // 'index', 'wp-content' or 'failed'
        'minRequired'       // 'index' or 'wp-content'
        'pluginToo'         // 'yes', 'no' or 'depends'
        'uploadToo'         // 'yes', 'no' or 'depends'
        'overidingRulesInWpContentWarning'  // true if main result is 'index' but we cannot remove those in wp-content
        'rules'             // the rules that were generated
        'pluginFailed'      // true if failed to write to plugin folder (it only tries that, if pluginToo == 'yes')
        'pluginFailedBadly' // true if plugin failed AND it seems we have rewrite rules there
        'uploadFailed'      // true if failed to write to plugin folder (it only tries that, if pluginToo == 'yes')
        'uploadFailedBadly' // true if plugin failed AND it seems we have rewrite rules there
        */
        $mainResult = $rulesResult['mainResult'];
        $rules = $rulesResult['rules'];

        if ($mainResult == 'failed') {
            if ($rulesResult['minRequired'] == 'wp-content') {
                Messenger::addMessage(
                    'error',
                    'Configuration saved, but failed saving rewrite rules. ' .
                        'Please grant us write access to your <i>wp-content</i> dir (we need that, because you have moved <i>wp-content</i> out of the Wordpress dir) ' .
                        '- or, alternatively insert the following rules directly in that <i>.htaccess</i> file, or your Apache configuration:' .
                        '<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
                );

            } else {
                Messenger::addMessage(
                    'error',
                    'Configuration saved, but failed saving rewrite rules. ' .
                        'Please grant us write access to either write rules to an <i>.htaccess</i> in your <i>wp-content</i> dir (preferably), ' .
                        'or your main <i>.htaccess</i> file. ' .
                        '- or, alternatively insert the following rules directly in that <i>.htaccess</i> file, or your Apache configuration:' .
                        '<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
                );
            }
        } else {
            $savedToPluginsToo = (($rulesResult['pluginToo'] == 'yes') && !($rulesResult['pluginFailed']));
            $savedToUploadsToo = (($rulesResult['uploadToo'] == 'yes') && !($rulesResult['uploadFailed']));

            Messenger::addMessage(
                'success',
                'Configuration saved. Rewrite rules were saved to your <i>.htaccess</i> in your <i>' . $mainResult . '</i> folder' .
                    (Paths::isWPContentDirMoved() ? ' (which you moved, btw)' : '') .
                    ($savedToPluginsToo ? ' as well as in your <i>plugins</i> folder' : '') .
                    ((Paths::isWPContentDirMoved() && $savedToPluginsToo) ? ' (you moved that as well!)' : '.') .
                    ($savedToUploadsToo ? ' as well as in your <i>uploads</i> folder' : '') .
                    ((Paths::isWPContentDirMoved() && $savedToUploadsToo) ? ' (you moved that as well!)' : '.') .
                    HTAccess::testLinks($config)
            );
        }
        if ($rulesResult['mainResult'] == 'index') {
            if ($rulesResult['overidingRulesInWpContentWarning']) {
                Messenger::addMessage(
                    'warning',
                    'We have rewrite rules in the <i>wp-content</i> folder, which we cannot remove. ' .
                        'These are overriding those just saved. ' .
                        'Please change file permissions or remove the rules from the <i>.htaccess</i> file manually'
                );
            } else {
                Messenger::addMessage(
                    'info',
                    'The rewrite rules are currently stored in your root. ' .
                        'WebP Express would prefer to store them in your wp-content folder, ' .
                        'but your current file permissions does not allow that.'
                );
            }
        }
        if ($rulesResult['pluginFailed']) {
            if ($rulesResult['pluginFailedBadly']) {
                Messenger::addMessage(
                    'warning',
                    'The <i>.htaccess</i> rules in your plugins folder could not be updated (no write access). ' .
                        'This is not so good, because we have rules there already...' .
                        'You should update them. Here they are: ' .
                        '<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
                );
            } else {
                Messenger::addMessage(
                    'info',
                    '<i>.htaccess</i> rules could not be written into your plugins folder. ' .
                        'Images stored in your plugins will not be converted to webp'
                );
            }
        }
        if ($rulesResult['uploadFailed']) {
            if ($rulesResult['uploadFailedBadly']) {
                Messenger::addMessage(
                    'error',
                    'The <i>.htaccess</i> rules in your uploads folder could not be updated (no write access). ' .
                        'This is not so good, because we have rules there already...' .
                        'You should update them. Here they are: ' .
                        '<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
                );
            } else {
                Messenger::addMessage(
                    'warning',
                    '<i>.htaccess</i> rules could not be written into your uploads folder (this is needed, because you have moved it outside your <i>wp-content</i> folder). ' .
                        'Please grant write permmissions to you uploads folder. Otherwise uploaded mages will not be converted to webp'
                );
            }
        }
    }
}

wp_redirect( $_SERVER['HTTP_REFERER']);

exit();
