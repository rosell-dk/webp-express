<?php

namespace HtaccessCapabilityTester;

use \HtaccessCapabilityTester\Testers\AbstractTester;
use \HtaccessCapabilityTester\Testers\AddTypeTester;
use \HtaccessCapabilityTester\Testers\ContentDigestTester;
use \HtaccessCapabilityTester\Testers\CrashTester;
use \HtaccessCapabilityTester\Testers\CustomTester;
use \HtaccessCapabilityTester\Testers\DirectoryIndexTester;
use \HtaccessCapabilityTester\Testers\HeaderSetTester;
use \HtaccessCapabilityTester\Testers\HtaccessEnabledTester;
use \HtaccessCapabilityTester\Testers\InnocentRequestTester;
use \HtaccessCapabilityTester\Testers\ModuleLoadedTester;
use \HtaccessCapabilityTester\Testers\PassInfoFromRewriteToScriptThroughRequestHeaderTester;
use \HtaccessCapabilityTester\Testers\PassInfoFromRewriteToScriptThroughEnvTester;
use \HtaccessCapabilityTester\Testers\RewriteTester;
use \HtaccessCapabilityTester\Testers\RequestHeaderTester;
use \HtaccessCapabilityTester\Testers\ServerSignatureTester;

/**
 * Main entrance.
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since 0.7
 */
class HtaccessCapabilityTester
{

    /** @var string  The dir where the test files should be put */
    protected $baseDir;

    /** @var string  The base url that the tests can be run from (corresponds to $baseDir) */
    protected $baseUrl;

    /** @var string  Additional info regarding last test (often empty) */
    public $infoFromLastTest;

    /** @var string  Status code from last test (can be empty) */
    public $statusCodeOfLastRequest;


    /** @var HttpRequesterInterface  The object used to make the HTTP request */
    private $requester;

    /** @var TestFilesLineUpperInterface  The object used to line up the test files */
    private $testFilesLineUpper;

