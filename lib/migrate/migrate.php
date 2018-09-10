<?php

include_once __DIR__ . '/../classes/State.php';
use \WebPExpress\State;


/*
In 0.4.0, we had a 'webp-express-configured' option.
As long as there are still users on 0.4 or below, we must do the following:
*/
if (get_option('webp-express-configured', false)) {
    State::setState('configured', true);
}

/*
In 0.1, we did not have the 'webp-express-configured' option.
To determine if WebP Express was configured in 0.1, we can test the (now obsolete) webp_express_converters option
As long as there are still users on 0.1, we must do the following:
*/
if (!get_option('webp-express-configured', false)) {
    if (!is_null(get_option('webp_express_converters', null))) {
        State::setState('configured', true);
    }
}


if (!(State::getState('configured', false))) {
    // Options has never has been saved, so no migration is needed.
    // We can set migrate-version to current
    update_option('webp-express-migration-version', WEBPEXPRESS_MIGRATION_VERSION);
} else {

    if (intval(get_option('webp-express-migration-version', 0)) == 0) {
        // run migration 1
        // It must take care of updating migration-version to 1, - if successful.
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
