<?php

namespace HtaccessCapabilityTester\Testers;

use \HtaccessCapabilityTester\HtaccessCapabilityTester;
use \HtaccessCapabilityTester\HttpRequesterInterface;
use \HtaccessCapabilityTester\HttpResponse;
use \HtaccessCapabilityTester\SimpleHttpRequester;
use \HtaccessCapabilityTester\TestResult;
use \HtaccessCapabilityTester\Testers\Helpers\ResponseInterpreter;

class CustomTester extends AbstractTester
{
    /** @var array  A definition defining the test */
    protected $test;

    /** @var array  For convenience, all tests */
    private $tests;

    /**
     * Constructor.
     *
     * @param  array   $test     The test (may contain subtests)
     *
     * @return void
     */
    public function __construct($test)
    {
        $this->test = $test;

        if (isset($test['subtests'])) {
            $this->tests = $test['subtests'];

            // Add main subdir to subdir for all subtests
            foreach ($this->tests as &$subtest) {
                if (isset($subtest['subdir'])) {
                    $subtest['subdir'] = $test['subdir'] . '/' . $subtest['subdir'];
                }
            }
        } else {
            $this->tests = [$test];
        }

        //echo '<pre>' . print_r($this->tests, true) . '</pre>';
        //echo json_encode($this->tests) . '<br>';
        parent::__construct();
    }

    /**
     * Register the test files using the "registerTestFile" method
     *
     * @return  void
     */
    protected function registerTestFiles()
    {

        foreach ($this->tests as $test) {
            if (isset($test['files'])) {
                foreach ($test['files'] as $file) {
                    // Two syntaxes are allowed:
                    // - Simple array (ie: ['0.txt', '0']
                    // - Named, ie:  ['filename' => '0.txt', 'content' => '0']
                    // The second makes more readable YAML definitions
                    if (isset($file['filename'])) {
                        $filename = $file['filename'];
                        $content = $file['content'];
                    } else {
                        list ($filename, $content) = $file;
                    }
                    $this->registerTestFile($test['subdir'] . '/' . $filename, $content);
                }
            }
        }
    }

    public function getSubDir()
    {
        return $this->test['subdir'];
    }



    /**
     *  Run
     *
     * @param  string  $baseDir  Directory on the server where the test files can be put
     * @param  string  $baseUrl  The base URL of the test files
     *
     * @return TestResult  Returns a test result
     * @throws \Exception  In case the test cannot be run due to serious issues
     */
    public function run($baseDir, $baseUrl)
    {
        $testResult = $this->realRun($baseDir, $baseUrl);

        // A test might not create a request if it has an unfulfilled requirement
        if (isset($this->lastHttpResponse)) {
            $testResult->statusCodeOfLastRequest = $this->lastHttpResponse->statusCode;
        }
        return $testResult;
    }

    /**
     *  Run
     *
     * @param  string  $baseDir  Directory on the server where the test files can be put
     * @param  string  $baseUrl  The base URL of the test files
     *
     * @return TestResult  Returns a test result
     * @throws \Exception  In case the test cannot be run due to serious issues
     */
    private function realRun($baseDir, $baseUrl)
    {
        $this->prepareForRun($baseDir, $baseUrl);

        $result = null;
        foreach ($this->tests as $i => $test) {
            /*
            Disabled, as I'm no longer sure if it is that useful

            if (isset($test['requirements'])) {
                $hct = $this->getHtaccessCapabilityTester();

                foreach ($test['requirements'] as $requirement) {
                    $requirementResult = $hct->callMethod($requirement);
                    if (!$requirementResult) {
                        // Skip test
                        continue 2;
                    }
                }
            }*/
            if (isset($test['request'])) {
                $requestUrl = $this->baseUrl . '/' . $test['subdir'] . '/';
                if (isset($test['request']['url'])) {
                    $requestUrl .= $test['request']['url'];
                } else {
                    $requestUrl .= $test['request'];
                }
                //echo $requestUrl . '<br>';
                $response = $this->makeHttpRequest($requestUrl);

                // Standard error handling
                $bypassErrors = [];
                $byPass = false;
                if (isset($test['request']['bypass-standard-error-handling'])) {
                    $bypassErrors = $test['request']['bypass-standard-error-handling'];
                }
                if (in_array($response->statusCode, $bypassErrors) || in_array('all', $bypassErrors)) {
                    $byPass = true;
                }

                if (!$byPass) {
                    if ($response->statusCode == '403') {
                        return new TestResult(null, '403 Forbidden');
                    } elseif ($response->statusCode == '404') {
                        return new TestResult(null, '404 Not Found');
                    } elseif ($response->statusCode == '500') {
                        $hct = $this->getHtaccessCapabilityTester();

                        // Run innocent request / get it from cache. This sets
                        // $statusCodeOfLastRequest, which we need now
                        $hct->innocentRequestWorks();
                        if ($hct->statusCodeOfLastRequest == '500') {
                            return new TestResult(null, 'Errored with 500. Everything errors with 500.');
                        } else {
                            return new TestResult(
                                false,
                                'Errored with 500. ' .
                                'Not all goes 500, so it must be a forbidden directive in the .htaccess'
                            );
                        }
                    }
                }

                $result = ResponseInterpreter::interpret($response, $test['interpretation']);
                if ($result->info != 'no-match') {
                    return $result;
                }
            }
        }
        if (is_null($result)) {
            $result = new TestResult(null, 'Nothing to test!');
        }
        return $result;
    }
}
