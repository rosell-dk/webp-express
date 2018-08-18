<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

//require 'webp-on-demand/vendor/autoload.php';
//require 'vendor/webp-on-demand/autoload.php';
require 'vendor/require-webp-on-demand.php';

use WebPOnDemand\WebPOnDemand;


$status = WebPOnDemand::serve(__DIR__);
if ($status < 0) {
    // Conversion failed.
    // you could message your application about the problem here...
}
