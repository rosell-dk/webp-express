<?php

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

$config = [
    'cache-control' => sanitize_text_field($_POST['cache-control']),
    'cache-control-custom' => sanitize_text_field($_POST['cache-control-custom']),
    'converters' => json_decode(wp_unslash($_POST['converters']), true), // holy moly! - https://stackoverflow.com/questions/2496455/why-are-post-variables-getting-escaped-in-php
    'fail' => sanitize_text_field($_POST['fail']),
    'forward-query-string' => true,
    'image-types' => sanitize_text_field($_POST['image-types']),
    'max-quality' => sanitize_text_field($_POST['max-quality']),
    'metadata' => sanitize_text_field($_POST['metadata']),
];

// remove id's
foreach ($config['converters'] as &$converter) {
    unset ($converter['id']);
}

$result = Config::saveConfigurationAndHTAccess($config, isset($_POST['force']));

/*
Messenger::addMessage(
    'info',
    isset($_POST['force']) ? 'force' : 'no-force' .
        (HTAccess::doesRewriteRulesNeedUpdate($config) ? 'need' : 'no need')
);*/

/*
Messenger::addMessage(
    'info',
    '<pre>' . htmlentities(print_r($result, true)) . '</pre>'
);*/

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
