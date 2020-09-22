<?php
/*
subdir: directory-index
files:
  - filename: '.htaccess'
    content: |
      <IfModule mod_dir.c>
          DirectoryIndex index2.html
      </IfModule>
  - filename: 'index.html'
    content: '0'
  - filename: 'index2.html'
    content: '1'

request:
  url: ''   # We request the index, that is why its empty
  bypass-standard-error-handling: ['404']

interpretation:
  - ['success', 'body', 'equals', '1']
  - ['failure', 'body', 'equals', '0']
  - ['failure', 'status-code', 'equals', '404']  # "index.html" might not be set to index

----

Tested:

Server setup                   |  Test result
--------------------------------------------------
.htaccess disabled             |  failure
forbidden directives (fatal)   |  failure       (highly unlikely, as it is part of core - but still possible)
access denied                  |  inconclusive  (it might be allowed to other files)
directive has no effect        |  failure
                               |  success

*/


namespace HtaccessCapabilityTester\Tests\Testers;

use HtaccessCapabilityTester\HttpResponse;
use HtaccessCapabilityTester\Testers\DirectoryIndexTester;
use HtaccessCapabilityTester\Tests\FakeServer;
use PHPUnit\Framework\TestCase;

class DirectoryIndexTesterTest extends BasisTestCase
{

    public function testHtaccessDisabled()
    {
        $fakeServer = new FakeServer();
        $fakeServer->disableHtaccess();
        $testResult = $fakeServer->runTester(new DirectoryIndexTester());
        $this->assertFailure($testResult);
    }

    public function testDisallowedDirectivesFatal()
    {
        $fakeServer = new FakeServer();
        $fakeServer->disallowAllDirectives('fatal');
        $testResult = $fakeServer->runTester(new DirectoryIndexTester());
        $this->assertFailure($testResult);
    }

    public function testAccessAllDenied()
    {
        $fakeServer = new FakeServer();
        $fakeServer->denyAllAccess();
        $testResult = $fakeServer->runTester(new DirectoryIndexTester());
        $this->assertInconclusive($testResult);
    }

    /**
     * Test when the directive has no effect.
     * This could happen when:
     * - The directive is forbidden (non-fatal)
     * - The module is not loaded
     *
     */
    public function testDirectiveHasNoEffect()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/directory-index/' => new HttpResponse('0', '200', []),
        ]);
        $testResult = $fakeServer->runTester(new DirectoryIndexTester());
        $this->assertFailure($testResult);
    }

    public function testSuccess()
    {
        $fakeServer = new FakeServer();
        $fakeServer->setResponses([
            '/directory-index/' => new HttpResponse('1', '200', [])
        ]);
        $testResult = $fakeServer->runTester(new DirectoryIndexTester());
        $this->assertSuccess($testResult);
    }

}
