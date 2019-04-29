<?php

//echo '<pre>'. print_r($_SERVER, 1) . '</pre>';

/*
Something like this is fine
[REQUEST_URI] => /wp-content/webp-express/capability-tests/request-uri-usable/test.php
[SCRIPT_NAME] => /wp-content/webp-express/capability-tests/request-uri-usable/test2.php

But this is indication of failure:
[REQUEST_URI] => /wp-content/webp-express/capability-tests/request-uri-usable/test.php
[SCRIPT_NAME] => /my_subdir/wp-content/webp-express/capability-tests/request-uri-usable/test2.php
*/

function stripFilename($dirName) {
    //echo 'dir:' . $dirName . '<br>';
    //echo 'pos: ' . strrpos($dirName, '/') . '<br>';
    return substr($dirName, 0, strrpos($dirName, '/'));

}
//echo stripFilename($_SERVER['REQUEST_URI']) . '<br>';
//echo stripFilename($_SERVER['SCRIPT_NAME']) . '<br>';

$equalDirs = (stripFilename($_SERVER['REQUEST_URI']) == stripFilename($_SERVER['SCRIPT_NAME']));

echo $equalDirs ? '1' : '0';


//echo preg_match('#$#')

//$_SERVER['REQUEST_URI'];
//$_SERVER['SCRIPT_NAME'];


/*
function getEnvPassedInRewriteRule($envName) {
    // Envirenment variables passed through the REWRITE module have "REWRITE_" as a prefix (in Apache, not Litespeed)
    // Multiple iterations causes multiple REWRITE_ prefixes, and we get many environment variables set.
    // We simply look for an environment variable that ends with what we are looking for.
    // (so make sure to make it unique)
    $len = strlen($envName);
    foreach ($_SERVER as $key => $item) {
        if (substr($key, -$len) == $envName) {
            return $item;
        }
    }
    return false;
}

//$result = getEnvPassedInRewriteRule('PASSTHROUGHENV');
*/
