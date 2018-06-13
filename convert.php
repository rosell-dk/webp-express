<?php

//require 'webp-on-demand/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 'On');


//require 'vendor/webp-convert-and-serve/autoload.php';
require 'vendor/webp-on-demand/autoload.php';

//echo '<br>done';

//require '../webp-convert/WebPConvert.php';


/*use WebPConvert\WebPConvert;

$source = __DIR__ . '/test/test.jpg';
$destination = __DIR__ . '/test/test.jpg.webp';

// .. fire up WebP conversion
$success = WebPConvert::convert($source, $destination, array(
    'quality' => 90,
    // more options available!
));


//$status = WebPOnDemand::serve(__DIR__);

//echo 'hi';
*/


use WebPOnDemand\WebPOnDemand;

$status = WebPOnDemand::serve(__DIR__);
if ($status < 0) {
    // Conversion failed.
    // you could message your application about the problem here...
}
