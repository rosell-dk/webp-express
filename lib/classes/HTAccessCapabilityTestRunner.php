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

    /**
     *  Run one of the htaccess capability tests
     *  Three possible outcomes: true, false or null (null if request fails)
     */
    public static function runTest($testName)
    {
        $baseDir = Paths::getWebPExpressContentDirAbs() . '/htaccess-capability-tests';
        $baseUrl = Paths::getContentUrl() . '/webp-express/htaccess-capability-tests';

        $tester = TesterFactory::create($testName, $baseDir, $baseUrl);
        $tester->setHTTPRequester(new WPHTTPRequester());

        return $tester->runTest();
    }


    /**
     *  Three possible outcomes: true, false or null (null if failed to run test)
     */
    public static function modRewriteWorking()
    {
        return self::runTest('rewrite-tester');
    }

    /**
     *  Three possible outcomes: true, false or null (null if failed to run test)
     */
    public static function modHeaderWorking()
    {
        return self::runTest('set-request-header-tester');
    }

    /**
     *  Three possible outcomes: true, false or null (null if failed to run test)
     */
    public static function passThroughEnvWorking()
    {
        return self::runTest('pass-env-through-rewrite-tester');
    }

    /**
     *  Three possible outcomes: true, false or null (null if failed to run test)
     */
    public static function passThroughHeaderWorking()
    {
        // pretend it fails because .htaccess rules aren't currently generated correctly
        return false;
        return self::runTest('pass-env-through-request-header-tester');
    }

    /**
     *  Three possible outcomes: true, false or null (null if failed to run test)
     */
    public static function grantAllAllowed()
    {
        return self::runTest('grant-all-crash-tester');
    }

}
