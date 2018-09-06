<?php

/**
 *  $level:  info | success | warning | error
 *  $msg:    the message
 */
function webpexpress_add_message($level, $msg, $escHtml = true) {
    update_option('webp-express-message-pending', true, false);

    $pendingMessagesJSON = get_option('webp-express-pending-messages', '[]');
    $pendingMessages = json_decode($pendingMessagesJSON, true);
    $pendingMessages[] = ['level' => $level, 'message' => $msg, 'escHtml' => $escHtml];

    update_option('webp-express-pending-messages', json_encode($pendingMessages, JSON_UNESCAPED_SLASHES), false);
}
