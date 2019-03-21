<?php

use \WebPExpress\Config;
use \WebPExpress\Messenger;
use \WebPExpress\Option;
use \WebPExpress\State;

/*
In 0.4.0, we had a 'webp-express-configured' option.
As long as there are still users on 0.4 or below, we must do the following:
*/
if (Option::getOption('webp-express-configured', false)) {
    State::setState('configured', true);
}

/*
In 0.1, we did not have the 'webp-express-configured' option.
To determine if WebP Express was configured in 0.1, we can test the (now obsolete) webp_express_converters option
As long as there are still users on 0.1, we must do the following:
*/
if (!Option::getOption('webp-express-configured', false)) {
    if (!is_null(Option::getOption('webp_express_converters', null))) {
        State::setState('configured', true);
    }
}

if (!(State::getState('configured', false))) {
    // Options has never has been saved, so no migration is needed.
    // We can set migrate-version to current
    Option::updateOption('webp-express-migration-version', WEBPEXPRESS_MIGRATION_VERSION);
} else {

    for ($x = intval(Option::getOption('webp-express-migration-version', 0)); $x < WEBPEXPRESS_MIGRATION_VERSION; $x++) {
        if (intval(Option::getOption('webp-express-migration-version', 0)) == $x) {
            // run migration X+1, which upgrades from X to X+1
            // It must take care of updating the "webp-express-migration-version" option to X+1, - if successful.
            // If unsuccessful, it must leaves the option unaltered, which will prevent
            // newer migrations to run, until the problem with that migration is fixed.
            include __DIR__ . '/migrate' . ($x + 1) . '.php';
        }
    }
}

//KeepEwwwSubscriptionAlive::keepAliveIfItIsTime($config);
