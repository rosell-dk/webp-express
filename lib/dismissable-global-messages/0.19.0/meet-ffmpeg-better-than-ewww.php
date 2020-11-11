<?php

namespace WebPExpress;

$msgId = '0.19.0/meet-ffmpeg-better-than-ewww';

DismissableGlobalMessages::printDismissableMessage(
    'info',
    'WebP Express 0.19.0 introduced a new conversion method: ffmpeg. ' .
        'You may consider moving it above ewww, as ffmpeg supports the "Auto" WebP encoding option ' .
        '(encoding to both lossy and lossless and then selecting the smallest)',
    $msgId,
    [
        ['text' => 'Take me to the settings', 'redirect-to-settings' => true],
        ['text' => 'Dismiss'],
    ]
);
