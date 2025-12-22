<?php

namespace WebPExpress;

$msgId = '0.25.12/nginx-rewrites-needs-updating';

DismissableGlobalMessages::printDismissableMessage(
    'warning',
    '<p>WebP Express needed to rename its configuration files to contain random characters - for security reasons. ' .
        'You will need to update the NGINX rewrites so it matches the correct new names. ' .
        'Check out the updated instructions in the README.</p>' .
        '<p>In short, you should edit the WebP Express rules set up in your server context (usually found in /etc/nginx/sites-available). You must now pass "&hash=' .  Paths::getConfigHash() . '" to the converter script.</p>' .
        '<p>Sorry for the inconvenience. The plugin was taken off wordpress because of this issue, so I needed to take immediate action</p>',
    $msgId,
    [
        ['text' => 'Ok'],
    ]
);
