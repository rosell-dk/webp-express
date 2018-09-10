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
$htaccessExists = Config::doesHTAccessExists();
$rules = Config::generateHTAccessRulesFromConfigObj($config);
$isConfigFileThere = Config::isConfigFileThere();

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

$showSuccess = false;
if (Config::saveConfigurationFile($config)) {
    $options = Config::generateWodOptionsFromConfigObj($config);
    if (Config::saveWodOptionsFile($options)) {
        if ($rewriteRulesNeedsUpdate) {
            if ($htaccessExists) {
                if (Config::saveHTAccessRules($rules)) {
                    $showSuccess = true;

                    if ($isConfigFileThere) {
                        Messenger::addMessage(
                            'success',
                            '<i>.htaccess</i> rules updated ok. The rules are now:<br>' .
                            '<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
                        );
                    } else {
                        Messenger::addMessage(
                            'success',
                            'Inserted the following magic in your <i>.htaccess</i>:<br>' .
                            '<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
                        );
                    }
                } else {
                    Messenger::addMessage('error',
                        'Failed saving rewrite rules to your <i>.htaccess</i>.<br>' .
                        'Change the file permissions and save settings again. Or, alternatively, paste the following into your <i>.htaccess</i>:' .
                        '<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
                    );
                }
            }
        } else {
            $showSuccess = true;
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

if ($showSuccess) {
    Messenger::addMessage('success', 'Configuration saved');

    if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false )) {
        $webpExpressRoot = Paths::getPluginUrlPath();

        if ($config['image-types'] != 0) {
            Messenger::addMessage(
                'info',
                'Your browser supports webp... So you can test if everything works (including the redirect magic) - using these links:<br>' .
                    '<a href="/' . $webpExpressRoot . '/test/test.jpg" target="_blank">Convert test image</a><br>' .
                    '<a href="/' . $webpExpressRoot . '/test/test.jpg?debug" target="_blank">Convert test image (show debug)</a><br>'
            );
        }
    }
}

wp_redirect( $_SERVER['HTTP_REFERER']);

exit();
