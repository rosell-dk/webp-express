<?php

namespace WebPExpress;

$msgId = '0.25.12/nginx-rewrites-needs-updating';

/*
DismissableGlobalMessages::printDismissableMessage(
    'info',
    '<p>WebP Express needed to rename its configuration files to contain random characters - for security reasons. ' .
        'You can safely ignore this message. However, if you want to do a tiny micro optimization, you can pass the "hash" part of the new name in the nginx rewrite rules (it seems you are running on nginx).' .
        'Check out the updated instructions in the README.</p>' .
        '<p>In short, by editing the WebP Express rules set up in your server context (usually found in /etc/nginx/sites-available), if you pass "&hash=' .  Paths::getConfigHash() . '" to the converter script, the converter script can read the config file directly, without resorting to finding it by inspecting the files in the directory.</p>',
    $msgId,
    [
        ['text' => 'Ok'],
    ]
);
*/

// The message is no longer relevant, as it is no longer neccessary to pass the hash
// So dismiss it, so this code is not run again
DismissableGlobalMessages::dismissMessage($msgId);
