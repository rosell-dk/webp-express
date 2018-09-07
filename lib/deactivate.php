<?php

include_once __DIR__ . '/classes/Config.php';
use \WebPExpress\Config;

include_once __DIR__ . '/classes/Messenger.php';
use \WebPExpress\Messenger;


if (!Config::deactivateHTAccessRules()) {

    /*
    TODO!
    if (Config::doesHTAccessExists()) {
        Messenger::addMessage('error',
            'Could not remove rewrite rules in the <i>.htaccess</i>. YOU wil have to do that! Now! (I recommend you reactivate WebP Express until you have solved the issue)'
        );
    } else {
        Messenger::addMessage('warning',
            'Plugin has been deactivated. Remember to remove the Rewrite Rules in your VirtualHost!'
        );
    }
    */

}