    /**
     * Constructor.
     *
     * @param  string  $baseDir  Directory on the server where the test files can be put
     * @param  string  $baseUrl  The base URL of the test files
     *
     * @return void
     */
    public function __construct($baseDir, $baseUrl)
    {
        $this->baseDir = $baseDir;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Run a test, store the info and return the status.
     *
     * @param  AbstractTester  $tester
     *
     * @return bool|null   true=success, false=failure, null=inconclusive
     */
    private function runTest($tester)
    {
        //$tester->setHtaccessCapabilityTester($this);
        if (isset($this->requester)) {
            $tester->setHttpRequester($this->requester);
        }
        if (isset($this->testFilesLineUpper)) {
            $tester->setTestFilesLineUpper($this->testFilesLineUpper);
        }
        //$tester->setHtaccessCapabilityTester($this);

        $cacheKeys = [$this->baseDir, $tester->getCacheKey()];
        if (TestResultCache::isCached($cacheKeys)) {
            $testResult = TestResultCache::getCached($cacheKeys);
        } else {
            $testResult = $tester->run($this->baseDir, $this->baseUrl);
            TestResultCache::cache($cacheKeys, $testResult);
        }

        $this->infoFromLastTest = $testResult->info;
        $this->statusCodeOfLastRequest = $testResult->statusCodeOfLastRequest;
        return $testResult->status;
    }

    /**
     * Run a test, store the info and return the status.
     *
     * @param  HttpRequesterInterface  $requester
     *
     * @return void
     */
    public function setHttpRequester($requester)
    {
        $this->requester = $requester;
    }

    /**
     * Set object responsible for lining up the test files.
     *
     * @param  TestFilesLineUpperInterface  $testFilesLineUpper
     * @return void
     */
    public function setTestFilesLineUpper($testFilesLineUpper)
    {
        $this->testFilesLineUpper = $testFilesLineUpper;
    }

    /**
     * Test if .htaccess files are enabled
     *
     * Apache can be configured to completely ignore .htaccess files. This test examines
     * if .htaccess files are proccesed.
     *
     * @return bool|null   true=success, false=failure, null=inconclusive
     */
    public function htaccessEnabled()
    {
        return $this->runTest(new HtaccessEnabledTester());
    }

    /**
     * Test if a module is loaded.
     *
     * This test detects if directives inside a "IfModule" is run for a given module
     *
     * @param string       $moduleName  A valid Apache module name (ie "rewrite")
     * @return bool|null   true=success, false=failure, null=inconclusive
     */
    public function moduleLoaded($moduleName)
    {
        return $this->runTest(new ModuleLoadedTester($moduleName));
    }

    /**
     * Test if rewriting works.
     *
     * The .htaccess in this test uses the following directives:
     * - IfModule
     * - RewriteEngine
     * - Rewrite
     *
     * @return bool|null   true=success, false=failure, null=inconclusive
     */
    public function rewriteWorks()
    {
        return $this->runTest(new RewriteTester());
    }

    /**
     * Test if AddType works.
     *
     * The .htaccess in this test uses the following directives:
     * - IfModule (core)
     * - AddType  (mod_mime, FileInfo)
     *
     * @return bool|null   true=success, false=failure, null=inconclusive
     */
    public function addTypeWorks()
    {
        return $this->runTest(new AddTypeTester());
    }

    /**
     * Test if setting a Response Header with the Header directive works.
     *
     * @return bool|null   true=success, false=failure, null=inconclusive
     */
    public function headerSetWorks()
    {
        return $this->runTest(new HeaderSetTester());
    }

    /**
     * Test if setting a Request Header with the RequestHeader directive works.
     *
     * @return bool|null   true=success, false=failure, null=inconclusive
     */
    public function requestHeaderWorks()
    {
        return $this->runTest(new RequestHeaderTester());
    }

    /**
     * Test if ContentDigest directive works.
     *
     * @return bool|null   true=success, false=failure, null=inconclusive
     */
    public function contentDigestWorks()
    {
        return $this->runTest(new ContentDigestTester());
    }

    /**
     * Test if ServerSignature directive works.
     *
     * @return bool|null   true=success, false=failure, null=inconclusive
     */
    public function serverSignatureWorks()
    {
        return $this->runTest(new ServerSignatureTester());
    }


    /**
     * Test if DirectoryIndex works.
     *
     * @return bool|null   true=success, false=failure, null=inconclusive
     */
    public function directoryIndexWorks()
    {
        return $this->runTest(new DirectoryIndexTester());
    }

    /**
     * Test a complex construct for passing information from a rewrite to a script through a request header.
     *
     * @return bool|null   true=success, false=failure, null=inconclusive
     */
    public function passingInfoFromRewriteToScriptThroughRequestHeaderWorks()
    {
        return $this->runTest(new PassInfoFromRewriteToScriptThroughRequestHeaderTester());
    }


    /**
     * Test if an environment variable can be set in a rewrite rule  and received in PHP.
     *
     * @return bool|null   true=success, false=failure, null=inconclusive
     */
    public function passingInfoFromRewriteToScriptThroughEnvWorks()
    {
        return $this->runTest(new PassInfoFromRewriteToScriptThroughEnvTester());
    }

    /**
     * Call one of the methods of this class (not all allowed).
     *
     * @param string  $functionCall  ie "rewriteWorks()"
     *
     * @return bool|null   true=success, false=failure, null=inconclusive
     */
     /*
    public function callMethod($functionCall)
    {
        switch ($functionCall) {
            case 'htaccessEnabled()':
                return $this->htaccessEnabled();
            case 'rewriteWorks()':
                return $this->rewriteWorks();
            case 'addTypeWorks()':
                return $this->addTypeWorks();
            case 'headerSetWorks()':
                return $this->headerSetWorks();
            case 'requestHeaderWorks()':
                return $this->requestHeaderWorks();
            case 'contentDigestWorks()':
                return $this->contentDigestWorks();
            case 'directoryIndexWorks()':
                return $this->directoryIndexWorks();
            case 'passingInfoFromRewriteToScriptThroughRequestHeaderWorks()':
                return $this->passingInfoFromRewriteToScriptThroughRequestHeaderWorks();
            case 'passingInfoFromRewriteToScriptThroughEnvWorks()':
                return $this->passingInfoFromRewriteToScriptThroughEnvWorks();
            default:
                throw new \Exception('The method is not callable');
        }

        // TODO:             moduleLoaded($moduleName)
    }*/

    /**
     * Crash-test some .htaccess rules.
     *
     * Tests if the server can withstand the given rules without going fatal.
     *
     * - success: if the rules does not result in status 500.
     * - failure: if the rules results in status 500 while a request to a file in a directory
     *        without any .htaccess succeeds (<> 500)
     * - inconclusive: if the rules results in status 500 while a request to a file in a directory
     *        without any .htaccess also fails (500)
     *
     * @param string       $rules   Rules to crash-test
     * @param string       $subDir  (optional) Subdir for the .htaccess to reside.
     *                              if left out, a unique string will be generated
     *
     * @return bool|null   true=success, false=failure, null=inconclusive
     */
    public function crashTest($rules, $subDir = null)
    {
        return $this->runTest(new CrashTester($rules, $subDir));
    }

    /**
     * Test an innocent request to a text file.
     *
     * If this fails, everything else will also fail.
     *
     * Possible reasons for failure:
     * - A .htaccess in a parent folder has forbidden tags / syntax errors
     *
     * Possible reasons for inconclusive (= test could not be run)
     * - 403 Forbidden
     * - 404 Not Found
     * - Request fails (ie due to timeout)
     *
     * @return bool|null   true=success, false=failure, null=inconclusive
     */
    public function innocentRequestWorks()
    {
        return $this->runTest(new InnocentRequestTester());
    }

    /**
     * Run a custom test.
     *
     * @param array       $definition
     *
     * @return bool|null   true=success, false=failure, null=inconclusive
     */
    public function customTest($definition)
    {
        return $this->runTest(new CustomTester($definition));
    }
}
