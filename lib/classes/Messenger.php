<?php

namespace WebPExpress;

include_once "State.php";
use \WebPExpress\State;

class Messenger
{
    /**
     *  $level:  info | success | warning | error
     *  $msg:    the message (not translated)
     *
     *  Hm... we should add some sprintf-like support
     *  $msg = sprintf(__( 'You are on a very old version of PHP (%s). WebP Express may not work as intended.', 'webp-express' ), phpversion());
     */
    public static function addMessage($level, $msg) {

        update_option('webp-express-messages-pending', true, true);  // We want this option to be autoloaded

        $pendingMessages = State::getState('pendingMessages', []);
        $pendingMessages[] = ['level' => $level, 'message' => $msg];
        State::setState('pendingMessages', $pendingMessages);
    }

    public static function printMessage($level, $msg) {
        //$msg = __( $msg, 'webp-express');     // uncommented. We should add some sprintf-like functionality before making the plugin translatable
        printf(
          '<div class="%1$s"><p>%2$s</p></div>',
          esc_attr('notice notice-' . $level . ' is-dismissible'),
          $msg
        );
    }

    public static function printPendingMessages() {
        $messages = State::getState('pendingMessages', []);

        foreach ($messages as $message) {
            self::printMessage($message['level'], $message['message']);
        }

        State::setState('pendingMessages', []);
        update_option('webp-express-messages-pending', false, true);
    }
}
