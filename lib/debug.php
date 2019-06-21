<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
This file is actually not included, only when debugging activation errors, I include it manually.
Haven't used it in a quite a while...
*/

function webpexpress_activated() {
  update_option( 'webp-express-activation-error',  ob_get_contents() );
}
add_action( 'activated_plugin', 'webpexpress_activated' );
if (!empty(get_option('webp-express-activation-error'))) {
    add_filter( 'admin_footer_text', function() {
        return 'Activation error:' . get_option('webp-express-activation-error');
    });
}
