<?php

add_action( 'admin_menu', function() {

    //Add Settings Page
    add_options_page(
        'WebP Express Settings', //Page Title
        'WebP Express', //Menu Title
        'manage_options', //capability
        'webp_express_settings_page', //menu slug
        'webp_express_settings_page_content' //The function to be called to output the content for this page.
    );
});

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
	if ( ( 'options-general.php' === $pagenow ) && ( isset( $_GET['page'] ) ) && ( 'webp_express_settings_page' === $_GET['page'] ) ) {
		add_action( 'admin_enqueue_scripts', function () {
			include __DIR__ . '/enqueue_scripts.php';
		} );
	}
}

add_action( 'admin_init', 'webp_express_admin_init');
