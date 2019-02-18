<?php

namespace WebPExpress;


include_once __DIR__ . '/../classes/Config.php';
use \WebPExpress\Config;

include_once __DIR__ . '/../classes/Messenger.php';
use \WebPExpress\Messenger;

include_once __DIR__ . '/../classes/HTAccess.php';
use \WebPExpress\HTAccess;

include_once __DIR__ . '/../classes/Paths.php';
use \WebPExpress\Paths;

function webpexpress_migrate6() {

    // Regenerate .htaccess file if placed in root (so rewrites does not apply in wp-admin area)
    if (HTAccess::isInActiveHTAccessDirsArray('index')) {
        if (Config::isConfigFileThere()) {
            $config = Config::loadConfigAndFix();

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

    update_option('webp-express-migration-version', '6');
}

webpexpress_migrate6();
