<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Messenger;
use \WebPExpress\HTAccess;
use \WebPExpress\Paths;
use \WebPExpress\Option;

/**
 *  Fix records - if possible
 */
function webpexpress_migrate6_fixHtaccessRecordsForDir($dirId) {
    $haveRules = HTAccess::haveWeRulesInThisHTAccess(Paths::getAbsDirById($dirId) . '/.htaccess');

    // PS: $haveRules may be null, meaning "maybe"
    if ($haveRules === true) {
        HTAccess::addToActiveHTAccessDirsArray($dirId);
    }
    if ($haveRules === false) {
        HTAccess::removeFromActiveHTAccessDirsArray($dirId);
    }
}

function webpexpress_migrate6() {

    // Regenerate .htaccess file if placed in root (so rewrites does not apply in wp-admin area)
    if (HTAccess::isInActiveHTAccessDirsArray('index')) {
        if (Config::isConfigFileThere()) {
            $config = Config::loadConfigAndFix(false);  // false, because we do not need to test if quality detection is working

            $rules = HTAccess::generateHTAccessRulesFromConfigObj($config, 'index');
            $success = (HTAccess::saveHTAccessRulesToFile(Paths::getIndexDirAbs() . '/.htaccess', $rules, true));

            if ($success) {
                Messenger::addMessage(
                    'info',
                    'Fixed .htaccess rules in root (the old rules were also applying to wp-admin folder. In some cases this resulted in problems with the media library).'
                );
            } else {
                Messenger::addMessage(
                    'warning',
                    'Tried to fix .htaccess rules in root folder (the old rules applied to wp-admin, which in some cases resulted in problems with media library). However, the attempt failed.'
                );
            }
        }
    }

    // The records about which .htaccess files that contains rules were not correct.
    // Correct them if possible (haveWeRulesInThisHTAccess() may return null, if we cannot determine)
    // https://github.com/rosell-dk/webp-express/issues/169

    $dirsToFix = [
        'index',
        'home',
        'wp-content',
        'plugins',
        'uploads'
    ];
    foreach ($dirsToFix as $dirId) {
        webpexpress_migrate6_fixHtaccessRecordsForDir($dirId);
    }

    Option::updateOption('webp-express-migration-version', '6');
}

webpexpress_migrate6();
