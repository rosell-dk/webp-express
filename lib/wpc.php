<?php

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

//require_once( dirname( dirname( __FILE__ ) ) . '/wp-load.php' );

include_once __DIR__ . '/classes/Config.php';
use \WebPExpress\Config;

include_once __DIR__ . '/classes/Paths.php';
use \WebPExpress\Paths;

include_once __DIR__ . '/classes/State.php';
use \WebPExpress\State;

require_once __DIR__ . '/../vendor/autoload.php';
use WebPConvert\WebPConvert;


const ERROR_SERVER_SETUP = 0;
const ERROR_NOT_ALLOWED = 1;
const ERROR_RUNTIME = 2;

$action = (isset($_POST['action']) ? $_POST['action'] : 'convert');

if ($action == 'request-access') {
    if (!(State::getState('listening', false))) {
        exitWithError(ERROR_NOT_ALLOWED, 'Server is not listening for requests');
    } else {
        State::setState('request', [
            'label' => isset($_POST['label']) ? $_POST['label'] : 'unknown',
            'key' => isset($_POST['key']) ? $_POST['key'] : 'prut2',
            'ip' => $_SERVER['REMOTE_ADDR'],
            'timestamp' => time(),
        ]);
        $returnObject = [
            'success' => 1,
        ];
        echo json_encode($returnObject);
        die();
    }
}



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

$whitelisted = false;
$password = '';

/**
 *   Note about the whitelist:
 *   It is not unspoofable. But it does not have to be either.
 *   The extra layer of "security" is added to avoid massive misuse in case that the password
 *   is leaked. Massive misuse would be if the password where to spread in internet forums, and
 *   anyone could easily use it. With the whitelist, the password is not enough, you would also
 *   be needing to know an entry on the whitelist. This could of course also be leaked. But you
 *   would also need to do the spoofing. This additional step is probably more than most people
 *   would bother to go through.
 */
function testWhitelistEntry($sitePattern) {
    if ($sitePattern == '*') {
        return true;
    }
    $regEx = '/^' . str_replace('*', '.*', $sitePattern) . '$/';

    $ip = $_SERVER['REMOTE_ADDR'];
    if (preg_match($regEx, $ip)) {
        return true;
    }

    // If sitePattern looks like a full IP pattern, exit now,
    // so the other methods cant be misused with spoofing.
    // ^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$
    if (preg_match('/^\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}$/', $sitePattern)) {
        return false;
    }
    // Also test a nearly full IP pattern.
    // As domain names may now start with numbers, theoretically, we could have a domain
    // called 123.127.com, and the user might also have 123.127.net, and therefore add
    // a rule '123.127.*'.
    if (preg_match('/^\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.?\\*$/', $sitePattern)) {
        return false;
    }

    // ^(\d{1,3}(\.)?){1,4}\*?$  works in regexr.com, but not here...

    if (isset($_SERVER['REMOTE_HOST'])) {
        // REMOTE_HOST is only available if Apache has been configured
        // with HostnameLookups = On (in apache.conf).
        // It seldom is, as it is not the default, and it is expensive,as at least one
        // DNS lookup will be made per request.
        // Anyway, here we are, and we have it.
        if (preg_match($regEx, $_SERVER['REMOTE_HOST'])) {
            return true;
        }
    }

    // I know, it can easily be spoofed, simply by changing the source code.
    // However, encrypting it would not help, as the bandit would already be
    // knowing the secret.
    // - And $_SERVER['REMOTE_HOST'] is seldom available, and often misleading
    // on shared hosts
    if (isset($_POST['servername'])) {
        $domain = $_POST['servername'];
        if (preg_match($regEx, $_POST['servername'])) {
            return true;
        }
    }

    return false;
}

foreach ($wpcOptions['whitelist'] as $entry) {
    if (testWhitelistEntry($entry['site'])) {
        $whitelisted = true;
        $password = $entry['password'];
        break;
    }
}

if (!$whitelisted) {
    if (isset($_SERVER['REMOTE_HOST']) && (!empty($_SERVER['REMOTE_HOST']))) {
        if (isset($_POST['servername'])) {
            exitWithError(ERROR_NOT_ALLOWED, 'Neither your domain (' . $_POST['servername'] . '), the domain of your webhost (' . $_SERVER['REMOTE_HOST'] . ') or your IP (' . $_SERVER['REMOTE_ADDR'] . ') is on the whitelist');
        } else {
            exitWithError(ERROR_NOT_ALLOWED, 'Neither the domain of your webhost (' . $_SERVER['REMOTE_HOST'] . ') or your IP (' . $_SERVER['REMOTE_ADDR'] . ') is on the whitelist');
        }
    } else {
        if (isset($_POST['servername'])) {
            exitWithError(ERROR_NOT_ALLOWED, 'Neither your domain (' . $_POST['servername'] . ') or your IP (' . $_SERVER['REMOTE_ADDR'] . ') is on the whitelist');
        } else {
            exitWithError(ERROR_NOT_ALLOWED, 'Your IP (' . $_SERVER['REMOTE_ADDR'] . ') is not on the whitelist');
        }
    }
}

$uploaddir = Paths::getCacheDirAbs() . '/wpc';

if (!is_dir($uploaddir)) {
    if (!@mkdir($uploaddir, 0775, true)) {
        exitWithError(ERROR_SERVER_SETUP, 'Could not create folder for converted files: ' . $uploaddir);
    }
    @chmod($uploaddir, 0775);
}


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

    if (!empty($password)) {
        $hash = md5(md5_file($source) . $password);

        if ($hash != $_POST['hash']) {
            exitWithError(ERROR_NOT_ALLOWED, 'Wrong password.');
        }
    }

    $destination = $uploadfile . '.webp';

    // Merge in options in $_POST, overwriting those in config.yaml
    $convertOptionsInPost = (array) json_decode($_POST['options']);
    $options = Config::generateWodOptionsFromConfigObj($config);
    $convertOptions = array_merge($options, $convertOptionsInPost);

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
    exitWithError(ERROR_SERVER_SETUP, 'Failed to move uploaded file');

    //echo 'Failed to move uploaded file';
}
