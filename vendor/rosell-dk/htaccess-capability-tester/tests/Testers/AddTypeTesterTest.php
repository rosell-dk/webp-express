<?php
/*
subdir: add-type
files:
  - filename: '.htaccess'
    content: |
      <IfModule mod_mime.c>
          AddType image/gif .test
      </IfModule>
  - filename: 'request-me.test'
    content: 'hi'
request:
  url: 'request-me.test'

interpretation:
 - ['success', 'headers', 'contains-key-value', 'Content-Type', 'image/gif']
 - ['inconclusive', 'status-code', 'not-equals', '200']
 - ['failure', 'headers', 'not-contains-key-value', 'Content-Type', 'image/gif']

----

Tested:

| Case                           |  Test result
| ------------------------------ | ------------------
| .htaccess disabled             |  failure
| forbidden directives (fatal)   |  failure
| access denied                  |  inconclusive
| directive has no effect        |  failure
| it works                       |  success
*/


namespace HtaccessCapabilityTester\Tests\Testers;

use HtaccessCapabilityTester\HttpResponse;
use HtaccessCapabilityTester\Testers\AddTypeTester;
use HtaccessCapabilityTester\Tests\FakeServer;
use PHPUnit\Framework\TestCase;

class AddTypeTesterTest extends BasisTestCase
{

    public function testHtaccessDisabled()
    {
        $fakeServer = new FakeServer();
        $fakeServer->disableHtaccess();
        $testResult = $fakeServer->runTester(new AddTypeTester());
        $this->assertFailure($testResult);
    }

    public function testDisallowedDirectivesFatal()
    {
        $fakeServer = new FakeServer();
        $fakeServer->disallowAllDirectives('fatal');
        $testResult = $fakeServer->runTester(new AddTypeTester());
        $this->assertFailure($testResult);
    }

    public function testAccessAllDenied()
    {
        $fakeServer = new FakeServer();
        $fakeServer->denyAllAccess();
        $testResult = $fakeServer->runTester(new AddTypeTester());
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
            '/add-type/request-me.test' => new HttpResponse('hi', '200', [])
        ]);
        $testResult = $fakeServer->runTester(new AddTypeTester());
        $this->assertFailure($testResult);
    }

    public function testSuccess()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/add-type/request-me.test' => new HttpResponse('hi', '200', ['Content-Type' => 'image/gif'])
        ]);
        $testResult = $fakeServer->runTester(new AddTypeTester());
        $this->assertSuccess($testResult);
    }

}
