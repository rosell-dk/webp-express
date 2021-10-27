<?php

namespace WebPExpress;

use \WebPExpress\Option;
use \WebPExpress\State;
use \WebPExpress\Messenger;

class DismissableGlobalMessages
{

    /**
     *  Add dismissible message.
     *
     *  @param  string  $id  An identifier, ie "suggest_enable_pngs"
     */
    public static function addDismissableMessage($id)
    {
        $dismissableGlobalMessageIds = State::getState('dismissableGlobalMessageIds', []);

        // Ensure we do not add a message that is already there
        if (in_array($id, $dismissableGlobalMessageIds)) {
            return;
        }
        $dismissableGlobalMessageIds[] = $id;
        State::setState('dismissableGlobalMessageIds', $dismissableGlobalMessageIds);
    }

    public static function printDismissableMessage($level, $msg, $id, $buttons)
    {
        $msg .= '<br><br>';
        foreach ($buttons as $i => $button) {
            $javascript = "jQuery(this).closest('div.notice').slideUp();";
            //$javascript = "console.log(jQuery(this).closest('div.notice'));";
            $javascript .= "jQuery.post(ajaxurl, " .
                "{'action': 'webpexpress_dismiss_global_message', " .
                "'id': '" . $id . "'})";
            if (isset($button['javascript'])) {
                $javascript .= ".done(function() {" . $button['javascript'] . "});";
            }
            if (isset($button['redirect-to-settings'])) {
                $javascript .= ".done(function() {location.href='" . Paths::getSettingsUrl() . "'});";
            }

            $msg .= '<button type="button" class="button ' .
                (($i == 0) ? 'button-primary' : '') .
                '" onclick="' . $javascript . '" ' .
                'style="display:inline-block; margin-top:20px; margin-right:20px; ' . (($i > 0) ? 'float:right;' : '') .
                '">' . $button['text'] . '</button>';

        }
        Messenger::printMessage($level, $msg);
    }

    public static function printMessages()
    {
        $ids = State::getState('dismissableGlobalMessageIds', []);
        foreach ($ids as $id) {
            include_once __DIR__ . '/../dismissable-global-messages/' . $id . '.php';
        }
    }

    /**
     *  Dismiss message
     *
     *  @param  string  $id  An identifier, ie "suggest_enable_pngs"
     */
    public static function dismissMessage($id) {
        $messages = State::getState('dismissableGlobalMessageIds', []);
        $newQueue = [];
        foreach ($messages as $mid) {
            if ($mid == $id) {

            } else {
                $newQueue[] = $mid;
            }
        }
        State::setState('dismissableGlobalMessageIds', $newQueue);
    }

    /**
     *  Dismiss message
     *
     *  @param  string  $id  An identifier, ie "suggest_enable_pngs"
     */
    public static function dismissAll() {
        State::setState('dismissableGlobalMessageIds', []);
    }

    public static function processAjaxDismissGlobalMessage() {
        /*
        We have no security nonce here
        Dismissing a message is not harmful and dismissMessage($id) do anything harmful, no matter what you send in the "id"
        */
        $id = sanitize_text_field($_POST['id']);
        self::dismissMessage($id);
    }


}
