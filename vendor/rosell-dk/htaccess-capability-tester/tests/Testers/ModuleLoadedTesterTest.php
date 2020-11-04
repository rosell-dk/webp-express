<?php
/*

subdir: module-loaded
subtests:
  - subdir: server-signature
    requirements: htaccessEnabled()
    files:
    - filename: '.htaccess'
      content: |
          ServerSignature Off
          <IfModule mod_xxx.c>
          ServerSignature On
          </IfModule>

    - filename: 'test.php'
      content: |
          <?php
          if (isset($_SERVER['SERVER_SIGNATURE']) && ($_SERVER['SERVER_SIGNATURE'] != '')) {
              echo 1;
          } else {
              echo 0;
          }
    interpretation:
    - ['success', 'body', 'equals', '1']
    - ['failure', 'body', 'equals', '0']
  - subdir: rewrite
    ...
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
use HtaccessCapabilityTester\Testers\ModuleLoadedTester;
use HtaccessCapabilityTester\Tests\FakeServer;
use PHPUnit\Framework\TestCase;

class ModuleLoadedTesterTest extends BasisTestCase
{

    public function testHtaccessDisabled()
    {
        $fakeServer = new FakeServer();
        $fakeServer->disableHtaccess();
        $testResult = $fakeServer->runTester(new ModuleLoadedTester('setenvif'));
        $this->assertFailure($testResult);
    }

    public function testInconclusiveWhenAllCrashes()
    {
      $fakeServer = new FakeServer();

      $fakeServer->makeAllCrash();
      $testResult = $fakeServer->runTester(new ModuleLoadedTester('setenvif'));

      $this->assertInconclusive($testResult);
    }

    public function testServerSignatureSucceedsModuleLoaded()
    {
      $fakeServer = new FakeServer();
      $fakeServer->setResponses([
          '/server-signature/on/test.php' => new HttpResponse('1', '200', []),
          '/server-signature/off/test.php' => new HttpResponse('1', '200', []),
          '/module-loaded/setenvif/server-signature/test.php' => new HttpResponse('1', '200', [])
      ]);
      $testResult = $fakeServer->runTester(new ModuleLoadedTester('setenvif'));
      $this->assertSuccess($testResult);
    }

    public function testServerSignatureSucceedsModuleNotLoaded()
    {
      $fakeServer = new FakeServer();
      $fakeServer->setResponses([
          '/server-signature/on/test.php' => new HttpResponse('1', '200', []),
          '/server-signature/off/test.php' => new HttpResponse('1', '200', []),
          '/module-loaded/setenvif/server-signature/test.php' => new HttpResponse('0', '200', [])
      ]);
      $testResult = $fakeServer->runTester(new ModuleLoadedTester('setenvif'));
      $this->assertFailure($testResult);
    }

    public function testContentDigestWorksModuleLoaded()
    {
      $fakeServer = new FakeServer();
      $fakeServer->setResponses([
          '/content-digest/on/request-me.txt' => new HttpResponse(
              'hi',
              '200',
              ['Content-MD5' => 'aaoeu']
          ),
          '/content-digest/off/request-me.txt' => new HttpResponse('hi', '200', []),
          '/module-loaded/setenvif/content-digest/request-me.txt' => new HttpResponse(
              '',
              '200',
              ['Content-MD5' => 'aoeu']
          )
      ]);
      $testResult = $fakeServer->runTester(new ModuleLoadedTester('setenvif'));
      $this->assertSuccess($testResult);
    }

    public function testContentDigestWorksModuleNotLoaded()
    {
      $fakeServer = new FakeServer();
      $fakeServer->setResponses([
          '/content-digest/on/request-me.txt' => new HttpResponse(
              'hi',
              '200',
              ['Content-MD5' => 'aaoeu']
          ),
          '/content-digest/off/request-me.txt' => new HttpResponse('hi', '200', []),
          '/module-loaded/setenvif/content-digest/request-me.txt' => new HttpResponse('', '200', [])
      ]);
      $testResult = $fakeServer->runTester(new ModuleLoadedTester('setenvif'));
      $this->assertFailure($testResult);
    }

    public function testAddTypeWorksModuleLoaded()
    {
      $fakeServer = new FakeServer();
      $fakeServer->setResponses([
          '/add-type/request-me.test' => new HttpResponse(
              'hi',
              '200',
              ['Content-Type' => 'image/gif']
          ),
          '/module-loaded/setenvif/add-type/request-me.test' => new HttpResponse(
              'hi',
              '200',
              ['Content-Type' => 'image/gif']
          )
      ]);
      $testResult = $fakeServer->runTester(new ModuleLoadedTester('setenvif'));
      $this->assertSuccess($testResult);
    }

    public function testAddTypeWorksModuleNotLoaded()
    {
      $fakeServer = new FakeServer();
      $fakeServer->setResponses([
          '/add-type/request-me.test' => new HttpResponse(
              'hi',
              '200',
              ['Content-Type' => 'image/gif']
          ),
          '/module-loaded/setenvif/add-type/request-me.test' => new HttpResponse(
              'hi',
              '200',
              ['Content-Type' => 'image/jpeg']
          )
      ]);
      $testResult = $fakeServer->runTester(new ModuleLoadedTester('setenvif'));
      $this->assertFailure($testResult);
    }

    public function testDirectoryIndexWorksModuleLoaded()
    {
      $fakeServer = new FakeServer();
      $fakeServer->setResponses([
          '/directory-index/' => new HttpResponse('1', '200', []),
          '/module-loaded/setenvif/directory-index/' => new HttpResponse('1', '200', [])
      ]);
      $testResult = $fakeServer->runTester(new ModuleLoadedTester('setenvif'));
      $this->assertSuccess($testResult);
    }

    public function testDirectoryIndexWorksModuleNotLoaded()
    {
      $fakeServer = new FakeServer();
      $fakeServer->setResponses([
          '/directory-index/' => new HttpResponse('1', '200', []),
          '/module-loaded/setenvif/directory-index/' => new HttpResponse('0', '200', [])
      ]);
      $testResult = $fakeServer->runTester(new ModuleLoadedTester('setenvif'));
      $this->assertFailure($testResult);
    }

    public function testRewriteWorksModuleLoaded()
    {
      $fakeServer = new FakeServer();
      $fakeServer->setResponses([
          '/rewrite/0.txt' => new HttpResponse('1', '200', []),
          '/module-loaded/setenvif/rewrite/request-me.txt' => new HttpResponse('1', '200', []),
      ]);
      $testResult = $fakeServer->runTester(new ModuleLoadedTester('setenvif'));
      $this->assertSuccess($testResult);
    }

    public function testRewriteWorksModuleNotLoaded()
    {
      $fakeServer = new FakeServer();
      $fakeServer->setResponses([
          '/rewrite/0.txt' => new HttpResponse('1', '200', []),
          '/module-loaded/setenvif/rewrite/request-me.txt' => new HttpResponse('0', '200', []),
      ]);
      $testResult = $fakeServer->runTester(new ModuleLoadedTester('setenvif'));
      $this->assertFailure($testResult);
    }

    public function testHeaderSetWorksModuleLoaded()
    {
      $fakeServer = new FakeServer();
      $fakeServer->setResponses([
          '/header-set/request-me.txt' => new HttpResponse(
              'hi',
              '200',
              ['X-Response-Header-Test' => 'test']
          ),
          '/module-loaded/setenvif/header-set/request-me.txt' => new HttpResponse(
              'thanks',
              '200',
              ['X-Response-Header-Test' => '1']
          ),
      ]);
      $testResult = $fakeServer->runTester(new ModuleLoadedTester('setenvif'));
      $this->assertSuccess($testResult);
    }

    public function testHeaderSetWorksModuleNotLoaded()
    {
      $fakeServer = new FakeServer();
      $fakeServer->setResponses([
          '/header-set/request-me.txt' => new HttpResponse(
              'hi',
              '200',
              ['X-Response-Header-Test' => 'test']
          ),
          '/module-loaded/setenvif/header-set/request-me.txt' => new HttpResponse(
              'thanks',
              '200',
              ['X-Response-Header-Test' => '0']
          ),
      ]);
      $testResult = $fakeServer->runTester(new ModuleLoadedTester('setenvif'));
      $this->assertFailure($testResult);
    }

    public function testRequestFailure()
    {
        $fakeServer = new FakeServer();
        $fakeServer->failAllRequests();
        $testResult = $fakeServer->runTester(new ModuleLoadedTester('setenvif'));
        $this->assertInconclusive($testResult);
    }

}
