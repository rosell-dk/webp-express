<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Messenger;

function webpexpress_migrate11() {

    $config = Config::loadConfigAndFix(false);  // false, because we do not need to test if quality detection is working

    // There is a new option: "scope"
    // We want to set it to match current setup as closely as possible.

    $rootsWithRulesIn = HTAccess::getRootsWithWebPExpressRulesIn();

    if (in_array('index', $rootsWithRulesIn)) {
        $scope = ['index', 'plugins', 'themes', 'uploads', 'wp-content'];
    } elseif (in_array('wp-content', $rootsWithRulesIn)) {
        $scope = ['plugins', 'themes', 'uploads', 'wp-content'];
    } else {
        $scope = ['themes', 'uploads'];
    }

    // However, if some of the roots cannot be used, we remove these.

    $scope2 = [];
    foreach ($scope as $rootId) {
        if (Paths::canWriteHTAccessRulesInDir($rootId)) {
            $scope2[] = $rootId;
        }
    }
    if (count($scope2) == 0) {
        Messenger::addMessage(
            'warning',
            'WebP Express cannot update the .htaccess rules that it needs to. ' .
                'Please go to WebP Express settings and click "Save settings and force new .htaccess rules".'
        );
        $scope2 = ['themes', 'uploads'];
    }

    $config['scope'] = $scope2;

    if (in_array('index', $config['scope'])) {
        DismissableMessages::addDismissableMessage('0.15.0/new-scope-setting-index');
    } elseif (in_array('wp-content', $config['scope'])) {
        DismissableMessages::addDismissableMessage('0.15.0/new-scope-setting-content');
    } elseif (!in_array('uploads', $config['scope'])) {
        DismissableMessages::addDismissableMessage('0.15.0/new-scope-setting-no-uploads');
    }

/*
    error_log('roots with rules:' . implode(',', $rootsWithRulesIn));
    error_log('scope:' . implode(',', $config['scope']));
    error_log('scope2:' . implode(',', $scope2));*/


    $forceHtaccessRegeneration = true;
    $result = Config::saveConfigurationAndHTAccess($config, $forceHtaccessRegeneration);

    if ($result['saved-both-config']) {
        Messenger::addMessage(
            'info',
            'Successfully migrated <i>WebP Express</i> options for 0.15.0.'
        );
        Option::updateOption('webp-express-migration-version', '11');

    } else {
        Messenger::addMessage(
            'error',
            'Failed migrating webp express options to 0.15.0. Probably you need to grant write permissions in your wp-content folder.'
        );
    }

}

webpexpress_migrate11();
