<?php
/*

----

Tested:

Server setup                   |  Test result
--------------------------------------------------
.htaccess disabled             |  failure
access denied                  |  inconclusive  (it might be allowed to other files)
it works                       |  success
*/


namespace HtaccessCapabilityTester\Tests\Testers;

use HtaccessCapabilityTester\HttpResponse;
use HtaccessCapabilityTester\Testers\HtaccessEnabledTester;
use HtaccessCapabilityTester\Tests\FakeServer;
use PHPUnit\Framework\TestCase;

class HtaccessEnabledTesterTest extends BasisTestCase
{

    /**
     * Test failure when server signature fails
     *
     */
    public function testSuccessServerSignatureFails()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/server-signature/on/test.php' => new HttpResponse('0', '200', []),
            '/server-signature/off/test.php' => new HttpResponse('1', '200', [])
        ]);
        $testResult = $fakeServer->runTester(new HtaccessEnabledTester());
        $this->assertFailure($testResult);
    }

    /**
     * Test success when server signature works.
     *
     */
    public function testSuccessServerSignatureSucceeds()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/server-signature/on/test.php' => new HttpResponse('1', '200', []),
            '/server-signature/off/test.php' => new HttpResponse('1', '200', [])
        ]);
        $testResult = $fakeServer->runTester(new HtaccessEnabledTester());
        $this->assertSuccess($testResult);
    }

    /**
     * Test success when setting a header works.
     */
    public function testSuccessHeaderSetSucceeds()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/header-set/request-me.txt' => new HttpResponse(
                'hi',
                '200',
                ['X-Response-Header-Test' => 'test']
            )
        ]);
        $testResult = $fakeServer->runTester(new HtaccessEnabledTester());
        $this->assertSuccess($testResult);
    }

    /**
     * Test success when malformed .htaccess causes 500
     */
    public function testSuccessMalformedHtaccess()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/crash-tester/htaccess-enabled-malformed-htaccess/the-suspect/request-me.txt' =>
                new HttpResponse('', '500', []),
            '/crash-test/htaccess-enabled-malformed-htaccess/the-innocent/request-me.txt' =>
                new HttpResponse('thanks', '200', [])
        ]);
        $testResult = $fakeServer->runTester(new HtaccessEnabledTester());
        $this->assertSuccess($testResult);
    }

    /**
     * Test failure when malformed .htaccess causes 500
     */
    public function testFailureMalformedHtaccessDoesNotCauseCrash()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/crash-tester/htaccess-enabled-malformed-htaccess/the-suspect/request-me.txt' =>
                new HttpResponse('thanks', '200', []),
            '/crash-test/htaccess-enabled-malformed-htaccess/the-innocent/request-me.txt' =>
                new HttpResponse('thanks', '200', [])
        ]);
        $testResult = $fakeServer->runTester(new HtaccessEnabledTester());
        $this->assertFailure($testResult);
    }

    /**
     * Test inconclusive when all crashes
     */
    public function testInconclusiveWhenAllCrashes()
    {
        $fakeServer = new FakeServer();
        $fakeServer->makeAllCrash();
        $testResult = $fakeServer->runTester(new HtaccessEnabledTester());
        $this->assertInconclusive($testResult);
    }

    public function testRequestFailure()
    {
        $fakeServer = new FakeServer();
        $fakeServer->failAllRequests();
        $testResult = $fakeServer->runTester(new HtaccessEnabledTester());
        $this->assertInconclusive($testResult);
    }

}
