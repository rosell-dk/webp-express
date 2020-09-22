<?php
/*
subdir: request-header
files:
  - filename: '.htaccess'
    content: |
      <IfModule mod_headers.c>
          # Certain hosts seem to strip non-standard request headers,
          # so we use a standard one to avoid a false negative
          RequestHeader set User-Agent "request-header-test"
      </IfModule>
  - filename: 'test.php'
    content: |
      <?php
      if (isset($_SERVER['HTTP_USER_AGENT'])) {
          echo  $_SERVER['HTTP_USER_AGENT'] == 'request-header-test' ? 1 : 0;
      } else {
          echo 0;
      }

request:
  url: 'test.php'

interpretation:
  - ['success', 'body', 'equals', '1']
  - ['failure', 'body', 'equals', '0']
  - ['inconclusive', 'body', 'begins-with', '<?php'],


TODO:
TEST: php_flag engine off
https://stackoverflow.com/questions/1271899/disable-php-in-directory-including-all-sub-directories-with-htaccess
TEST: RemoveHandler and RemoveType (https://electrictoolbox.com/disable-php-apache-htaccess/)

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

TODO:
*/


namespace HtaccessCapabilityTester\Tests\Testers;

use HtaccessCapabilityTester\HttpResponse;
use HtaccessCapabilityTester\Testers\RequestHeaderTester;
use HtaccessCapabilityTester\Tests\FakeServer;
use PHPUnit\Framework\TestCase;

class RequestHeaderTesterTest extends BasisTestCase
{

    /* can't do this test, it would require processing PHP
    public function testHtaccessDisabled()
    {
        $fakeServer = new FakeServer();
        $fakeServer->disableHtaccess();
        $testResult = $fakeServer->runTester(new RequestHeaderTester());
        $this->assertFailure($testResult);
    }*/

    public function testDisallowedDirectivesFatal()
    {
        $fakeServer = new FakeServer();
        $fakeServer->disallowAllDirectives('fatal');
        $testResult = $fakeServer->runTester(new RequestHeaderTester());
        $this->assertFailure($testResult);
    }

    public function testAccessAllDenied()
    {
        $fakeServer = new FakeServer();
        $fakeServer->denyAllAccess();
        $testResult = $fakeServer->runTester(new RequestHeaderTester());
        $this->assertInconclusive($testResult);
    }

    /**
     * Test when the directive has no effect.
     * This could happen when:
     * - The directive is forbidden (non-fatal)
     * - The module is not loaded
     */
    public function testDirectiveHasNoEffect()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/request-header/test.php' => new HttpResponse('0', '200', [])
        ]);
        $testResult = $fakeServer->runTester(new RequestHeaderTester());
        $this->assertFailure($testResult);
    }


    public function testPHPNotProcessed()
    {
        $fakeServer = new FakeServer();
        $fakeServer->handlePHPasText();
        $testResult = $fakeServer->runTester(new RequestHeaderTester());
        $this->assertInconclusive($testResult);
    }


    public function testSuccess()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/request-header/test.php' => new HttpResponse('1', '200', [])
        ]);
        $testResult = $fakeServer->runTester(new RequestHeaderTester());
        $this->assertSuccess($testResult);
    }

}
