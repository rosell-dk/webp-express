<?php

namespace WebPExpress;

use \WebPExpress\HTAccess;
use \WebPExpress\Messenger;

class PluginDeactivate
{
    // The hook was registred in AdminInit
    public static function deactivate() {

        $result = HTAccess::deactivateHTAccessRules();
        if ($result !== true) {
            // Oh no. We failed removing the rules
            $msg = "<b>Sorry, can't let you disable WebP Express!</b><br>" .
                'There are rewrite rules in the <i>.htaccess</i> that could not be removed. If these are not removed, it would break all images.<br>' .
                'Please make your <i>.htaccess</i> writable and then try to disable WebPExpress again.<br>Alternatively, remove the rules manually in your <i>.htaccess</i> file and try disabling again.' .
                '<br>It concerns the following files:<br>' . implode('<br>', $result);

            Messenger::addMessage(
                'error',
                $msg
            );

            wp_redirect( $_SERVER['HTTP_REFERER']);
            exit;
        }
    }
}
