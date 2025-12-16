<?php

namespace WebPExpress;

$msgId = '0.25.10/renamed-config-file';

DismissableGlobalMessages::printDismissableMessage(
    'info',
    'WebP Express has renamed its configuration files to something unique. ' .
        'This was done in order to protect its contents from prying eyes on servers that does not respect .htaccess files (such as Nginx). ' .
        (PlatformInfo::isNginx() ? 'I apologize for not failing to consider that this could be a problem on Nginx' : 'Your server is by the way not Nginx'),
    $msgId,
    [
        ['text' => 'Ok'],
    ]
);
