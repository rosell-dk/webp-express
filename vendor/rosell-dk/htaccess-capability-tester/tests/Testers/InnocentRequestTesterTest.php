<?php
/*
subdir: innocent-request
files:
  - filename: 'request-me.txt'
    content: 'thank you my dear'

request:
  url: 'request-me.txt'
  bypass-standard-error-handling: 'all'

interpretation:
  - ['success', 'status-code', 'equals', '200']
  - ['inconclusive', 'status-code', 'equals', '403']
  - ['inconclusive', 'status-code', 'equals', '404']
  - ['failure']
----

Tested:

Server setup                   |  Test result
--------------------------------------------------
access denied                  |  inconclusive  (it might be allowed to other files)
always fatal                   |  failure
*/


namespace HtaccessCapabilityTester\Tests\Testers;

use HtaccessCapabilityTester\HttpResponse;
use HtaccessCapabilityTester\Testers\InnocentRequestTester;
use HtaccessCapabilityTester\Tests\FakeServer;
use PHPUnit\Framework\TestCase;

class InnocentRequestTesterTest extends BasisTestCase
{


    public function testAccessAllDenied()
    {
        $fakeServer = new FakeServer();
        $fakeServer->denyAllAccess();
        $testResult = $fakeServer->runTester(new InnocentRequestTester());
        $this->assertInconclusive($testResult);
    }

    public function testSuccess()
    {
        $fakeServer = new FakeServer();
        $testResult = $fakeServer->runTester(new InnocentRequestTester());
        $this->assertSuccess($testResult);
    }

}
