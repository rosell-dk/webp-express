<?php

namespace WebPExpress;


include_once __DIR__ . '/../classes/FileHelper.php';
use \WebPExpress\FileHelper;

include_once __DIR__ . '/../classes/Paths.php';
use \WebPExpress\Paths;

include_once __DIR__ . '/../classes/PathHelper.php';
use \WebPExpress\PathHelper;

include_once __DIR__ . '/../classes/Messenger.php';
use \WebPExpress\Messenger;


function webpexpress_migrate4() {
/*
    Messenger::addMessage(
        'info',
        '<i>Welcome to WebP Express 0.10.0:</i>'
    );*/

    // PSST: When creating new migration files, remember to update WEBPEXPRESS_MIGRATION_VERSION in admin.php
    //update_option('webp-express-migration-version', '3');

}

webpexpress_migrate4();
