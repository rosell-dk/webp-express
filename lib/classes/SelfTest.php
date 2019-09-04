<?php

namespace WebPExpress;

use \WebPExpress\Paths;

class SelfTest
{

    private static $next;

    public static function systemInfo()
    {
        $result = [];
        $result[] = '# System info';
        $result[] = 'PHP version: ' . phpversion();
        $result[] = 'OS: ' . PHP_OS;
        $result[] = 'Server software: ' . $_SERVER["SERVER_SOFTWARE"];
        $result[] = 'Document Root: ' . Paths::docRootStatusText();

        self::$next = 'configInfo';
        return $result;
    }

    public static function configInfo()
    {
        $config = Config::loadConfigAndFix(false);
        $result[] = '# Configuration info';
        $result[] = 'Destination folder: ' . $config['destination-folder'];
        $result[] = 'Destination extension: ' . $config['destination-extension'];
        $result[] = 'Destination structure: ' . $config['destination-structure'];
        //$result[] = 'Image types: ' . ;
        $result[] = '';
        $result[] = 'To view all configuration, take a look at the config file, which is stored in *' . Paths::getConfigFileName() . '*';

        self::$next = 'capabilityTests';

        return $result;
    }

    private static function trueFalseNullString($var)
    {
        if ($var === true) {
            return 'yes';
        }
        if ($var === false) {
            return 'no';
        }
        return 'could not be determined';
    }

    public static function capabilityTests()
    {
        $config = Config::loadConfigAndFix(false);
        $capTests = $config['base-htaccess-on-these-capability-tests'];

        $result[] = '# .htaccess capability tests';
        $result[] = 'Exactly what you can do in a .htaccess depends on the server setup. WebP Express ' .
            'makes some blind tests to verify if a certain feature in fact works. This is done by creating ' .
            'test files (.htaccess files and php files) in a dir inside the content dir and running these. ' .
            'These test results are used when creating the rewrite rules. Here are the results:';

        $result[] = '';
        $result[] = '- mod_header working?: ' . self::trueFalseNullString($capTests['modHeaderWorking']);
        /*$result[] = '- pass variable from .htaccess to script through header working?: ' .
            self::trueFalseNullString($capTests['passThroughHeaderWorking']);*/
        $result[] = '- passing variables from .htaccess to PHP script through environment variable working?: ' . self::trueFalseNullString($capTests['passThroughEnvWorking']);

        self::$next = 'done';
        return $result;
    }

    public static function redirectTests()
    {
        return self::redirectToExisting();
        /*
        $result = [];
        $result[] = '# Redirection tests';
        $modRewriteWorking = CapabilityTest::modRewriteWorking();
        $modHeaderWorking = CapabilityTest::modHeaderWorking();

        if (($modRewriteWorking === false) && ($modHeaderWorking)) {
            //$result[] = 'mod_rewrite is not working';

            if (stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') !== false) {

                $result[] = 'You are on Nginx and the rules that WebP Express stores in the .htaccess files does not ' .
                    'have any effect. '

            }
            // if (stripos($_SERVER["SERVER_SOFTWARE"], 'apache') !== false && stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') === false) {

        }

        return [$result, 'done'];*/
    }

    private static function copyFile($source, $destination)
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
    private static function copyTestImageToUploadFolder($imageType = 'jpeg')
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
        $result[] = 'Copying test image to upload folder (' . $imageType . ')';
        $testSource = Paths::getPluginDirAbs() . '/webp-express/test/' . $fileNameToCopy;
        $filenameOfDestination = 'webp-express-test-image.' . $imageType;
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

    private static function copyDummyWebPToCacheFolderUpload($destinationFolder, $destinationExtension, $destinationStructure, $imageType = 'jpeg')
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
        $filenameOfDestination = 'webp-express-test-image' . ($destinationExtension == 'append' ? '.' . $imageType : '') . '.webp';
        $destination = $destDir . '/' . $filenameOfDestination;

