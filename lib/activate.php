<?php

include_once __DIR__ . '/classes/Config.php';
use \WebPExpress\Config;

include_once __DIR__ . '/classes/Messenger.php';
use \WebPExpress\Messenger;

include_once __DIR__ . '/classes/Actions.php';
use \WebPExpress\Actions;

include_once __DIR__ . '/classes/State.php';
use \WebPExpress\State;

function webp_express_activate() {

    $server = strtolower($_SERVER['SERVER_SOFTWARE']);

    // First check basic requirements.
    // -------------------------------

    $serverIsMicrosoftIis = ( strpos( $server, 'microsoft-iis') !== false );
    if ($serverIsMicrosoftIis) {
        Messenger::addMessage('error', 'You are on Microsof IIS server. The plugin does not work on IIS (yet)');
        Actions::procastinate('deactivate');

        // Well, that was it.
        return;
    }

    if ( is_multisite() ) {
        Messenger::addMessage('error', 'You are on multisite. It is not supported yet. BUT IT IS ON THE ROADMAP! Stay tuned!');
        Actions::procastinate('deactivate');
        return;
    }

    if (!version_compare(PHP_VERSION, '5.5.0', '>=')) {
        //$msg = sprintf(__( 'You are on a very old version of PHP (%s). WebP Express may not work as intended.', 'webp-express' ), phpversion());
        Messenger::addMessage(
            'warning',
            'You are on a very old version of PHP. WebP Express may not work correctly. Your PHP version:' . phpversion()
        );
        return;
    }

    // Next issue warnings, if any
    // -------------------------------

    $server_is_litespeed = ( strpos( $server, 'litespeed') !== false );
    $server_is_apache = ( strpos( $server, 'apache') !== false );
    if ($server_is_litespeed || $server_is_apache) {
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
            '<a href="options-general.php?page=webp_express_settings_page">configure it here</a>.'
    );
}

function webp_express_reactivate() {
    // The plugin has been reactivated.
    // We must regenerate the .htaccess rules.
    // (config dir and options and of course still there, no need to do anything about that)

//    Messenger::addMessage('error', 'You are on Microsof IIS server. The plugin does not work on IIS (yet)');
    //Actions::procastinate('deactivate');

    $config = Config::loadConfig();
    if ($config === false) {
        Messenger::addMessage(
            'error',
            'The config file seems to have gone missing. You will need to reconfigure WebP Express <a href="options-general.php?page=webp_express_settings_page">(here)</a>.'
        );
    } else {
        $htaccessExists = Config::doesHTAccessExists();
        $rules = Config::generateHTAccessRulesFromConfigObj($config);

        if ($htaccessExists) {
            if (Config::saveHTAccessRules($rules)) {
                Messenger::addMessage(
                    'success',
                    'WebP Express re-activated successfully.<br>' .
                        'The image redirections are in effect again (the <i>.htaccess</i> file was updated)<br><br>' .
                        'Just a quick reminder: If you at some point change the upload directory or move Wordpress, the <i>.htaccess</i> will need to be regenerated.<br>' .
                        'You do that by re-saving the settings <a href="options-general.php?page=webp_express_settings_page">(here)</a>'
                );
                return true;
            } else {
                Messenger::addMessage('error',
                    'WebP Express failed saving rewrite rules to your <i>.htaccess</i>.<br>' .
                    'Please deactivate the plugin, change file permissions, and try to activate again. Or paste the following into your <i>.htaccess</i>:' .
                    '<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
                );
                return true;
            }
        } else {
            Messenger::addMessage('info',
                'The rewrite rules needs to be updated. However, as you do not have an <i>.htaccess</i> file, you pressumably need to insert the rules in your VirtualHost manually. ' .
                'You must insert/update the rules to the following:' .
                '<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
            );
            return true;
        }

    }

    /*
    TODO!
        $rules = WebPExpressHelpers::generateHTAccessRulesFromConfigObj($config);

        if (!Config::saveHTAccessRules($rules)) {
            Messenger::addMessage('info',
                'You must insert the following rules in your VirtualHost manually (you do not have an <i>.htaccess</i> file in your root)<br>' .
                'Insert the following:<br>' .
                '<pre>' . htmlentities(print_r($rules, true)) . '</pre>'
            );
        }
        */
}

// Test if plugin is activated for the first time, or simply reactivated
if (State::getState('configured', false)) {
    webp_express_reactivate();
} else {
    webp_express_activate();
}
