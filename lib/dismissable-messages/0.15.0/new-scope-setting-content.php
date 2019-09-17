<?php
namespace WebPExpress;

// introduced in 0.14.0 (migrate 9)
DismissableMessages::printDismissableMessage(
    'info',
    'WebP Express 0.15 introduced a new "scope" setting which determines which folders that WebP Express ' .
        'operates in. It has been set to "All content" in order not to change behaviour. ' .
        'However, I would usually recommend limitting scope to "Uploads and Themes".',
    '0.15.0/new-scope-setting-content',
    'Got it!'
);
