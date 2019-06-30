<?php

namespace WebPConvertCloudService;

use \WebPConvertCloudService\WebPConvertCloudService;
use \WebPConvert\WebPConvert;

class Serve
{

    private static function configurationError($msg)
    {
        WebPConvertCloudService::exitWithError(WebPConvertCloudService::ERROR_CONFIGURATION, $msg);
    }

    private static function accessDenied($msg)
    {
        WebPConvertCloudService::exitWithError(WebPConvertCloudService::ERROR_ACCESS_DENIED, $msg);
    }

    private static function runtimeError($msg)
    {
        WebPConvertCloudService::exitWithError(WebPConvertCloudService::ERROR_RUNTIME, $msg);
    }

    public static function serve($options)
    {
        $uploaddir = $options['destination-dir'] ;

        if (!is_dir($uploaddir)) {
            if (!@mkdir($uploaddir, 0775, true)) {
                self::configurationError('Could not create folder for converted files: ' . $uploaddir);
            }
            @chmod($uploaddir, 0775);
        }

        /*
        if (!isset($_POST['hash'])) {
            self::accessDenied('Restricted access. Hash required, but missing');
        }*/

        if (!isset($_FILES['file'])) {
            self::runtimeError('No file was supplied');
        }
        if (!isset($_FILES['file']['error'])) {
            self::runtimeError('Invalid parameters');
        }

        if (is_array($_FILES['file']['error'])) {
            self::runtimeError('Cannot convert multiple files');
        }

        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                self::runtimeError('No file sent');
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                self::runtimeError('Exceeded filesize limit.');
                break;
            default:
                self::runtimeError('Unknown error.');
        }

        if ($_FILES['file']['size'] == 0) {
            self::accessDenied('File size is zero. Perhaps exceeded filesize limit?');
        }
        // Undefined | Multiple Files | $_FILES Corruption Attack
            // If this request falls under any of them, treat it invalid.
        /*if ($_FILES['file']['size'] > 1000000) {
            throw new RuntimeException('Exceeded filesize limit.');
        }*/

        // DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
        // Check MIME Type by yourself.
        if (function_exists('finfo_file') && (defined('FILEINFO_MIME_TYPE'))) {
            $r = finfo_open(FILEINFO_MIME_TYPE);
            if (false === $ext = array_search(
                finfo_file($r, $_FILES['file']['tmp_name']),
                array(
                    'jpg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                ),
                true
            )) {
                self::accessDenied('Invalid file format.');
            }
        } else {
            $ext = 'jpg';   // We set it to something, in case above fails.
        }

        $uploadfile = $uploaddir . '/' . sha1_file($_FILES['file']['tmp_name']) . '.' . $ext;
        //echo $uploadfile;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
            // File is valid, and was successfully uploaded

            $source = $uploadfile;

            /*
            if (!empty($password)) {
                $hash = md5(md5_file($source) . $password);

                if ($hash != $_POST['hash']) {
                    self::accessDenied('Wrong password.');
                }
            }
            */
            $destination = $uploadfile . '.webp';

            if (isset($_POST['options'])) {
                // Merge in options in $_POST, overwriting the webp-convert options in config
                $convertOptionsInPost = (array) json_decode($_POST['options'], true);
                $convertOptions = array_merge($options['webp-convert'], $convertOptionsInPost);
            } else {
                $convertOptions = $options['webp-convert'];
            }

            try {
                WebPConvert::convert($source, $destination, $convertOptions);
                header('Content-type: application/octet-stream');
                echo file_get_contents($destination);

                unlink($source);
                unlink($destination);
            } catch (\Exception $e) {
                echo 'Conversion failed!';
                echo $e->getMessage();
            }
        } else {
            // Possible file upload attack!
            self::configurationError('Failed to move uploaded file');

            //echo 'Failed to move uploaded file';
        }
    }
}
