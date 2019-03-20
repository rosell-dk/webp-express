<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\HTAccess;
use \WebPExpress\Messenger;
use \WebPExpress\Multisite;
use \WebPExpress\Paths;
use \WebPExpress\PlatformInfo;
use \WebPExpress\State;

class PluginActivate
{
    // callback for 'register_activation_hook' (registred in AdminInit)
    public static function activate($network_active) {

        Multisite::overrideIsNetworkActivated($network_active);

        // Test if plugin is activated for the first time or reactivated
        if (State::getState('configured', false)) {
            self::reactivate();
        } else {
            self::activateFirstTime();
        }

    }

    private static function reactivate()
    {
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
    }

    private static function activateFirstTime()
    {
        // First check basic requirements.
        // -------------------------------

        if (PlatformInfo::isMicrosoftIis()) {
            Messenger::addMessage(
                'warning',
                'You are on Microsoft IIS server. ' .
                    'WebP Express <a href="https://github.com/rosell-dk/webp-express/pull/213">should work on Windows now</a>, but it has not been tested thoroughly.'

            );
        }


        if ( is_multisite() ) {
            Messenger::addMessage(
                'warning',
                'Multisite functionality in still new in WebP Express (it was added in release 0.12.0). ' .
                'While it has been tested on several setups, there might be a bug or two yet to be found.'
            );
        }

        if (!version_compare(PHP_VERSION, '5.5.0', '>=')) {
            Messenger::addMessage(
                'warning',
                'You are on a very old version of PHP. WebP Express may not work correctly. Your PHP version:' . phpversion()
            );
        }

        // Next issue warnings, if any
        // -------------------------------

        if (PlatformInfo::isApache() || PlatformInfo::isLiteSpeed()) {
            // all is well.
        } else {
            Messenger::addMessage(
                'warning',
                'You are not on Apache server, nor on LiteSpeed. WebP Express only works out of the box on Apache and LiteSpeed.<br>' .
                    'But you may get it to work. WebP Express will print you rewrite rules for Apache. You could try to configure your server to do similar routing.<br>' .
                    'Btw: your server is: ' . $_SERVER['SERVER_SOFTWARE']
            );
        }

        // Welcome!
        // -------------------------------
        Messenger::addMessage(
            'info',
            'WebP Express was installed successfully. To start using it, you must ' .
                '<a href="' . Paths::getSettingsUrl() . '">configure it here</a>.'
        );

        // While not neccessary, lets get those tests copied right away. Some servers are a bit slow to pick up on changes in the filesystem
        CapabilityTest::copyCapabilityTestsToWpContent();
    }
}
