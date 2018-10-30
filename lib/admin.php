<?php
use \WebPExpress\State;

// When an update requires a migration, the number should be increased
define('WEBPEXPRESS_MIGRATION_VERSION', '2');

if (WEBPEXPRESS_MIGRATION_VERSION != get_option('webp-express-migration-version', 0)) {
    // run migration logic
    include __DIR__ . '/migrate/migrate.php';
}

// uncomment next line to debug an error during activation
//include __DIR__ . "/debug.php";

include __DIR__ . '/options/options-hooks.php';

register_activation_hook(WEBPEXPRESS_PLUGIN, function () {
    include __DIR__ . '/activate-hook.php';
});

register_deactivation_hook(WEBPEXPRESS_PLUGIN, function () {
    include __DIR__ . '/deactivate.php';
});

if (get_option('webp-express-messages-pending')) {
    include_once __DIR__ . '/classes/Messenger.php';
    add_action( 'admin_notices', function() {
        \WebPExpress\Messenger::printPendingMessages();
    });
}
if (get_option('webp-express-actions-pending')) {
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
    $mylinks = array(
        '<a href="' . admin_url( 'options-general.php?page=webp_express_settings_page' ) . '">Settings</a>',
    );
    return array_merge($links, $mylinks);
});

add_action('wp_ajax_webpexpress_start_listening', 'webpexpress_start_listening');
function webpexpress_start_listening() {
    include_once __DIR__ . '/classes/State.php';
    State::setState('listening', true);
    State::setState('request', null);
    wp_die();
}

add_action('wp_ajax_webpexpress_stop_listening', 'webpexpress_stop_listening');
function webpexpress_stop_listening() {
    include_once __DIR__ . '/classes/State.php';
    State::setState('listening', false);
    State::setState('request', null);
    wp_die();
}

add_action('wp_ajax_webpexpress_get_request', 'webpexpress_get_request');
function webpexpress_get_request() {
    include_once __DIR__ . '/classes/State.php';
    echo json_encode(State::getState('request', null), JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    wp_die();
}

add_action('wp_ajax_webpexpress_request_access', 'webpexpress_request_access');
function webpexpress_request_access() {

    $ch = curl_init();

    curl_setopt_array(
        $ch,
        [
            CURLOPT_URL => 'http://we0/wordpress/webp-express-server',
            CURLOPT_HTTPHEADER => [
                'User-Agent: WebPConvert',
            ],
            CURLOPT_POSTFIELDS => [
                'action' => 'request-access',
                'label' => 'test',
                'key' => 'test-key'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false
        ]
    );
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
    }
    
    $returnObj = [
        'success' => true
    ];
    echo json_encode($returnObj, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

    wp_die();
}
/*
add_action('wp_ajax_webpexpress_accept_request', 'webpexpress_accept_request');
function webpexpress_accept_request() {
    include_once __DIR__ . '/classes/State.php';

    State::setState('listening', true);
    State::setState('request', null);
    wp_die();
}*/
