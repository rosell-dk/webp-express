<?php

/*
Unfortunately, we did not set the 'webp-express-configured' option back in 0.1
As long as there are still users on 0.1, we must do the following:
*/
// TODO: TEST the following
if (!get_option('webp-express-configured', false)) {
    if (!is_null(get_option('webp_express_converters', null))) {
        update_option('webp-express-configured', true);
    }
}



// If options never has been saved, no migration is needed.
// - and we can then set migrate-version to current
if (!(get_option('webp-express-configured', false))) {
    update_option('webp-express-migration-version', WEBPEXPRESS_MIGRATION_VERSION);
} else {

    if (intval(get_option('webp-express-migration-version', 0)) == 0) {
        // run migration 1
        // It must take care of updating migration-version, - if successful.
        include __DIR__ . '/migrate1.php';
    }

    // When a new version needs a new migration, uncomment this:
    // (make sure to grab the option again - it might have been changed in the migration above)
    /*
    if (intval(get_option('webp-express-migration-version', 0)) == 1) {
        // run migration 2
        include __DIR__ . '/migrate2.php';
    }
    */

}






//include __DIR__ . '/migrate_50.php';

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
