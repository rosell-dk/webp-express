<?php
/*
This functionality will be moved to a separate project.

Btw:
Seems someone else got similar idea:
http://christian.roy.name/blog/detecting-modrewrite-using-php
*/
namespace WebPExpress;

use \WebPExpress\FileHelper;
use \WebPExpress\Paths;

class CapabilityTest
{

    public static function copyCapabilityTestsToWpContent()
    {
        return FileHelper::cpdir(Paths::getWebPExpressPluginDirAbs() . '/htaccess-capability-tests', Paths::getWebPExpressContentDirAbs() . '/htaccess-capability-tests');
    }


    /**
     *  Run one of the tests in wp-content/webp-express/capability-tests
     *  Three possible outcomes: true, false or null (null if request fails)
     */
    public static function runTest($testDir)
    {
        //echo 'running test:' . $testDir . '<br>';
        if (!@file_exists(Paths::getWebPExpressPluginDirAbs() . '/htaccess-capability-tests/' . $testDir)) {
            // test does not even exist
            //echo 'test does not exist: ' . $testDir . '<br>';
            return null;
        }

        if (!@file_exists(Paths::getWebPExpressContentDirAbs() . '/htaccess-capability-tests/' . $testDir)) {
            self::copyCapabilityTestsToWpContent();
        }

        // If copy failed, we can use the test in plugin path
        if (!@file_exists(Paths::getWebPExpressContentDirAbs() . '/htaccess-capability-tests/' . $testDir)) {
            $testUrl = Paths::getContentUrl() . '/' . 'webp-express/htaccess-capability-tests/' . $testDir . '/test.php';
        } else {
            $testUrl = Paths::getWebPExpressPluginUrl() . '/' . 'htaccess-capability-tests/' . $testDir . '/test.php';
        }

        //echo 'test url: ' . $testUrl . '<br>';
        // TODO:  Should we test if wp_remote_get exists first? - and if not, include wp-includes/http.php ?

        $response = wp_remote_get($testUrl, ['timeout' => 10]);
        //echo '<pre>' . print_r($response, true) . '</pre>';
        if (wp_remote_retrieve_response_code($response) != '200') {
            return null;
        }
        $responseBody = wp_remote_retrieve_body($response);
        if ($responseBody == '') {
            return null;            // Some failure
        }
        if ($responseBody == '0') {
            return false;
        }
        if ($responseBody == '1') {
            return true;
        }
        return null;
    }


    /**
     *  Three possible outcomes: true, false or null (null if failed to run test)
     */
    public static function modRewriteWorking()
    {
        return self::runTest('has-mod-rewrite');
    }

    /**
     *  Three possible outcomes: true, false or null (null if failed to run test)
     */
    public static function modHeaderWorking()
    {
        return self::runTest('has-mod-header');
    }

    /**
     *  Three possible outcomes: true, false or null (null if failed to run test)
     */
    public static function passThroughEnvWorking()
    {
        return self::runTest('pass-through-environment-var');
    }

    /**
     *  Three possible outcomes: true, false or null (null if failed to run test)
     */
    public static function passThroughHeaderWorking()
    {
        // pretend it fails because .htaccess rules aren't currently generated correctly
        return false;
        return self::runTest('pass-server-var-through-header');
    }

}
