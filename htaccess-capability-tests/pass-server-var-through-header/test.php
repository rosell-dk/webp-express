<?php

//echo '<pre>'. print_r($_SERVER, 1) . '</pre>';

if (isset($_SERVER['HTTP_PASSTHROUGHHEADER'])) {
    echo ($_SERVER['HTTP_PASSTHROUGHHEADER'] == $_SERVER['DOCUMENT_ROOT'] ? 1 : 0);
    exit;
}
echo '0';
