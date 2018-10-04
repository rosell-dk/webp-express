<?php

include_once __DIR__ . '/classes/Config.php';
use \WebPExpress\Config;

include_once __DIR__ . '/classes/HTAccess.php';
use \WebPExpress\HTAccess;

include_once __DIR__ . '/classes/Messenger.php';
use \WebPExpress\Messenger;

include_once __DIR__ . '/classes/Actions.php';
use \WebPExpress\Actions;

include_once __DIR__ . '/classes/Paths.php';
use \WebPExpress\Paths;

include_once __DIR__ . '/classes/PlatformInfo.php';
use \WebPExpress\PlatformInfo;

include_once __DIR__ . '/classes/State.php';
use \WebPExpress\State;


// The plugin has been reactivated.
// We must regenerate the .htaccess rules.
// (config dir and options and of course still there, no need to do anything about that)

//    Messenger::addMessage('error', 'You are on Microsof IIS server. The plugin does not work on IIS (yet)');
//Actions::procastinate('deactivate');

$config = Config::loadConfig();
if ($config === false) {
    Messenger::addMessage(
        'error',
        'The config file seems to have gone missing. You will need to reconfigure WebP Express ' .
            '<a href="' . Paths::getSettingsUrl() . '">(here)</a>.'
    );
} else {
    $rulesResult = HTAccess::saveRules($config);
    /*
    'mainResult'        // 'index', 'wp-content' or 'failed'
    'minRequired'       // 'index' or 'wp-content'
    'pluginToo'         // 'yes', 'no' or 'depends'
    'pluginFailed'      // true if failed to write to plugin folder (it only tries that, if pluginToo == 'yes')
    'pluginFailedBadly' // true if plugin failed AND it seems we have rewrite rules there
    'overidingRulesInWpContentWarning'  // true if main result is 'index' but we cannot remove those in wp-content
    'rules'             // the rules that were generated
    */
    $mainResult = $rulesResult['mainResult'];
    $rules = $rulesResult['rules'];

    if ($mainResult != 'failed') {
        Messenger::addMessage(
            'success',
            'WebP Express re-activated successfully.<br>' .
                'The image redirections are in effect again.<br><br>' .
                'Just a quick reminder: If you at some point change the upload directory or move Wordpress, the <i>.htaccess</i> will need to be regenerated.<br>' .
                'You do that by re-saving the settings ' .
                '<a href="' . Paths::getSettingsUrl() . '">(here)</a>'
        );
    } else {
        Messenger::addMessage(
            'warning',
            'WebP Express could not regenerate the rewrite rules<br>' .
                'You need to change some permissions. Head to the ' .
                '<a href="' . Paths::getSettingsUrl() . '">settings page</a> ' .
                'and try to save the settings there (it will provide more information about the problem)'
        );

    }

}
