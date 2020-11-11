<?php

namespace WebPExpress;

$msgId = '0.19.0/meet-ffmpeg-a-working-conversion-method';

DismissableGlobalMessages::printDismissableMessage(
    'success',
    'Great news!<br><br> WebP Express 0.19.0 introduced a new conversion method: ffmpeg, which works on your system. ' .
        'You now have a conversion method that works! To start using it, you must go to settings and click save.',
    $msgId,
    [
        ['text' => 'Take me to the settings', 'redirect-to-settings' => true],
        ['text' => 'Dismiss'],
    ]
);
