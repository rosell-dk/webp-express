<?php

include_once __DIR__ . '/classes/Actions.php';
use \WebPExpress\Actions;

include_once __DIR__ . '/classes/CapabilityTest.php';
use \WebPExpress\CapabilityTest;

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
    Messenger::addMessage(
        'warning',
        'You are on Microsoft IIS server. The developer of WebP Express has no money for Microsoft products, so no testing have been done on IIS. Use at own risk. If you are on WAMP, things might work. Without Apache, you will need to create redirect rules yourself.'
    );
}


if ( is_multisite() ) {
    Messenger::addMessage(
        'warning',
        'Multisite functionality in WebP Express has just been added with current release (0.12.0). ' .
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
