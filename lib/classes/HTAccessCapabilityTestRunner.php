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

use \HtaccessCapabilityTester\HtaccessCapabilityTester;

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
        if (is_wp_error($response)) {
            return null;
        }
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

            case 'htaccessEnabled':
                return self::runTestInWebPExpressContentDir('htaccessEnabled');

            case 'modHeadersLoaded':
                return self::runTestInWebPExpressContentDir('modHeadersLoaded');

            case 'modHeaderWorking':
                return self::runTestInWebPExpressContentDir('headerSetWorks');

            case 'modRewriteWorking':
                return self::runTestInWebPExpressContentDir('rewriteWorks');

            case 'passThroughEnvWorking':
                return self::runTestInWebPExpressContentDir('passingInfoFromRewriteToScriptThroughEnvWorks');

            case 'passThroughHeaderWorking':
                // pretend it fails because .htaccess rules aren't currently generated correctly
                return false;
                return self::runTestInWebPExpressContentDir('passingInfoFromRewriteToScriptThroughRequestHeaderWorks');

            case 'grantAllAllowed':
                return self::runTestInWebPExpressContentDir('grantAllCrashTester');
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

        $hct = new HtaccessCapabilityTester($baseDir, $baseUrl);
        $hct->setHttpRequester(new WPHttpRequester());

        try {
            switch ($testName) {
                case 'htaccessEnabled':
                    return $hct->htaccessEnabled();
                case 'rewriteWorks':
                    return $hct->rewriteWorks();
                case 'addTypeWorks':
                    return $hct->addTypeWorks();
                case 'modHeadersLoaded':
                    return $hct->moduleLoaded('headers');
                case 'headerSetWorks':
                    return $hct->headerSetWorks();
                case 'requestHeaderWorks':
                    return $hct->requestHeaderWorks();
                case 'passingInfoFromRewriteToScriptThroughRequestHeaderWorks':
                    return $hct->passingInfoFromRewriteToScriptThroughRequestHeaderWorks();
                case 'passingInfoFromRewriteToScriptThroughEnvWorks':
                    return $hct->passingInfoFromRewriteToScriptThroughEnvWorks();
                case 'grantAllCrashTester':
                    $rules = <<<'EOD'
<FilesMatch "(webp-on-demand\.php|webp-realizer\.php|ping\.php|ping\.txt)$">
  <IfModule !mod_authz_core.c>
    Order deny,allow
    Allow from all
  </IfModule>
  <IfModule mod_authz_core.c>
    Require all granted
  </IfModule>
</FilesMatch>
EOD;
                    return $hct->crashTest($rules, 'grant-all');
            }

        } catch (\Exception $e) {
            return null;
        }
        //error_log('test: ' . $testName . ':' . (($testResult === true) ? 'ok' : ($testResult === false ? 'failed' : 'hm')));

        throw new \Exception('Unknown test:' . $testName);
    }


    public static function modRewriteWorking()
    {
        return self::runOrGetCached('modRewriteWorking');
    }

    public static function htaccessEnabled()
    {
        return self::runOrGetCached('htaccessEnabled');
    }

    public static function modHeadersLoaded()
    {
        return self::runOrGetCached('modHeadersLoaded');
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
