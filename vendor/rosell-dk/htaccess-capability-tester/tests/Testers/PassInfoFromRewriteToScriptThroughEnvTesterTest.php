<?php
/*
subdir: pass-info-from-rewrite-to-script-through-env
files:
  - filename: '.htaccess'
    content: |
      <IfModule mod_rewrite.c>

          # Testing if we can pass environment variable from .htaccess to script in a RewriteRule
          # We pass document root, because that can easily be checked by the script

          RewriteEngine On
          RewriteRule ^test\.php$ - [E=PASSTHROUGHENV:%{DOCUMENT_ROOT},L]

      </IfModule>
  - filename: 'test.php'
    content: |
      <?php
      function getEnvPassedInRewriteRule($envName) {
          // Environment variables passed through the REWRITE module have "REWRITE_" as a prefix
          // (in Apache, not Litespeed, if I recall correctly).
          // Multiple iterations causes multiple REWRITE_ prefixes, and we get many environment variables set.
          // We simply look for an environment variable that ends with what we are looking for.
          // (so make sure to make it unique)
          $len = strlen($envName);
          foreach ($_SERVER as $key => $item) {
              if (substr($key, -$len) == $envName) {
                  return $item;
              }
          }
          return false;
      }

      $result = getEnvPassedInRewriteRule('PASSTHROUGHENV');
      if ($result === false) {
          echo '0';
          exit;
      }
      echo ($result == $_SERVER['DOCUMENT_ROOT'] ? '1' : '0');

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
use HtaccessCapabilityTester\Testers\PassInfoFromRewriteToScriptThroughEnvTester;
use HtaccessCapabilityTester\Tests\FakeServer;
use PHPUnit\Framework\TestCase;

class PassInfoFromRewriteToScriptThroughEnvTesterTest extends BasisTestCase
{

    /* can't do this test, it would require processing PHP
    public function testHtaccessDisabled()
    {
        $fakeServer = new FakeServer();
        $fakeServer->disableHtaccess();
        $testResult = $fakeServer->runTester(new PassInfoFromRewriteToScriptThroughEnvTester());
        $this->assertFailure($testResult);
    }*/

    public function testDisallowedDirectivesFatal()
    {
        $fakeServer = new FakeServer();
        $fakeServer->disallowAllDirectives('fatal');
        $testResult = $fakeServer->runTester(new PassInfoFromRewriteToScriptThroughEnvTester());
        $this->assertFailure($testResult);
    }

    public function testAccessAllDenied()
    {
        $fakeServer = new FakeServer();
        $fakeServer->denyAllAccess();
        $testResult = $fakeServer->runTester(new PassInfoFromRewriteToScriptThroughEnvTester());
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
            '/pass-info-from-rewrite-to-script-through-env/test.php' =>
              new HttpResponse('0', '200', [])
        ]);
        $testResult = $fakeServer->runTester(new PassInfoFromRewriteToScriptThroughEnvTester());
        $this->assertFailure($testResult);
    }

    public function testPHPNotProcessed()
    {
        $fakeServer = new FakeServer();
        $fakeServer->handlePHPasText();
        $testResult = $fakeServer->runTester(
          new PassInfoFromRewriteToScriptThroughEnvTester()
        );
        $this->assertInconclusive($testResult);
    }

    public function testSuccess()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/pass-info-from-rewrite-to-script-through-env/test.php' =>
                new HttpResponse('1', '200', [])
        ]);
        $testResult = $fakeServer->runTester(
          new PassInfoFromRewriteToScriptThroughEnvTester()
        );
        $this->assertSuccess($testResult);
    }

}
