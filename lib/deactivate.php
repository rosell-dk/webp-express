<?php

include_once __DIR__ . '/classes/Actions.php';
use \WebPExpress\Actions;

include_once __DIR__ . '/classes/Config.php';
use \WebPExpress\Config;

include_once __DIR__ . '/classes/Messenger.php';
use \WebPExpress\Messenger;

include_once __DIR__ . '/classes/Paths.php';
use \WebPExpress\Paths;



function webpexpress_deny_deactivate($msg) {
    Messenger::addMessage(
        'error',
        $msg
    );
    wp_redirect( $_SERVER['HTTP_REFERER']);
    exit;
}

if (Config::doesHTAccessExists()) {

    // Try to deactivate the rules
    if (!Config::deactivateHTAccessRules()) {

        // Oh no. We failed removing the rules

        $result = Config::deactivateHTAccessRules();
        if ($result !== true) {
            $msg = "<b>Sorry, can't let you disable WebP Express!</b><br>" .
                'There are rewrite rules in the <i>.htaccess</i> that could not be removed. If these are not removed, it would break all images.<br>' .
                'Please make your <i>.htaccess</i> writable and then try to disable WebPExpress again.<br>Alternatively, remove the rules manually in your <i>.htaccess</i> file and try disabling again.' .
                '<br>It conserns the following files:' . implode('<br>', $result);
            webpexpress_deny_deactivate($msg);
        }
    }
}
