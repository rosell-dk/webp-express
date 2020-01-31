<?php

//echo '<pre>'. print_r($_SERVER, 1) . '</pre>';

if (isset($_SERVER['HTTP_USER_AGENT'])) {
    echo  $_SERVER['HTTP_USER_AGENT'] == 'webp-express-test' ? 1 : 0;
} else {
	echo 0;
}
