<?php

namespace WebPExpress;

class PluginDeactivate
{
    // The hook was registred in AdminInit
    public static function deactivate() {

        list($success, $failures, $successes) = HTAccess::deactivateHTAccessRules();

        if ($success) {
            // Oh, it would be nice to be able to add a goodbye message here...
            // But well, that cannot be done here.
        } else {
            // Oh no. We failed removing the rules
            $msg = "<b>Sorry, can't let you disable WebP Express!</b><br>" .
                'There are rewrite rules in the <i>.htaccess</i> that could not be removed. If these are not removed, it would break all images.<br>' .
                'Please make your <i>.htaccess</i> writable and then try to disable WebPExpress again.<br>Alternatively, remove the rules manually in your <i>.htaccess</i> file and try disabling again.' .
                '<br>It concerns the following files:<br>';


            foreach ($failures as $rootId) {
                $msg .= '- ' . Paths::getAbsDirById($rootId) . '/.htaccess<br>';
            }

            Messenger::addMessage(
                'error',
                $msg
            );

            wp_redirect(admin_url('options-general.php?page=webp_express_settings_page'));
            exit;
        }
    }
}
