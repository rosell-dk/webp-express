<?php

namespace WebPExpress;

use \WebPExpress\Option;
use \WebPExpress\State;

class Messenger
{
    private static $printedStyles = false;

    /**
     *  @param string   $level    (info | success | warning | error)
     *  @param string   $msg      the message (not translated)
     *
     *  Hm... we should add some sprintf-like support
     *  $msg = sprintf(__( 'You are on a very old version of PHP (%s). WebP Express may not work as intended.', 'webp-express' ), phpversion());
     */
    public static function addMessage($level, $msg) {
        //error_log('add message:' . $msg);

        Option::updateOption('webp-express-messages-pending', true, true);  // We want this option to be autoloaded
        $pendingMessages = State::getState('pendingMessages', []);

        // Ensure we do not add a message that is already pending.
        foreach ($pendingMessages as $i => $entry) {
            if ($entry['message'] == $msg) {
                return;
            }
        }
        $pendingMessages[] = ['level' => $level, 'message' => $msg];
        State::setState('pendingMessages', $pendingMessages);
    }

    public static function printMessage($level, $msg) {
        if (!(self::$printedStyles)) {
            global $wp_version;
            if (floatval(substr($wp_version, 0, 3)) < 4.1) {
                // Actually, I don't know precisely what version the styles were introduced.
                // They are there in 4.1. They are not there in 4.0
                self::printMessageStylesForOldWordpress();
            }
            self::$printedStyles = true;
        }

        //$msg = __( $msg, 'webp-express');     // uncommented. We should add some sprintf-like functionality before making the plugin translatable
        printf(
          '<div class="%1$s"><div style="margin:10px 0">%2$s</div></div>',
          //esc_attr('notice notice-' . $level . ' is-dismissible'),
          esc_attr('notice notice-' . $level),
          $msg
        );
    }

    private static function printMessageStylesForOldWordpress() {
        ?>
        <style>
        /* In Older Wordpress (ie 4.0), .notice is not declared */
        .notice {
            background: #fff;
            border-left: 4px solid #fff;
            -webkit-box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
            box-shadow: 0 1px 1px 0 rgba(0,0,0,.1);
            margin: 10px 15px 2px 2px;
            padding: 1px 12px;
        }
        .notice-error {
            border-left-color: #dc3232;
        }
        .notice-success {esc_attr('notice notice-' . $level . ' is-dismissible'),
            border-left-color: #46b450;
        }
        .notice-info {
            border-left-color: #00a0d2;
        }
        .notice-warning {
            border-left-color: #ffb900;
        }
        </style>
        <?php
    }

    public static function printPendingMessages() {

        $messages = State::getState('pendingMessages', []);

        foreach ($messages as $message) {
            self::printMessage($message['level'], $message['message']);
        }

        State::setState('pendingMessages', []);

        Option::updateOption('webp-express-messages-pending', false, true);
    }

}
