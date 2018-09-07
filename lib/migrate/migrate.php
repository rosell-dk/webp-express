<?php

$version = get_option('webp-express-version', '0.4.0');

// Make a number out of it.
// "0.4.1" => 41
// "1.0.0" => 100
$version = intval(str_replace('.', '', $version));


if ($version <= 40) {
    include 'migrate_50.php';
}

/*
if (empty(get_option('webp-express-configured'))) {
    global $wpdb;
    $hasWebPExpressOptionBeenSaved = ($wpdb->get_row( "SELECT * FROM $wpdb->options WHERE option_name = 'webp_express_converters'" ) !== null);
    if ($hasWebPExpressOptionBeenSaved) {
        // Store the fact that webp options has been changed.
        // When nobody is using 0.3 or below, we can test on the existence of that option, instead of
        // querying the database directly ($hasWebPExpressOptionBeenSaved)
        add_option('webp-express-configured', true);
    }
}
*/
