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

use \HtaccessCapabilityTester\TesterFactory;

include_once WEBPEXPRESS_PLUGIN_DIR . '/vendor/autoload.php';

class HTAccessCapabilityTestRunner
{

    public static $cachedResults;

    /**
     *  Tests if a test script responds with "pong"
     */
    private static function canRunPingPongTestScript($url)
    {
        $response = wp_remote_get($url, ['timeout' => 10]);
        //echo '<pre>' . print_r($response, true) . '</pre>';
        if (wp_remote_retrieve_response_code($response) != '200') {
            return false;
        }
        $body = wp_remote_retrieve_body($response);
        return ($body == 'pong');
    }

    private static function runNamedTest($testName)
    {
        switch ($testName) {
            case 'canRunTestScriptInWOD':
                $url = Paths::getWebPExpressPluginUrl() . '/wod/ping.php';
                return self::canRunPingPongTestScript($url);

            case 'canRunTestScriptInWOD2':
                $url = Paths::getWebPExpressPluginUrl() . '/wod2/ping.php';
                return self::canRunPingPongTestScript($url);

            case 'modHeaderWorking':
                return self::runTestInWebPExpressContentDir('set-request-header-tester');

            case 'modRewriteWorking':
                return self::runTestInWebPExpressContentDir('rewrite-tester');

            case 'passThroughEnvWorking':
                return self::runTestInWebPExpressContentDir('pass-env-through-rewrite-tester');

            case 'passThroughHeaderWorking':
                // pretend it fails because .htaccess rules aren't currently generated correctly
                return false;
                return self::runTestInWebPExpressContentDir('pass-env-through-request-header-tester');

            case 'grantAllAllowed':
                return self::runTestInWebPExpressContentDir('grant-all-crash-tester');
        }
    }

    private static function runOrGetCached($testName)
    {
        if (!isset(self::$cachedResults)) {
            self::$cachedResults = [];
        }
        if (!isset(self::$cachedResults[$testName])) {
            self::$cachedResults[$testName] = self::runNamedTest($testName);
        }
        return self::$cachedResults[$testName];
    }

    /**
     *  Run one of the htaccess capability tests.
     *  Three possible outcomes: true, false or null (null if request fails)
     */
    private static function runTestInWebPExpressContentDir($testName)
    {
        $baseDir = Paths::getWebPExpressContentDirAbs() . '/htaccess-capability-tests';
        $baseUrl = Paths::getContentUrl() . '/webp-express/htaccess-capability-tests';

        $tester = TesterFactory::create($testName, $baseDir, $baseUrl);
        $tester->setHTTPRequester(new WPHTTPRequester());

        try {
            $testResult = $tester->runTest();
        } catch (\Exception $e) {
            $testResult = null;
        }
        //error_log('test: ' . $testName . ':' . (($testResult === true) ? 'ok' : ($testResult === false ? 'failed' : 'hm')));

        return $testResult;
    }


    public static function modRewriteWorking()
    {
        return self::runOrGetCached('modRewriteWorking');
    }

    public static function modHeaderWorking()
    {
        return self::runOrGetCached('modHeaderWorking');
    }

    public static function passThroughEnvWorking()
    {
        return self::runOrGetCached('passThroughEnvWorking');
    }

    public static function passThroughHeaderWorking()
    {
        return self::runOrGetCached('passThroughHeaderWorking');
    }

    public static function grantAllAllowed()
    {
        return self::runOrGetCached('grantAllAllowed');
    }

    public static function canRunTestScriptInWOD()
    {
        return self::runOrGetCached('canRunTestScriptInWOD');
    }

    public static function canRunTestScriptInWOD2()
    {
        return self::runOrGetCached('canRunTestScriptInWOD2');
    }


}
