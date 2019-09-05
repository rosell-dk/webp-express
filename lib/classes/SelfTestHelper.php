<?php

namespace WebPExpress;

use \WebPExpress\Paths;

class SelfTestHelper
{

    public static function deleteTestImagesInUploadFolder()
    {
        $destDir = Paths::getAbsDirById('uploads');
        foreach (glob($destDir . DIRECTORY_SEPARATOR . "webp-express-test-image-*") as $filename) {
            unlink($filename);
        }
    }


    public static function copyFile($source, $destination)
    {
        $result = [];
        if (@copy($source, $destination)) {
            return [true, $result];
        } else {
            $result[] = 'Failed to copy *' . $source . '* to *' . $destination . '*';
            if (!@file_exists($source)) {
                $result[] = 'The source file was not found';
            } else {
                if (!@file_exists(dirname($destination))) {
                    $result[] = 'The destination folder does not exist!';
                } else {
                    $result[] = 'This is probably a permission issue. Check that your webserver has permission to ' .
                        'write files in the upload directory (*' . dirname($destination) . '*)';
                }
            }
            return [false, $result];
        }
    }

    public static function randomDigitsAndLetters($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function copyTestImageToUploadFolder($imageType = 'jpeg')
    {
        $result = [];
        switch ($imageType) {
            case 'jpeg':
                $fileNameToCopy = 'focus.jpg';
                break;
            case 'png':
                $fileNameToCopy = 'alphatest.png';
                break;
        }
        $testSource = Paths::getPluginDirAbs() . '/webp-express/test/' . $fileNameToCopy;
        $filenameOfDestination = 'webp-express-test-image-' . self::randomDigitsAndLetters(6) . '.' . $imageType;
        $result[] = 'Copying ' . strtoupper($imageType) . ' to upload folder (*' . $filenameOfDestination . '*)';

        $destDir = Paths::getAbsDirById('uploads');
        $destination = $destDir . '/' . $filenameOfDestination;

        list($success, $errors) = self::copyFile($testSource, $destination);
        if (!$success) {
            $result[count($result) - 1] .= '. FAILED';
            $result = array_merge($result, $errors);
            return [$result, false, ''];
        } else {
            $result[count($result) - 1] .= '. ok!';
//            $result[] = 'We now have a file here:';
//            $result[] = '*' . $destination . '*';
        }
        return [$result, true, $filenameOfDestination];
    }

    public static function copyDummyWebPToCacheFolderUpload($destinationFolder, $destinationExtension, $destinationStructure, $destinationFileNameNoExt, $imageType = 'jpeg')
    {
        $result = [];
        $dummyWebP = Paths::getPluginDirAbs() . '/webp-express/test/test.jpg.webp';

        $result[] = 'Copying dummy webp to the cache root for uploads';
        $destDir = Paths::getCacheDirForImageRoot($destinationFolder, $destinationStructure, 'uploads');
        if (!file_exists($destDir)) {
            $result[] = 'The folder did not exist. Creating folder at: ' . $destinationDir;
            if (!mkdir($destDir, 0777, true)) {
                $result[] = 'Failed creating folder!';
                return [$result, false, ''];
            }
        }
        $filenameOfDestination = $destinationFileNameNoExt . ($destinationExtension == 'append' ? '.' . $imageType : '') . '.webp';
        $destination = $destDir . '/' . $filenameOfDestination;

        list($success, $errors) = self::copyFile($dummyWebP, $destination);
        if (!$success) {
            $result[count($result) - 1] .= '. FAILED';
            $result = array_merge($result, $errors);
            return [$result, false, ''];
        } else {
            $result[count($result) - 1] .= '. ok!';
            $result[] = 'We now have a webp file stored here:';
            $result[] = '*' . $destination . '*';
            $result[] = '';
        }
        return [$result, true, $destination];
    }

    public static function remoteGet($requestUrl, $args = [])
    {
        $result = [];
        $return = wp_remote_get($requestUrl, $args);
        if (is_wp_error($return)) {
            $result[] = 'The remote request errored!';
            $result[] = 'Request URL: ' . $requestUrl;
            return [false, $result];
        }
        if ($return['response']['code'] != '200') {
            //$result[count($result) - 1] .= '. FAILED';
            $result[] = 'Unexpected response: ' . $return['response']['code'] . ' ' . $return['response']['message'];
            $result[] = 'Request URL: ' . $requestUrl;
            return [false, $result];
        }
        return [true, $result, $return['headers']];
    }

    public static function printHeaders($headers)
    {
        $result = [];
        $result[] = '';
        $result[] = 'Response headers:';
        foreach ($headers as $headerName => $headerValue) {
            if (gettype($headerValue) == 'array') {
                foreach ($headerValue as $i => $value) {
                    $result[] = '- ' . $headerName . ': ' . $value;
                }
            } else {
                $result[] = '- ' . $headerName . ': ' . $headerValue;
            }

        }
        $result[] = '';
        return $result;
    }

}
