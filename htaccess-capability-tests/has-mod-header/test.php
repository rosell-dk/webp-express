<?php

//echo '<pre>'. print_r($_SERVER, 1) . '</pre>';

if (isset($_SERVER['HTTP_HEADERFORTEST'])) {
    echo ($_SERVER['HTTP_HEADERFORTEST'] == 'test' ? 1 : 0);
    exit;
}
echo '0';
