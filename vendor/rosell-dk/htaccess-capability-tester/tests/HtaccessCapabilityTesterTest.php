<?php
/*
subdir: rewrite
files:
    - filename: '.htaccess'
      content: |
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteRule ^0\.txt$ 1\.txt [L]
        </IfModule>
    - filename: '0.txt'
      content: '0'
    - filename: '1.txt'
      content: '1'

request:
    url: '0.txt'

interpretation:
    - [success, body, equals, '1']
    - [failure, body, equals, '0']


----

Tested:

Server setup                   |  Test result
--------------------------------------------------
.htaccess disabled             |  failure
forbidden directives (fatal)   |  failure
access denied                  |  inconclusive  (it might be allowed to other files)
directive has no effect        |  failure
                               |  success
*/


namespace HtaccessCapabilityTester\Tests\Testers;

use HtaccessCapabilityTester\HtaccessCapabilityTester;
use HtaccessCapabilityTester\HttpResponse;
use HtaccessCapabilityTester\Tests\FakeServer;
use PHPUnit\Framework\TestCase;

class HtaccessCapabilityTesterTest extends BasisTestCase
{

    public function testHeaderSetWorksSuccess()
    {
        $hct = new HtaccessCapabilityTester('', '');

        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/header-set/request-me.txt' => new HttpResponse(
                'hi',
                '200',
                ['X-Response-Header-Test' => 'test']
            )
        ]);
        $fakeServer->connectHCT($hct);
        $this->assertTrue($hct->headerSetWorks());
    }

    public function testRequestHeaderWorksSuccess()
    {
        $hct = new HtaccessCapabilityTester('', '');

        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/request-header/test.php' => new HttpResponse('1', '200', [])
        ]);
        $fakeServer->connectHCT($hct);
        $this->assertTrue($hct->requestHeaderWorks());
    }

    public function testRequestHeaderWorksFailure1()
    {
        $hct = new HtaccessCapabilityTester('', '');

        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/request-header/test.php' => new HttpResponse('0', '200', [])
        ]);
        $fakeServer->connectHCT($hct);
        $this->assertFalse($hct->requestHeaderWorks());
    }

    public function testPassingThroughRequestHeaderSuccess()
    {
        $hct = new HtaccessCapabilityTester('', '');

        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/pass-info-from-rewrite-to-script-through-request-header/test.php' =>
                new HttpResponse('1', '200', [])
        ]);
        $fakeServer->connectHCT($hct);
        $this->assertTrue($hct->passingInfoFromRewriteToScriptThroughRequestHeaderWorks());
    }

    public function testPassingThroughEnvSuccess()
    {
        $hct = new HtaccessCapabilityTester('', '');

        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/pass-info-from-rewrite-to-script-through-env/test.php' =>
                new HttpResponse('1', '200', [])
        ]);
        $fakeServer->connectHCT($hct);
        $this->assertTrue($hct->passingInfoFromRewriteToScriptThroughEnvWorks());
    }

    public function testModuleLoadedWhenNotLoaded()
    {
        $hct = new HtaccessCapabilityTester('', '');

        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/rewrite/0.txt' => new HttpResponse('1', '200', []),
            '/module-loaded/setenvif/rewrite/request-me.txt' => new HttpResponse('0', '200', []),
        ]);
        $fakeServer->connectHCT($hct);
        $this->assertFalse($hct->moduleLoaded('setenvif'));
    }

    public function testModuleLoadedWhenLoaded()
    {
        $hct = new HtaccessCapabilityTester('', '');

        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/rewrite/0.txt' => new HttpResponse('1', '200', []),
            '/module-loaded/setenvif/rewrite/request-me.txt' => new HttpResponse('1', '200', []),
        ]);
        $fakeServer->connectHCT($hct);
        $this->assertTrue($hct->moduleLoaded('setenvif'));
    }


//
}
