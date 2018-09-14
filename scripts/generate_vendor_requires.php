<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once('RequireGenerator.php');

//use RequireGenerator;

RequireGenerator::generate([
    'require_dir_relative_to_this_script' => '../vendor/webp-convert',
    'dir' => 'webp-convert',    // the require statements will start with __DIR__ plus this dir
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
    ],
    'output' => '../vendor/require-webp-convert.php'
]);


RequireGenerator::generate([
    'require_dir_relative_to_this_script' => '../vendor/webp-convert-and-serve',
    'dir' => 'webp-convert-and-serve',
    'files' => [
        '../require-webp-convert.php',
    ],
    'dirs' => [
        '.',
    ],
    'output' => '../vendor/require-webp-convert-and-serve.php'
]);

RequireGenerator::generate([
    'require_dir_relative_to_this_script' => '../vendor/webp-on-demand',
    'dir' => 'webp-on-demand',
    'files' => [
        '../require-webp-convert-and-serve.php',
    ],
    'dirs' => [
        '.',
    ],
    'output' => '../vendor/require-webp-on-demand.php'
]);
