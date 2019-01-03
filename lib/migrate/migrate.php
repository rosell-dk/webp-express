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

    for ($x = intval(get_option('webp-express-migration-version', 0)); $x < WEBPEXPRESS_MIGRATION_VERSION; $x++) {
        if (intval(get_option('webp-express-migration-version', 0)) == $x) {
            // run migration X+1, which upgrades from X to X+1
            // It must take care of updating the "webp-express-migration-version" option to X+1, - if successful.
            // If unsuccessful, it must leaves the option unaltered, which will prevent
            // newer migrations to run, until the problem with that migration is fixed.
            include __DIR__ . '/migrate' . ($x + 1) . '.php';
        }

    }
/*
    if (intval(get_option('webp-express-migration-version', 0)) == 0) {
        // run migration 1
        // It must take care of updating migration-version to 1, - if successful.
        include __DIR__ . '/migrate1.php';
    }

    // We make sure to grab the option again - it might have been changed in the migration above
    if (intval(get_option('webp-express-migration-version', 0)) == 1) {
        // run migration 2
        include __DIR__ . '/migrate2.php';
    }


    // We make sure to grab the option again - it might have been changed in the migration above
    if (intval(get_option('webp-express-migration-version', 0)) == 2) {
        // run migration 3
        include __DIR__ . '/migrate3.php';
    }
    */
}
