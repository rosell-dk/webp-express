<?php

use \WebPExpress\Paths;
use \WebPExpress\HTAccess;
use \WebPExpress\Config;
use \WebPExpress\State;
use \WebPExpress\Messenger;
use \WebPExpress\PlatformInfo;
use \WebPExpress\FileHelper;

/*
$indexDir = Paths::getIndexDirAbs();
$homeDir = Paths::getHomeDirAbs();
$wpContentDir = Paths::getWPContentDirAbs();
$pluginDir = Paths::getPluginDirAbs();
*/
//State::setState('htaccess-rules-saved-to-wp-content', false);
//echo (State::getState('htaccess-rules-saved-to-wp-content', false) ? 'yes' : 'no');

//echo (Config::haveWeRulesInThisHTAccess(Paths::getWPContentDirAbs().'/.htaccess') ? 'yes' : 'no');

if ((!State::getState('configured', false))) {
    include __DIR__ . "/page-welcome.php";
}

//echo (isset($_SERVER['HTACCESS']) ? 'set' : 'not set');

if (PlatformInfo::definitelyNotGotModRewrite()) {
    Messenger::printMessage(
        'error',
        "Rewriting isn't enabled on your server. WebP Express cannot work without it. Tell your host or system administrator to enable the 'mod_rewrite' module. If you are on a shared host, chances are that mod_rewrite can be turned on in your control panel."
    );
}
/*
if (Config::isConfigFileThereAndOk() ) { // && PlatformInfo::definitelyGotModEnv()
    if (!isset($_SERVER['HTACCESS'])) {
        Messenger::printMessage(
            'warning',
            "Using rewrite rules in <i>.htaccess</i> files seems to be disabled " .
                "(The <i>AllowOverride</i> directive is probably set to <i>None</i>. " .
                "It needs to be set to <i>All</i>, or at least <i>FileInfo</i> to allow rewrite rules in <i>.htaccess</i> files.)<br>" .
                "Disabled <i>.htaccess</i> files is actually a good thing, both performance-wise and security-wise. <br> " .
                "But it means you will have to insert the following rules into your apache configuration manually:" .
                "<pre>" . htmlentities(print_r(Config::generateHTAccessRulesFromConfigFile(), true)) . "</pre>"
        );
    }
}*/
if (!Paths::createContentDirIfMissing()) {
    Messenger::printMessage(
        'error',
        'WebP Express needs to create a directory "webp-express" under your wp-content folder, but does not have permission to do so.<br>' .
            'Please create the folder manually, or change the file permissions of your wp-content folder.'
    );
} else {
    if (!Paths::createConfigDirIfMissing()) {
        Messenger::printMessage(
            'error',
            'WebP Express needs to create a directory "webp-express/config" under your wp-content folder, but does not have permission to do so.<br>' .
                'Please create the folder manually, or change the file permissions.'
        );
    }

    if (!Paths::createCacheDirIfMissing()) {
        Messenger::printMessage(
            'error',
            'WebP Express needs to create a directory "webp-express/webp-images" under your wp-content folder, but does not have permission to do so.<br>' .
                'Please create the folder manually, or change the file permissions.'
        );
    }
}

if (Config::isConfigFileThere()) {
    if (!Config::isConfigFileThereAndOk()) {
        Messenger::printMessage(
            'warning',
            'Warning: The configuration file is not ok! (cant be read, or not valid json).<br>' .
                'file: "' . Paths::getConfigFileName() . '"'
        );
    } else {
        if (HTAccess::arePathsUsedInHTAccessOutdated()) {
            Messenger::printMessage(
                'warning',
                'Warning: Wordpress paths have changed since the last time the Rewrite Rules was generated. The rules needs updating! (click <i>Save settings</i> to do so)<br>'
            );
        }
    }
}
