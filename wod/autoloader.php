<?php
define('WOD_DIR', __DIR__);
function webpexpress_autoloader($class) {
    if (strpos($class, 'WebPExpress\\') === 0) {
        require_once WOD_DIR . '/../lib/classes/' . substr($class, 12) . '.php';
    }
}
spl_autoload_register('webpexpress_autoloader');
