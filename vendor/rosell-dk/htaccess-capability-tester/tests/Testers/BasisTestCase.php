<?php

namespace HtaccessCapabilityTester\Tests\Testers;

use HtaccessCapabilityTester\HtaccessCapabilityTester;
use HtaccessCapabilityTester\TestResult;

use HtaccessCapabilityTester\Testers\RewriteTester;
use HtaccessCapabilityTester\Testers\AbstractTester;

use HtaccessCapabilityTester\Tests\FakeServer;

use PHPUnit\Framework\TestCase;

class BasisTestCase extends TestCase
{

    protected function assertSuccess($testResult)
    {
        $this->assertTrue($testResult->status, $testResult->info);
    }

    protected function assertFailure($testResult)
    {
        $this->assertFalse($testResult->status, $testResult->info);
    }

    protected function assertInconclusive($testResult)
    {
        $this->assertNull($testResult->status, $testResult->info);
    }

    /**
     *
     * @param TestResult $testResult
     * @param string     $expectedResult  failure|success|inconclusive
     *
     */
     /*
    protected function assertTestResult($testResult, $expectedResult)
    {
        if ($expectedResult == 'failure') {
            $this->assertFalse($testResult->status);
        } elseif ($expectedResult == 'success') {
            $this->assertTrue($testResult->status);
        } elseif ($expectedResult == 'inconclusive') {
            $this->assertNull($testResult->status);
        }
    }*/

    /**
     * @param AbstractTester  $tester
     * @param array           $expectedBehaviour
     * @param FakeServer      $fakeServer
     */
     /*
    protected function behaviourOnFakeServer($tester, $expectedBehaviour, $fakeServer)
    {
        $tester->setTestFilesLineUpper($fakeServer);
        $tester->setHttpRequester($fakeServer);

//            $hct = Helper::getTesterUsingFakeServer($fakeServer);

        if (isset($expectedBehaviour['htaccessDisabled'])) {
            $fakeServer->disallowAllDirectives = true;
            $testResult = $tester->run('', '');
            $this->assertTestResult($testResult, );

            $this->assertFailure($testResult->status);
        }
    }*/
}
