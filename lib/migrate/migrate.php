<?php

$currentMigration = intval(get_option('webp-express-migration-version', 0));

if ($currentMigration == 0) {
    // run migration 1
    // It must take care of updating migration-version, - if successful.
    include __DIR__ . '/migrate1.php';
}

// When a new version needs a new migration, uncomment this:
/*
if ($currentMigration == 1) {
    // run migration 2
    include __DIR__ . '/migrate2.php';
}
*/




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
