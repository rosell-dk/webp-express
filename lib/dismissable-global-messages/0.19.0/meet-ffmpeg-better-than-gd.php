<?php

namespace WebPExpress;

$msgId = '0.19.0/meet-ffmpeg-better-than-gd';

DismissableGlobalMessages::printDismissableMessage(
    'info',
    'WebP Express 0.19.0 introduced a new conversion method: ffmpeg. ' .
        'You may consider moving it above Gd, as it is slightly better. ',
    $msgId,
    [
        ['text' => 'Take me to the settings', 'redirect-to-settings' => true],
        ['text' => 'Dismiss'],
    ]
);
