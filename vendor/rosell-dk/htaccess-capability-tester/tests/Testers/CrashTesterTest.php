<?php
/*
subdir: 'crash-tester/xxx'  # xxx is a subdir for the specific crash-test
subtests:
  - subdir: the-suspect
    files:
        - filename: '.htaccess'
          content:                          # the rules goes here
        - filename: 'request-me.txt'
          content: 'thanks'
    request:
        url: 'request-me.txt'
        bypass-standard-error-handling': ['all']
    interpretation:
        - [success, body, equals, '1']
        - [failure, body, equals, '0']
        - [success, status-code, not-equals, '500']

  - subdir: the-innocent
    files:
        - filename: '.htaccess'
          content: '# I am no trouble'
        - filename: 'request-me.txt'
          content: 'thanks'
    request:
        url: 'request-me.txt'
        bypass-standard-error-handling: ['all']
    interpretation:
      # The suspect crashed. But if the innocent crashes too, we cannot judge
      [inconclusive, status-code, equals, '500']

      # The innocent did not crash. The suspect is guilty!
      [failure]

----

Tested:

Server setup                   |  Test result
--------------------------------------------------
.htaccess disabled             |  success!  (nothing crashes)
access denied                  |  success!  (nothing crashes. In case there is both errors and
                                             access denied, the response is 500. This is however
                                             only tested on Apache 2.4.29)
all requests crash             |  inconclusive  (even innocent request crashes means that we cannot
                                             conclude that the rules are "crashy", or that they are not

*/


namespace HtaccessCapabilityTester\Tests\Testers;

use HtaccessCapabilityTester\HttpResponse;
use HtaccessCapabilityTester\Testers\CrashTester;
use HtaccessCapabilityTester\Tests\FakeServer;
use PHPUnit\Framework\TestCase;

class CrashTesterTest extends BasisTestCase
{
    public function testHtaccessDisabled()
    {
      $fakeServer = new FakeServer();
      $fakeServer->disableHtaccess();
      $testResult = $fakeServer->runTester(new CrashTester(''));
      $this->assertSuccess($testResult);
    }

    public function testAccessAllDenied()
    {
        $fakeServer = new FakeServer();
        $fakeServer->denyAllAccess();
        $testResult = $fakeServer->runTester(new CrashTester(''));
        $this->assertSuccess($testResult);
    }

    public function testWhenAllRequestsCrashes()
    {
        $fakeServer = new FakeServer();
        $fakeServer->makeAllCrash();
        $testResult = $fakeServer->runTester(new CrashTester(''));
        $this->assertInconclusive($testResult);
    }

    public function testWhenAllRequestsCrashes2()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/crash-tester/test/the-suspect/request-me.txt' => new HttpResponse('', '500', []),
            '/crash-tester/test/the-innocent/request-me.txt' => new HttpResponse('', '500', [])
        ]);
        $testResult = $fakeServer->runTester(new CrashTester('aoeu', 'test'));
        $this->assertInconclusive($testResult);
    }

    public function testWhenRequestCrashesButInnocentDoesNot()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/crash-tester/test/the-suspect/request-me.txt' => new HttpResponse('', '500', []),
            '/crash-tester/test/the-innocent/request-me.txt' => new HttpResponse('thanks', '200', [])
        ]);
        $testResult = $fakeServer->runTester(new CrashTester('aoeu', 'test'));
        $this->assertFailure($testResult);
    }

    public function testRequestFailure()
    {
        $fakeServer = new FakeServer();
        $fakeServer->failAllRequests();
        $testResult = $fakeServer->runTester(new CrashTester('aoeu', 'test'));
        $this->assertInconclusive($testResult);
    }

}
