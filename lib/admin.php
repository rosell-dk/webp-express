<?php
use \WebPExpress\State;
use \WebPExpress\Option;
use \WebPExpress\Multisite;

// When an update requires a migration, the number should be increased
define('WEBPEXPRESS_MIGRATION_VERSION', '6');

if (WEBPEXPRESS_MIGRATION_VERSION != Option::getOption('webp-express-migration-version', 0)) {
    // run migration logic
    include __DIR__ . '/migrate/migrate.php';
}

// uncomment next line to test-run a migration
// include __DIR__ . '/migrate/migrate6.php';

// uncomment next line to debug an error during activation
//include __DIR__ . "/debug.php";

include __DIR__ . '/options/options-hooks.php';

register_activation_hook(WEBPEXPRESS_PLUGIN, function ($network_active) {
    Multisite::overrideIsNetworkActivated($network_active);
    include __DIR__ . '/activate-hook.php';
});

register_deactivation_hook(WEBPEXPRESS_PLUGIN, function () {
    include __DIR__ . '/deactivate.php';
});

if (Option::getOption('webp-express-messages-pending')) {
    include_once __DIR__ . '/classes/Messenger.php';

    add_action(Multisite::isNetworkActivated() ? 'network_admin_notices' : 'admin_notices', function() {
        \WebPExpress\Messenger::printPendingMessages();
    });
}
if (Option::getOption('webp-express-actions-pending')) {
    include_once __DIR__ . '/classes/Actions.php';
    \WebPExpress\Actions::processQueuedActions();
}

function webp_express_uninstall() {
    include __DIR__ . '/uninstall.php';
}

// interestingly, I get "Serialization of 'Closure' is not allowed" if I pass anonymous function
// ... perhaps we should not do that in the other hooks either.
register_uninstall_hook(WEBPEXPRESS_PLUGIN, 'webp_express_uninstall');

// Add settings link on the plugins page
add_filter('plugin_action_links_' . plugin_basename(WEBPEXPRESS_PLUGIN), function ( $links ) {
    if (Multisite::isNetworkActivated()) {
        $mylinks= [
            '<a href="https://ko-fi.com/rosell" target="_blank">donate?</a>',
        ];
    } else {
        $mylinks = array(
            '<a href="' . admin_url('options-general.php?page=webp_express_settings_page') . '">Settings</a>',
            '<a href="https://ko-fi.com/rosell" target="_blank">Provide coffee for the developer</a>',
        );

    }
    return array_merge($links, $mylinks);
});

add_filter('network_admin_plugin_action_links_' . plugin_basename(WEBPEXPRESS_PLUGIN), function ( $links ) {
    $mylinks = array(
        '<a href="' . network_admin_url('settings.php?page=webp_express_settings_page') . '">Settings</a>',
        '<a href="https://ko-fi.com/rosell" target="_blank">donate?</a>',
    );
    return array_merge($links, $mylinks);
});
