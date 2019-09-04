<?php

namespace WebPExpress;

use \WebPExpress\Paths;

class SelfTest
{

    public static function ignite()
    {
        return ['', 'systemInfo'];
    }

    public static function systemInfo()
    {
        $result = [];
        $result[] = '# System info';
        $result[] = 'PHP version: ' . phpversion();
        $result[] = 'OS: ' . PHP_OS;
        $result[] = 'Server software: ' . $_SERVER["SERVER_SOFTWARE"];
        $result[] = 'Document Root: ' . Paths::docRootStatusText();
        return [$result, 'configInfo'];
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
        return [$result, 'capabilityTests'];
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

        return [$result, 'done'];
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


    public static function redirectToExisting()
    {
        $config = Config::loadConfigAndFix(false);
        $result = [];

        //$result[] = '*hello* with *you* and **you**. ok! FAILED';
        $result[] = '# Testing redirection to existing webp';
        if (!$config['redirect-to-existing-in-htaccess']) {
            $result[] = 'Turned off, nothing to test';
        } else {
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
            }
            $result[] = 'More tests will come in future versions!';

        }

        return [$result, 'done'];
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
        $next = '';
        if (method_exists(__CLASS__, $testId)) {
            list($result, $next) = call_user_func(array(__CLASS__, $testId));
        } else {
            $result = ['Unknown test: ' . $testId];
            $next = 'break';
        }

        $response = [
            'result' => $result,
            'next' => $next
        ];
        echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
        wp_die();
    }

}
