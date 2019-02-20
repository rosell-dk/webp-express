<?php
use \WebPExpress\Config;
use \WebPExpress\Multisite;

if (Multisite::isNetworkActivated()) {
    add_action("network_admin_menu", function() {
        add_submenu_page(
    		'settings.php', // Parent element
    		'WebP Express settings (for network)', // Text in browser title bar
    		'WebP Express', // Text to be displayed in the menu.
    		'manage_network_options', // Capability
    		'webp_express_settings_page', // slug
    		'webp_express_settings_page_content2' // Callback function which displays the page
    	);
    });
} else {
    add_action( 'admin_menu', function() {
        //Add Settings Page
        add_options_page(
            'WebP Express Settings', //Page Title
            'WebP Express', //Menu Title
            'manage_options', //capability
            'webp_express_settings_page', // slug
            'webp_express_settings_page_content' //The function to be called to output the content for this page.
        );
    });
}

add_action('admin_post_webpexpress_settings_submit', function() {
    include __DIR__ . '/submit.php';
});

function webp_express_settings_page_content()
{
    //include __DIR__ . '/enqueue_scripts.php';     // we don't do that, because our inline scripts would not be written in head on wordpress 4.4 and below
    include __DIR__ . '/page.php';
}

function webp_express_admin_init() {

	global $pagenow;
	if ((('options-general.php' === $pagenow) || (('settings.php' === $pagenow)))  && (isset( $_GET['page'])) && ('webp_express_settings_page' === $_GET['page'])) {
        add_action( 'admin_enqueue_scripts', function () {
            include __DIR__ . '/enqueue_scripts.php';
        } );
	}

}

add_action( 'admin_init', 'webp_express_admin_init');


include_once __DIR__ . '/../classes/Config.php';

// -- multisite

function webp_express_settings_page_content2()
{
    /*
    if ( is_plugin_active_for_network( 'webp-express/webp-express.php' ) ) {
        echo 'network activated, yes';
    }
    $config = Config::getConfigForOptionsPage();
    echo '<pre>' . print_r($config) . '</pre>';
*/
    include __DIR__ . '/page.php';
}
