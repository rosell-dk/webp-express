<?php

function autoloadWebPConvert()
{
    // load base class, which are required for other classes
    require_once(__DIR__  . '/Exceptions/WebPConvertBaseException.php');

    $dirsToAutoload = [
        '.',
        'Converters',
        'Exceptions',
        'Converters/Exceptions',
    ];
    foreach ($dirsToAutoload as $dir) {
        $dirAbs = __DIR__  . '/' . $dir;

        // If directory has its own autoload.php, use that (but ignore autoload in current folder)
        if ((file_exists($dirAbs  . '/autoload.php') && ($dir != '.'))) {
            require_once($dirAbs  . '/autoload.php');
        } else {
            $files = glob($dirAbs . '/*.php');
            foreach ($files as $file) {
                // only require files that begins with uppercase (A-Z)
                if (preg_match('/\/[A-Z][a-zA-Z]*\.php/', $file)) {
                    require_once($file);
                }
            }
        }
    }
}
autoloadWebPConvert();
