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

        self::$required = [];

        // load base classes, which are required for other classes
        foreach ($def['files'] as $file) {
            self::add_require_once($def['dir'] . '/' . $file);
        }

        // load dirs in defined order. No recursion.
        foreach ($def['dirs'] as $dir) {
            $dirAbs = $def['require_dir_relative_to_this_script'] . '/' . $dir;
            $files = glob($dirAbs . '/*.php');
            foreach ($files as $file) {

                // remove ie '../vendor/'
                $file = str_replace($def['require_dir_relative_to_this_script'], '', $file);

                $file = str_replace('./', '', $file);
                $file = $def['dir'] . $file;
                self::add_require_once($file);

                // only require files that begins with uppercase (A-Z) todo. this one does not take subfolders into account: (if (preg_match('/\/[A-Z][a-zA-Z]*\.php/', $file)) {
                    //$file = str_replace(__DIR__, '', $file);
            }
        }

        // remove duplicates
        self::$required = array_unique(self::$required);

        // print
        foreach (self::$required as $path) {
//            echo 'require_once(__DIR__  . "' . $path . '");' . "\n";
        }

        // save
        $filename = __DIR__ . '/' . $def['output'];
        $file = fopen($filename, "w");
        fwrite($file, "<?php\n");
        foreach (self::$required as $path) {
            fwrite($file, 'require_once(__DIR__  . "' . $path . '");' . "\n");
        }
        fclose($file);
        echo 'saved file:' . $filename . "\n";

    }
}
