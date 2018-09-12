<?php

include_once __DIR__ . '/../classes/Config.php';
use \WebPExpress\Config;

include_once __DIR__ . '/../classes/Messenger.php';
use \WebPExpress\Messenger;

include_once __DIR__ . '/../classes/Paths.php';
use \WebPExpress\Paths;


// https://premium.wpmudev.org/blog/handling-form-submissions/
// checkout https://codex.wordpress.org/Function_Reference/sanitize_meta

$config = [
    'fail' => sanitize_text_field($_POST['fail']),
    'max-quality' => sanitize_text_field($_POST['max-quality']),
    'image-types' => sanitize_text_field($_POST['image-types']),
    'converters' => json_decode(wp_unslash($_POST['converters']), true), // holy moly! - https://stackoverflow.com/questions/2496455/why-are-post-variables-getting-escaped-in-php
    'forward-query-string' => true
];

// remove id's
foreach ($config['converters'] as &$converter) {
    unset ($converter['id']);
}

$rewriteRulesNeedsUpdate = Config::doesRewriteRulesNeedUpdate($config);
//$htaccessExists = Config::doesHTAccessExists();
$rules = Config::generateHTAccessRulesFromConfigObj($config);
$isConfigFileThere = Config::isConfigFileThere();

/*
if (!$htaccessExists) {
    if ($isConfigFileThere) {
        if ($rewriteRulesNeedsUpdate) {
            Messenger::addMessage('info',
                'The rewrite rules needs to be updated. However, as you do not have an <i>.htaccess</i> file, you pressumably need to insert the rules in your VirtualHost manually. ' .
                'You must insert/update the rules to the following:' .
                '<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
            );
        } else {
            Messenger::addMessage('info', 'The rewrite rules does not need to be updated.');
        }
    } else {
        Messenger::addMessage('info',
            'You must insert the following rules in your VirtualHost manually (you do not have an <i>.htaccess</i> file in your root)<br>' .
            'Insert the following:<br>' .
            '<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
        );
    }
}
*/

function webpexpress_submit_saveRulesToDir($dir, $rules) {
    $createIfMissing = true;
    Config::saveHTAccessRulesToFile($dir . '/.htaccess', $rules, $createIfMissing);
}

function webpexpress_submit_saveRules($rules, $testLinks) {

    $indexDir = Paths::getIndexDirAbs();
    $homeDir = Paths::getHomeDirAbs();
    $wpContentDir = Paths::getWPContentDirAbs();
    $pluginDir = Paths::getPluginDirAbs();

    $writeToPluginsDirToo = false;
    $showSuccess = true;

    $result = Config::saveHTAccessRulesToFirstWritableHTAccessDir([$wpContentDir, $indexDir, $homeDir], $rules);

    if ($result == false) {
        $showSuccess = false;
        Messenger::addMessage(
            'warning',
            'Configuration saved, but the <i>.htaccess</i> rules could not be saved. Please grant access to either your <i>wp-content</i> dir, ' .
                'or your main <i>.htaccess</i> file. ' .
                '- or, alternatively insert the following rules directly in your Apache configuration:' .
                '<pre>' . htmlentities(print_r($rules, true)) . '</pre>' .
                $testLinks
        );
    } else {
        if ($result == $wpContentDir) {
            $writeToPluginsDirToo = Paths::isPluginDirMovedOutOfWpContent();
        } else {
            /*
            TODO: It is serious, if there are rules in wp-content that can no longer be removed
            We should try to read that file to see if there is a problem.
            */
            $showSuccess = false;
            Messenger::addMessage('success', 'Configuration saved.');
            Messenger::addMessage(
                'warning',
                '<i>.htaccess</i> rules were written to your main <i>.htaccess</i>. ' .
                    'However, consider to let us write into you wp-content dir instead.' .
                    $testLinks
            );

            $writeToPluginsDirToo = Paths::isPluginDirMovedOutOfAbsPath();
        }
    }
    if ($writeToPluginsDirToo) {
        if (!Config::saveHTAccessRulesToFile($pluginDir . '/.htaccess', $rules, true)) {
            $showSuccess = false;
            Messenger::addMessage('success', 'Configuration saved.');
            Messenger::addMessage(
                'warning',
                '<i>.htaccess</i> rules could not be written into your plugins folder. ' .
                    'Images stored in your plugins will not be converted to webp (or, if <i>WebP Express</i> has rewrite rules there already, they did not get updated)'
            );
        }
    }
    if ($showSuccess) {
        Messenger::addMessage(
            'success',
            'Configuration saved and rewrite rules were updated (they are placed in your <i>wp-content</i> dir)' .
                ($writeToPluginsDirToo ? '. Also updated rewrite rules in your plugins dir.' : '.') .
                $testLinks
        );
    }
    Messenger::addMessage(
        'info',
        'Rules:<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
    );
}

$testLinks = '';
if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false )) {
    if ($config['image-types'] != 0) {
        $webpExpressRoot = Paths::getPluginUrlPath();

        $testLinks = '<br>' .
            '<a href="/' . $webpExpressRoot . '/test/test.jpg?debug&time=' . time() . '" target="_blank">Convert test image (show debug)</a><br>' .
            '<a href="/' . $webpExpressRoot . '/test/test.jpg?' . time() . '" target="_blank">Convert test image</a><br>';
    }
}

$showSuccess = false;
if (Config::saveConfigurationFile($config)) {
    $options = Config::generateWodOptionsFromConfigObj($config);
    if (Config::saveWodOptionsFile($options)) {

        if ($rewriteRulesNeedsUpdate) {
            webpexpress_submit_saveRules($rules, $testLinks);
            //Messenger::addMessage('success', 'Configuration saved.');
        } else {
            Messenger::addMessage('success', 'Configuration saved. Rewrite rules did not need to be updated. ' . $testLinks);
        }


    } else {
        Messenger::addMessage('error', 'Failed saving options file. Check file permissiPathsons<br>Tried to save to: "' . Paths::getWodOptionsFileName() . '"');
    }
} else {
    Messenger::addMessage(
        'error',
        'Failed saving configuration file.<br>Current file permissions are preventing WebP Express to save configuration to: "' . Paths::getConfigFileName() . '"'
    );
}

wp_redirect( $_SERVER['HTTP_REFERER']);

exit();
