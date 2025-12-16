<?php

namespace WebPExpress;

$msgId = '0.25.10/failed-renaming-config-file';

DismissableGlobalMessages::printDismissableMessage(
    'warning',
    'WebP Express failed renaming the configuration files to something secret. ' .
        'If you are on NGINX ' . (PlatformInfo::isNginx() ? '(you are)' : '(you are not)') . ', you should not consider the configuration files private. This is a problem if you store EWWW api key in the configuration files. Thu files in question are config.json and wod-options.json in wp-content/webp-express/config',
    $msgId,
    [
        ['text' => 'Dismiss'],
    ]
);
