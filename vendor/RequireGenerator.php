<?php

class RequireGenerator
{
    private static $required = [];

    private static function add_require_once($path)
    {
        if ($path[0] != '/') {
            $path = '/' . $path;
        }

        self::$required[] = $path;

        //echo 'require_once(__DIR__  . "' . $path . '");' . "\n";

    }
    public static function generate($def)
    {
        // load base classes, which are required for other classes
        foreach ($def['files'] as $file) {
            self::add_require_once($def['dir'] . '/' . $file);
        }

        // load dirs in defined order. No recursion.
        foreach ($def['dirs'] as $dir) {
            $dirAbs = __DIR__  . '/' . $def['dir'] . '/' . $dir;

            $files = glob($dirAbs . '/*.php');
            foreach ($files as $file) {
    //            echo $file . "\n";
                // only require files that begins with uppercase (A-Z)
                if (preg_match('/\/[A-Z][a-zA-Z]*\.php/', $file)) {
                    $file = str_replace(__DIR__, '', $file);
                    $file = str_replace('./', '', $file);
                    self::add_require_once($file);
                }
            }
        }

        // remove duplicates
        self::$required = array_unique(self::$required);

        // print
        foreach (self::$required as $path) {
            echo 'require_once(__DIR__  . "' . $path . '");' . "\n";
        }
    }
}
