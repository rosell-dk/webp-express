<?php

namespace WebPExpress;

// introduced in 0.14.0 (migrate 9)
DismissableMessages::printDismissableMessage(
    'info',
    'WebP Express 0.15 introduced a new "scope" setting which determines which folders that WebP Express ' .
        'operates in. It has been set to work on "index" (any images in the whole install, including wp-adimn) ' .
        'in order not to change the behaviour. However, I would usually recommend a more limitted scope, ie. "Uploads and Themes".',
    '0.15.0/new-scope-setting-index',
    'Got it!'
);
