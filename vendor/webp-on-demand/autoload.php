<?php

function autoloadWebOnDemand()
{
    $dirsToAutoload = [
        '../webp-convert-and-serve',
        '.',
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
autoloadWebOnDemand();
