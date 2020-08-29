<?php

namespace WebPExpress;

class SelfTest
{

    private static $next;

    public static function allInfo()
    {
        self::$next = 'done';
        $config = Config::loadConfigAndFix(false);
        return SelfTestHelper::allInfo($config);
    }


    public static function systemInfo()
    {
        self::$next = 'configInfo';
        return SelfTestHelper::systemInfo();
    }

    public static function configInfo()
    {
        self::$next = 'capabilityTests';
        $config = Config::loadConfigAndFix(false);
        return SelfTestHelper::configInfo($config);
    }

    public static function capabilityTests()
    {
        self::$next = 'done';
        $config = Config::loadConfigAndFix(false);
        return SelfTestHelper::capabilityTests($config);
    }

    public static function redirectToExisting()
    {
        self::$next = 'done';
        list ($success, $result) = SelfTestRedirectToExisting::runTest();
        return $result;
        /*
        $result = [];
        $result[] = '# Redirection tests';
        $modRewriteWorking = HTAccessCapabilityTestRunner::modRewriteWorking();
        $modHeaderWorking = HTAccessCapabilityTestRunner::modHeaderWorking();

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

    public static function redirectToConverter()
    {
        self::$next = 'done';
        list ($success, $result) = SelfTestRedirectToConverter::runTest();
        return $result;
    }

    public static function redirectToWebPRealizer()
    {
        self::$next = 'done';
        list ($success, $result) = SelfTestRedirectToWebPRealizer::runTest();
        return $result;
    }


    public static function processAjax()
    {
        if (!check_ajax_referer('webpexpress-ajax-self-test-nonce', 'nonce', false)) {
            wp_send_json_error('The security nonce has expired. You need to reload the settings page (press F5) and try again)');
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
