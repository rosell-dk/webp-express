<?php

namespace HtaccessCapabilityTester;

abstract class AbstractTester
{
    use TraitTestFileCreator;

    /** @var string  The dir where the test files should be put */
    protected $baseDir;

    /** @var string  The base url that the tests can be run from (corresponds to $baseDir) */
    protected $baseUrl;

    /** @var string  A subdir */
    protected $subDir;

    /** @var array  Test files for the test */
    protected $testFiles;

    /** @var HTTPRequesterInterface  An object for making the HTTP request */
    protected $httpRequester;

    /**
     * Register the test files using the "registerTestFile" method
     *
     * @return  void
     */
    abstract protected function registerTestFiles();

    /**
     * Child classes must implement this method, which tells which subdir the
     * test files are to be put.
     *
     * @return  string  A subdir for the test files
     */
    abstract protected function getSubDir();

    /**
     * Child classes must that implement the registerTestFiles method must call
     * this method to register each test file.
     *
     * @return  void
     */
    protected function registerTestFile($fileName, $content, $subDir = '') {
        $this->testFiles[] = [$fileName, $content, $subDir];
    }

    /**
     * Child classes must implement this method - or use the trait: TraitStandardTestRunner.
     *
     * If the test involves making a HTTP request (which it probably does), the class should
     * use the makeHTTPRequest() method making the request. The result must be interpreted
     * into true, false or null. True = Success (the feature is supported), False = Failure
     * (the feature is not supported), Null = Indetermite (ie if the test could not be completed
     * due to a failure).
     *
     * @return bool|null  Returns true if it can be established that it works, false if it can
     *                       be established that it does not work, or null if nothing could be
     *                       established due to some other failure
     */
    abstract protected function runTest();

    /**
     * Constructor.
     *
     * @param  string  $baseDir  Directory on the server where the test files can be put
     * @param  string  $baseUrl  The base URL of the test files
     *
     * @return void
     */
    public function __construct($baseDir, $baseUrl) {
        $this->baseDir = $baseDir;
        $this->baseUrl = $baseUrl;
        $this->subDir = $this->getSubDir();
        $this->registerTestFiles();
        $this->createTestFilesIfNeeded();
    }

    /**
     * Make a HTTP request to a URL.
     *
     * @param  string  $url  The URL to make the HTTP request to
     * @return string  The response text
     */
    protected function makeHTTPRequest($url) {
        if (!isset($this->httpRequester)) {
            $this->httpRequester = new SimpleHttpRequester();
        }
        return $this->httpRequester->makeHTTPRequest($url);
    }

    /**
     * Set HTTP requester object, which handles making HTTP requests.
     *
     * @param  HTTPRequesterInterface  $httpRequester  The HTTPRequester to use
     * @return void
     */
    public function setHTTPRequester($httpRequester) {
        $this->httpRequester = $httpRequester;
    }


}