        list($success, $errors) = self::copyFile($dummyWebP, $destination);
        if (!$success) {
            $result[count($result) - 1] .= '. FAILED';
            $result = array_merge($result, $errors);
            return [$result, false, ''];
        } else {
            $result[count($result) - 1] .= '. ok!';
            $result[] = 'We now have a file here:';
            $result[] = '*' . $destination . '*';
            $result[] = '';
        }
        return [$result, true, $destination];
    }

    private static function remoteGet($requestUrl, $args = [])
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

    public static function redirectToExisting()
    {
        // TODO: Delete test images after test
        // TODO: Also test if we get the Vary: Accept header
        // TODO: If options have never been saved (and .htaccess never saved), tell the user so
        // TODO: If no cache control header is set, advise the user to set it
        // TODO: Add notice that only images placed in uploads have been tested. And no PNG either (yet)

        self::$next = 'done';

        $config = Config::loadConfigAndFix(false);
        $result = [];

        //$result[] = '*hello* with *you* and **you**. ok! FAILED';
        $result[] = '# Testing redirection to existing webp';
        //$result[] = 'This test examines image responses "from the outside".';

        if (!$config['redirect-to-existing-in-htaccess']) {
            $result[] = 'Turned off, nothing to test';
            return $result;
        }

        if ($config['image-types'] == 0) {
            $result[] = 'No image types have been activated, nothing to test';
            return $result;
        }

        if (!($config['image-types'] & 1)) {
            $result[] = 'Sorry, the test is currently only designed to work with jpeg but you have not enabled jpeg. Exiting';
            return $result;
        }
        if ($config['image-types'] & 1) {

            // Copy test image (jpeg)
            list($subResult, $success, $sourceFileName) = self::copyTestImageToUploadFolder('jpeg');
            $result = array_merge($result, $subResult);
            if (!$success) {
                $result[] = 'The test cannot be completed';
                return $result;
            }

            // Copy dummy webp
            list($subResult, $success, $destinationFile) = self::copyDummyWebPToCacheFolderUpload(
                $config['destination-folder'],
                $config['destination-extension'],
                $config['destination-structure'],
                'jpeg'
            );
            $result = array_merge($result, $subResult);
            if (!$success) {
                $result[] = 'The test cannot be completed';
                return $result;
            }

            $requestUrl = Paths::getUploadUrl() . '/' . $sourceFileName;
            $result[] = 'Making a HTTP request for the test image (pretending to be a client that supports webp, by setting the "Accept" header to "image/webp")';
            $requestArgs = [
                'headers' => [
                    'ACCEPT' => 'image/webp'
                ]
            ];
            list($success, $errors, $headers) = self::remoteGet($requestUrl, $requestArgs);

            if (!$success) {
                $result[count($result) - 1] .= '. FAILED';
                $result = array_merge($result, $errors);
                $result[] = 'The test cannot be completed';
                //$result[count($result) - 1] .= '. FAILED';
                return $result;
            }
            //$result[count($result) - 1] .= '. ok!';
            $result[] = '*' . $requestUrl . '*';

            $result[] = '';
            $result[] = 'Response headers:';
            foreach ($headers as $headerName => $headerValue) {
                $result[] = '- ' . $headerName . ': ' . $headerValue;
            }
            $result[] = '';

            if (!isset($headers['content-type'])) {
                $result[] = 'Bummer. There is no "content-type" response header. The test FAILED';
                return $result;
            }

            if ($headers['content-type'] == 'image/jpeg') {
                $result[] = 'Bummer. As the "content-type" header reveals, we got the jpeg. So the redirection to the webp is not working.';
                $result[] = 'The test FAILED.';
                return $result;
            }

            if ($headers['content-type'] != 'image/webp') {
                $result[] = 'Bummer. As the "content-type" header reveals, we did not get a webp' .
                    'Surprisingly we got: "' . $headers['content-type'] . '"';
                $result[] = 'The test FAILED.';
            }
            $result[] = 'Alrighty. We got a webp. Just what we wanted. Great!';
            $result[] = '';
            $result[] = 'Now lets check that browsers *not* supporting webp gets the jpeg';
            $result[] = 'Making a HTTP request for the test image (without setting the "Accept" header)';
            list($success, $errors, $headers) = self::remoteGet($requestUrl);

            if (!$success) {
                $result[count($result) - 1] .= '. FAILED';
                $result = array_merge($result, $errors);
                $result[] = 'The test cannot be completed';
                //$result[count($result) - 1] .= '. FAILED';
                return $result;
            }
            //$result[count($result) - 1] .= '. ok!';
            $result[] = '*' . $requestUrl . '*';

            $result[] = '';
            $result[] = 'Response headers:';
            foreach ($headers as $headerName => $headerValue) {
                $result[] = '- ' . $headerName . ': ' . $headerValue;
            }
            $result[] = '';

            if (!isset($headers['content-type'])) {
                $result[] = 'Bummer. There is no "content-type" response header. The test FAILED';
                return $result;
            }

            if ($headers['content-type'] == 'image/webp') {
                $result[] = 'Bummer. As the "content-type" header reveals, we got the webp. ' .
                    'So even browsers not supporting webp gets webp. Not good!';
                $result[] = 'The test FAILED.';
                return $result;
            }

            if ($headers['content-type'] != 'image/jpeg') {
                $result[] = 'Bummer. As the "content-type" header reveals, we did not get the jpeg' .
                    'Surprisingly we got: "' . $headers['content-type'] . '"';
                $result[] = 'The test FAILED.';
            }
            $result[] = 'Alrighty. We got the jpeg. Everything is great.';

        }
        /*
        $result[] = 'Copying test image to upload folder';
        $testSourceJpg = Paths::getPluginDirAbs() . "/webp-express/test/focus.jpg";
        $testDestinationJpg = Paths::getAbsDirById('uploads') . "/webp-express-test-image.jpg";

        if (!@copy($testSourceJpg, $testDestinationJpg)) {
            $result[count($result) - 1] .= '. FAILED';
        } else {
            $result[count($result) - 1] .= '. ok!';

            $result[] = 'Making a HTTP request for the image to verify that we get a jpeg back (there is no webp yet)';
            $requestUrl = Paths::getUploadUrl() . "/webp-express-test-image.jpg";
            $return = wp_remote_request($requestUrl);
            if (is_wp_error($return)) {
                $result[count($result) - 1] .= '. FAILED';
                $result[] = 'Request URL: ' . $requestUrl;
            } else {
                if ($return['response']['code'] != '200') {
                    $result[count($result) - 1] .= '. FAILED';
                    $result[] = 'Unexpected response: ' . $return['response']['code'] . ' ' . $return['response']['message'];
                    $result[] = 'Request URL: ' . $requestUrl;
                }
                if ((isset($return['headers']['content-type']) == 'image/jpeg') && ($return['headers']['content-type'] == 'image/jpeg')) {
                    $result[count($result) - 1] .= '. ok!';
                } else {
                    $result[count($result) - 1] .= '. FAILED';
                    if (!isset($return['headers']['content-type'])) {
                        $result[] = 'Hm - expected a "content-type" response header, but it is missing';
                    } else {
                        $result[] = 'The content-type header is NOT "image/jpeg"';
                    }
                    $result[] = 'Response headers:';
                    foreach ($return['headers'] as $headerName => $headerValue) {
                        $result[] = '- ' . $headerName . ': ' . $headerValue;
                    }
                }

            }
            $result[] = 'More tests will come in future versions!';
        }*/

        return $result;
    }

    public static function processAjax()
    {
        if (!check_ajax_referer('webpexpress-ajax-self-test-nonce', 'nonce', false)) {
            wp_send_json_error('Invalid security nonce (it has probably expired - try refreshing)');
            wp_die();
        }

        // Check input
        // --------------
        try {
            // Check "testId"
            $checking = '"testId" argument';
            Validate::postHasKey('testId');

            $testId = sanitize_text_field(stripslashes($_POST['testId']));

        } catch (Exception $e) {
            wp_send_json_error('Validation failed for ' . $checking . ': '. $e->getMessage());
            wp_die();
        }
        $result = '';
        if (method_exists(__CLASS__, $testId)) {

            // The following call sets self::$next.
            $result = call_user_func(array(__CLASS__, $testId));
        } else {
            $result = ['Unknown test: ' . $testId];
            self::$next = 'break';
        }

        $response = [
            'result' => $result,
            'next' => self::$next
        ];
        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
        wp_die();
    }

}
