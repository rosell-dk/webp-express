<?php

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

//require_once( dirname( dirname( __FILE__ ) ) . '/wp-load.php' );

include_once __DIR__ . '/classes/Config.php';
use \WebPExpress\Config;

include_once __DIR__ . '/classes/Paths.php';
use \WebPExpress\Paths;

//require 'vendor/autoload.php';
//use WebPConvert\WebPConvert;

const ERROR_SERVER_SETUP = 0;
const ERROR_NOT_ALLOWED = 1;
const ERROR_RUNTIME = 2;


function exitWithError($errorCode, $msg)
{
    $returnObject = [
        'success' => 0,
        'errorCode' => $errorCode,
        'errorMessage' => $msg,
    ];
    echo json_encode($returnObject);
    exit;
}

$config = Config::loadConfig();
if ($config === false) {
    if (Config::isConfigFileThere()) {
        exitWithError(ERROR_SERVER_SETUP, 'config file could not be loaded.');
    } else {
        exitWithError(ERROR_SERVER_SETUP, 'config file could not be loaded (its not there): ' . Paths::getConfigFileName());
    }
}

if (!isset($config['wpc'])) {
    exitWithError(ERROR_SERVER_SETUP, 'cloud service is not configured');
}

$wpcOptions = $config['wpc'];

if (!isset($wpcOptions['enabled']) || $wpcOptions['enabled'] == false) {
    exitWithError(ERROR_SERVER_SETUP, 'cloud service is not enabled');
}


if (!isset($wpcOptions['access']['allowed-ips']) && count($wpcOptions['access']['allowed-ips']) > 0) {
    $ipCheckPassed = false;
    foreach ($wpcOptions['access']['allowed-ips'] as $ip) {
        if ($ip == $_SERVER['REMOTE_ADDR']) {
            $ipCheckPassed = true;
            break;
        }
    }
    if (!$ipCheckPassed) {
        exitWithError(ERROR_NOT_ALLOWED, 'Restricted access. Not on IP whitelist');
    }
}

/*
if (isset($wpcOptions['access']['allowed-ips']) && count($wpcOptions['access']['allowed-ips']) > 0) {
    $ipCheckPassed = false;
    foreach ($wpcOptions['access']['allowed-ips'] as $ip) {
        if ($ip == $_SERVER['REMOTE_ADDR']) {
            $ipCheckPassed = true;
            break;
        }
    }
    if (!$ipCheckPassed) {
        exitWithError(ERROR_NOT_ALLOWED, 'Restricted access. Not on IP whitelist');
    }
}

if (isset($wpcOptions['access']['allowed-hosts']) && count($wpcOptions['access']['allowed-hosts']) > 0) {
    $h = $_SERVER['REMOTE_HOST'];
    if ($h == '') {
        // Alternatively, we could catch the notice...
        exitWithError(ERROR_SERVER_SETUP, 'WPC is configured with allowed-hosts option. But the server is not set up to resolve host names. For example in Apache you will need HostnameLookups On inside httpd.conf. See also PHP documentation on gethostbyaddr().');
    }
    $hostCheckPassed = false;
    foreach ($wpcOptions['access']['allowed-hosts'] as $hostName) {
        if ($hostName == $_SERVER['REMOTE_HOST']) {
            $hostCheckPassed = true;
            break;
        }
    }
    if (!$hostCheckPassed) {
        exitWithError(ERROR_NOT_ALLOWED, 'Restricted access. Hostname is not on whitelist');
    }
}
*/

$uploaddir = Paths::getCacheDirAbs() . '/wpc';

if (!isset($_POST['hash'])) {
    exitWithError(ERROR_NOT_ALLOWED, 'Restricted access. Hash required, but missing');
}

if (!isset($_FILES['file']['error'])) {
    exitWithError(ERROR_RUNTIME, 'Invalid parameters');
}

if (is_array($_FILES['file']['error'])) {
    exitWithError(ERROR_RUNTIME, 'Cannot convert multiple files');
}

switch ($_FILES['file']['error']) {
    case UPLOAD_ERR_OK:
        break;
    case UPLOAD_ERR_NO_FILE:
        exitWithError(ERROR_RUNTIME, 'No file sent');
    case UPLOAD_ERR_INI_SIZE:
    case UPLOAD_ERR_FORM_SIZE:
        exitWithError(ERROR_RUNTIME, 'Exceeded filesize limit.');
    default:
        exitWithError(ERROR_RUNTIME, 'Unknown error.');
}

if ($_FILES['file']['size'] == 0) {
    exitWithError(ERROR_NOT_ALLOWED, 'File size is zero. Perhaps exceeded filesize limit?');
}
// Undefined | Multiple Files | $_FILES Corruption Attack
    // If this request falls under any of them, treat it invalid.
/*if ($_FILES['file']['size'] > 1000000) {
    throw new RuntimeException('Exceeded filesize limit.');
}*/

// DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
// Check MIME Type by yourself.
$finfo = new finfo(FILEINFO_MIME_TYPE);
if (false === $ext = array_search(
    $finfo->file($_FILES['file']['tmp_name']),
    array(
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
    ),
    true
)) {
    exitWithError(ERROR_NOT_ALLOWED, 'Invalid file format.');
}

$uploadfile = $uploaddir . '/' . sha1_file($_FILES['file']['tmp_name']) . '.' . $ext;
//echo $uploadfile;
if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
    // File is valid, and was successfully uploaded

    $source = $uploadfile;


    if (isset($wpcOptions['access']['secret'])) {
        $hash = md5(md5_file($source) . $wpcOptions['access']['secret']);

        if ($hash != $_POST['hash']) {
            exitWithError(ERROR_NOT_ALLOWED, 'Hash is incorrect. Perhaps the secrets does not match?. Hash was:' . $_POST['hash']);
        }
    }

    $destination = $uploadfile . '.webp';

    // Merge in options in $_POST, overwriting those in config.yaml
    $convertOptionsInPost = (array) json_decode($_POST['options']);
    $convertOptions = array_merge($wpcOptions['webp-convert'], $convertOptionsInPost);

    try {
        if (WebPConvert::convert($source, $destination, $convertOptions)) {
            header('Content-type: application/octet-stream');
            echo file_get_contents($destination);

            unlink($source);
            unlink($destination);
        } else {
            echo 'no converters could convert the image';
        }
    } catch (\Exception $e) {
        echo 'failed!';
        echo $e->getMessage();
    }
} else {
    // Possible file upload attack!
    echo 'Failed to move uploaded file';
}
