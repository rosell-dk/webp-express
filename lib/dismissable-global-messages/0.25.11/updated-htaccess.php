<?php

namespace WebPExpress;


DismissableGlobalMessages::printDismissableMessage(
    'info',
    'WebP Express has regenerated .htaccess files, fixing an error introduced in 0.25.10',
    '0.25.11/updated-htaccess',
    [
        ['text' => 'Ok'],
    ]
);
