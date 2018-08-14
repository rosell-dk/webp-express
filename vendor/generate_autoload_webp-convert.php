<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once('RequireGenerator.php');

use RequireGenerator;

RequireGenerator::generate([
    'dir' => 'webp-convert',
    'files' => [
        'Exceptions/WebPConvertBaseException.php',
        'Loggers/BaseLogger.php'
    ],
    'dirs' => [
        '.',
        'Converters',
        'Exceptions',
        'Converters/Exceptions',
        'Loggers',
    ]
]);
