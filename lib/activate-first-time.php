<?php

include_once __DIR__ . '/classes/Actions.php';
use \WebPExpress\Actions;

include_once __DIR__ . '/classes/Config.php';
use \WebPExpress\Config;

include_once __DIR__ . '/classes/Messenger.php';
use \WebPExpress\Messenger;

include_once __DIR__ . '/classes/Paths.php';
use \WebPExpress\Paths;

include_once __DIR__ . '/classes/PlatformInfo.php';
use \WebPExpress\PlatformInfo;

include_once __DIR__ . '/classes/State.php';
use \WebPExpress\State;

// First check basic requirements.
// -------------------------------

if (PlatformInfo::isMicrosoftIis()) {
    Messenger::addMessage('error', 'You are on Microsof IIS server. The plugin does not work on IIS (yet). The plugin has been <i>deactivated</i> again!');
    Actions::procastinate('deactivate');

    // Well, that was it.
    return;
}


if ( is_multisite() ) {
    Messenger::addMessage('error', 'You are on multisite. It is not supported yet. BUT IT IS ON THE ROADMAP! Stay tuned! The plugin has been <i>deactivated</i> again!');
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
