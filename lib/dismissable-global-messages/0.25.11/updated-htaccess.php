<?php

namespace WebPExpress;

/*
DismissableGlobalMessages::printDismissableMessage(
    'info',
    'WebP Express has regenerated .htaccess files, fixing an error introduced in 0.25.10',
    '0.25.11/updated-htaccess',
    [
        ['text' => 'Ok'],
    ]
);
*/

// The message is no longer relevant, as it is no longer neccessary
// So dismiss it, so this code is not run again
DismissableGlobalMessages::dismissMessage('0.25.11/updated-htaccess');
