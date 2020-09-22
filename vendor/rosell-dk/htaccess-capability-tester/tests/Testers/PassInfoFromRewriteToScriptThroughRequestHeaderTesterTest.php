<?php
/*
subdir: pass-info-from-rewrite-to-script-through-request-header
files:
  - filename: '.htaccess'
    content: |
      <IfModule mod_rewrite.c>
          RewriteEngine On

          # Testing if we can pass an environment variable through a request header
          # We pass document root, because that can easily be checked by the script

          <IfModule mod_headers.c>
            RequestHeader set PASSTHROUGHHEADER "%{PASSTHROUGHHEADER}e" env=PASSTHROUGHHEADER
          </IfModule>
          RewriteRule ^test\.php$ - [E=PASSTHROUGHHEADER:%{DOCUMENT_ROOT},L]

      </IfModule>
  - filename: 'test.php'
    content: |
      <?php
      if (isset($_SERVER['HTTP_PASSTHROUGHHEADER'])) {
          echo ($_SERVER['HTTP_PASSTHROUGHHEADER'] == $_SERVER['DOCUMENT_ROOT'] ? 1 : 0);
          exit;
      }
      echo '0';

request:
  url: 'test.php'

interpretation:
  - ['success', 'body', 'equals', '1']
  - ['failure', 'body', 'equals', '0']
  - ['inconclusive', 'body', 'begins-with', '<?php']
  - ['inconclusive']
----

Tested:

Server setup                   |  Test result
--------------------------------------------------
.htaccess disabled             |  failure
forbidden directives (fatal)   |  failure
access denied                  |  inconclusive  (it might be allowed to other files)
directive has no effect        |  failure
php is unprocessed             |  inconclusive
directive works                |  success

*/


namespace HtaccessCapabilityTester\Tests\Testers;

use HtaccessCapabilityTester\HttpResponse;
use HtaccessCapabilityTester\Testers\PassInfoFromRewriteToScriptThroughRequestHeaderTester;
use HtaccessCapabilityTester\Tests\FakeServer;
use PHPUnit\Framework\TestCase;

class PassInfoFromRewriteToScriptThroughRequestHeaderTesterTest extends BasisTestCase
{

    /* can't do this test, it would require processing PHP
    public function testHtaccessDisabled()
    {
        $fakeServer = new FakeServer();
        $fakeServer->disableHtaccess();
        $testResult = $fakeServer->runTester(new PassInfoFromRewriteToScriptThroughRequestHeaderTester());
        $this->assertFailure($testResult);
    }*/

    public function testDisallowedDirectivesFatal()
    {
        $fakeServer = new FakeServer();
        $fakeServer->disallowAllDirectives('fatal');
        $testResult = $fakeServer->runTester(new PassInfoFromRewriteToScriptThroughRequestHeaderTester());
        $this->assertFailure($testResult);
    }

    public function testAccessAllDenied()
    {
        $fakeServer = new FakeServer();
        $fakeServer->denyAllAccess();
        $testResult = $fakeServer->runTester(new PassInfoFromRewriteToScriptThroughRequestHeaderTester());
        $this->assertInconclusive($testResult);
    }

    /**
     * Test when the magic is not working
     * This could happen when:
     * - Any of the directives are forbidden (non-fatal)
     * - Any of the modules are not loaded
     * - Perhaps these advanced features are not working on all platforms
     *   (does LiteSpeed ie support these this?)
     */
    public function testMagicNotWorking()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/pass-info-from-rewrite-to-script-through-request-header/test.php' =>
              new HttpResponse('0', '200', [])
        ]);
        $testResult = $fakeServer->runTester(new PassInfoFromRewriteToScriptThroughRequestHeaderTester());
        $this->assertFailure($testResult);
    }

    public function testPHPNotProcessed()
    {
        $fakeServer = new FakeServer();
        $fakeServer->handlePHPasText();
        $testResult = $fakeServer->runTester(
          new PassInfoFromRewriteToScriptThroughRequestHeaderTester()
        );
        $this->assertInconclusive($testResult);
    }

    public function testSuccess()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/pass-info-from-rewrite-to-script-through-request-header/test.php' =>
              new HttpResponse('1', '200', [])
        ]);
        $testResult = $fakeServer->runTester(
          new PassInfoFromRewriteToScriptThroughRequestHeaderTester()
        );
        $this->assertSuccess($testResult);
    }

}
