<?php

/**
 *  Get environment variable set with mod_rewrite module
 *  Return false if the environment variable isn't found
 */
function getEnvPassedInRewriteRule($envName) {
    // Envirenment variables passed through the REWRITE module have "REWRITE_" as a prefix (in Apache, not Litespeed, if I recall correctly)
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

$result = getEnvPassedInRewriteRule('PASSTHROUGHENV');
if ($result === false) {
    echo '0';
    exit;
}
echo ($result == $_SERVER['DOCUMENT_ROOT'] ? '1' : '0');
