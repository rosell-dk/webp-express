<?php
/*
subdir: server-signature
subtests:
  - subdir: on
    files:
    - filename: '.htaccess'
      content: |
        ServerSignature On
    - filename: 'test.php'
      content: |
      <?php
      if (isset($_SERVER['SERVER_SIGNATURE']) && ($_SERVER['SERVER_SIGNATURE'] != '')) {
          echo 1;
      } else {
          echo 0;
      }
    request:
      url: 'test.php'
    interpretation:
      - ['inconclusive', 'body', 'isEmpty']
      - ['inconclusive', 'status-code', 'not-equals', '200']
      - ['failure', 'body', 'equals', '0']

  - subdir: off
    files:
    - filename: '.htaccess'
      content: |
        ServerSignature Off
    - filename: 'test.php'
      content: |
      <?php
      if (isset($_SERVER['SERVER_SIGNATURE']) && ($_SERVER['SERVER_SIGNATURE'] != '')) {
          echo 0;
      } else {
          echo 1;
      }
    request:
      url: 'test.php'
    interpretation:
      - ['inconclusive', 'body', 'isEmpty']
      - ['success', 'body', 'equals', '1']
      - ['failure', 'body', 'equals', '0']
      - ['inconclusive']

----

Tested:

Server setup                   |  Test result
--------------------------------------------------
.htaccess disabled             |  failure
forbidden directives (fatal)   |  inconclusive  (special!)
access denied                  |  inconclusive  (it might be allowed to other files)
directive has no effect        |  failure
                               |  success
*/


namespace HtaccessCapabilityTester\Tests\Testers;

use HtaccessCapabilityTester\HttpResponse;
use HtaccessCapabilityTester\Testers\ServerSignatureTester;
use HtaccessCapabilityTester\Tests\FakeServer;
use PHPUnit\Framework\TestCase;

class ServerSignatureTesterTest extends BasisTestCase
{

/*
    can't do this test as our fake server does not execute PHP

    public function testHtaccessDisabled()
    {
        $fakeServer = new FakeServer();
        $fakeServer->disableHtaccess();
        $testResult = $fakeServer->runTester(new ServerSignatureTester());
        $this->assertFailure($testResult);
    }*/

    public function testDisallowedDirectivesFatal()
    {
        $fakeServer = new FakeServer();
        $fakeServer->disallowAllDirectives('fatal');
        $testResult = $fakeServer->runTester(new ServerSignatureTester());
        $this->assertFailure($testResult);

        // SPECIAL!
        // As ServerSignature is in core and AllowOverride is None, the tester assumes
        // that this does not happen. The 500 must then be another problem, which is why
        // it returns inconclusive
        //$this->assertInconclusive($testResult);
    }

    public function testAccessAllDenied()
    {
        $fakeServer = new FakeServer();
        $fakeServer->denyAllAccess();
        $testResult = $fakeServer->runTester(new ServerSignatureTester());
        $this->assertInconclusive($testResult);
    }

    /**
     * Test when the directive has no effect.
     * This could happen when:
     * - The directive is forbidden (non-fatal)
     * - The module is not loaded
     *
     * This tests when ServerSignature is set, and the directive has no effect.
     */
    public function testDirectiveHasNoEffect1()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/server-signature/on/test.php' => new HttpResponse('1', '200', []),
            '/server-signature/off/test.php' => new HttpResponse('0', '200', [])
        ]);
        $testResult = $fakeServer->runTester(new ServerSignatureTester());
        $this->assertFailure($testResult);
    }

    /**
     * This tests when ServerSignature is unset, and the directive has no effect.
     */
    public function testDirectiveHasNoEffect2()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/server-signature/on/test.php' => new HttpResponse('0', '200', []),
            '/server-signature/off/test.php' => new HttpResponse('1', '200', [])
        ]);
        $testResult = $fakeServer->runTester(new ServerSignatureTester());
        $this->assertFailure($testResult);
    }


    public function testSuccess()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/server-signature/on/test.php' => new HttpResponse('1', '200', []),
            '/server-signature/off/test.php' => new HttpResponse('1', '200', [])
        ]);
        $testResult = $fakeServer->runTester(new ServerSignatureTester());
        $this->assertSuccess($testResult);
    }

}
