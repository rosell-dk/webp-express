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
     *  @param int      $id       id (only relevant for "sticky" messages
     *  @param boolean  $sticky   a sticky message can only go away by clicking a dismiss button
     *
     *  Hm... we should add some sprintf-like support
     *  $msg = sprintf(__( 'You are on a very old version of PHP (%s). WebP Express may not work as intended.', 'webp-express' ), phpversion());
     */
    public static function addMessage($level, $msg, $id=0, $sticky = false) {
        //error_log('add message:' . $msg);

        Option::updateOption('webp-express-messages-pending', true, true);  // We want this option to be autoloaded
        $pendingMessages = State::getState('pendingMessages', []);

        // Ensure we do not add a message that is already pending.
        foreach ($pendingMessages as $i => $entry) {
            if ($entry['message'] == $msg) {
                return;
            }
        }
        $pendingMessages[] = ['level' => $level, 'message' => $msg, 'id' => $id, 'sticky' => $sticky];
        State::setState('pendingMessages', $pendingMessages);
    }

    public static function addStickyMessage($level, $msg, $id, $gotItText = '')
    {
        if ($gotItText != '') {
            $javascript = "jQuery.post(ajaxurl, {'action': 'webpexpress_dismiss_message', 'id': " . $id . "});";
            $javascript .= "jQuery(this).parentsUntil('div.notice').parent().hide();";

            $msg .= '<br><br><button type="button" class="button button-primary" onclick="' . $javascript . '">' . $gotItText . '</button>';
        }
        self::addMessage($level, $msg, $id, true);
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
          '<div class="%1$s"><p>%2$s</p></div>',
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

        $stickyMessages = [];
        foreach ($messages as $message) {
            self::printMessage($message['level'], $message['message']);
            if (isset($message['sticky']) && ($message['sticky'] === true)) {
                $stickyMessages[] = $message;
            }
        }

        State::setState('pendingMessages', $stickyMessages);
        //State::setState('pendingMessages', []);

        if (count($stickyMessages) == 0) {
            Option::updateOption('webp-express-messages-pending', false, true);
        }
    }

    public static function processAjaxDismissMessage() {
        $id = intval($_POST['id']);
        //error_log('deleting:' . $id);

        $messages = State::getState('pendingMessages', []);
        $newQueue = [];
        foreach ($messages as $message) {
            if ($message['sticky'] && $message['id'] == $id) {

            } else {
                $newQueue[] = $message;
            }
        }
        State::setState('pendingMessages', $newQueue);
    }


    /**
     *  Add dismissible message for the WebP Express options page screen only.
     *
     *  @param  string  $id  An identifier, ie "suggest_enable_pngs"
     */
    public static function addDismissablePageMessage($id)
    {
        $dismissablePageMessageIds = State::getState('dismissablePageMessageIds', []);

        // Ensure we do not add a message that is already there
        if (in_array($id, $dismissablePageMessageIds)) {
            return;
        }
        $dismissablePageMessageIds[] = $id;
        State::setState('dismissablePageMessageIds', $dismissablePageMessageIds);
    }

    public static function printDismissablePageMessage($level, $msg, $id, $gotItText = '')
    {
        if ($gotItText != '') {
            $javascript = "jQuery.post(ajaxurl, {'action': 'webpexpress_dismiss_page_message', 'id': '" . $id . "'});";
            $javascript .= "jQuery(this).parentsUntil('div.notice').parent().hide();";

            $msg .= '<br><br><button type="button" class="button button-primary" onclick="' . $javascript . '">' . $gotItText . '</button>';
        }
        self::printMessage($level, $msg);
    }

    /**
     *  Add dismissible message for the WebP Express options page screen only.
     *
     *  @param  string  $id  An identifier, ie "suggest_enable_pngs"
     */
    public static function dismissPageMessage($id) {
        $messages = State::getState('dismissablePageMessageIds', []);
        $newQueue = [];
        foreach ($messages as $mid) {
            if ($mid == $id) {

            } else {
                $newQueue[] = $mid;
            }
        }
        State::setState('dismissablePageMessageIds', $newQueue);
    }

    public static function processAjaxDismissPageMessage() {
        $id = $_POST['id'];
        self::dismissPageMessage($id);
    }


}
