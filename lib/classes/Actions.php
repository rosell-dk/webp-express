<?php

namespace WebPExpress;

include_once "State.php";
use \WebPExpress\State;

/**
 *
 */

class Actions
{
    /**
     *  $action:    identifier
     */
    public static function procastinate($action) {
        update_option('webp-express-actions-pending', true, true);

        $pendingActions = State::getState('pendingActions', []);
        $pendingActions[] = $action;
        State::setState('pendingActions', $pendingActions);
    }

    public static function takeAction($action) {
        switch ($action) {
            case 'deactivate':
                add_action('admin_init', function () {
                    deactivate_plugins(plugin_basename(WEBPEXPRESS_PLUGIN));
                });
                break;
        }
    }

    public static function processQueuedActions() {
        $actions = State::getState('pendingActions', []);

        foreach ($actions as $action) {
            self::takeAction($action);
        }

        State::setState('pendingActions', []);
        update_option('webp-express-actions-pending', false, true);

    }
}
