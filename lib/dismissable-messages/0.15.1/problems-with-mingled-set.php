<?php

namespace WebPExpress;

DismissableMessages::printDismissableMessage(
    'error',
    'Sorry, due to a bug, the combination of destination folder: mingled and destination extension: set ' .
        'does not currently work. Please change the settings. ' .
        'I shall fix this soon!',
    '0.15.1/problems-with-mingled-set',
    'Got it!'
);
