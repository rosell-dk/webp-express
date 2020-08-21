<?php

namespace HtaccessCapabilityTester;

/**
 * Abstract class for testing if a .htaccess results in a 500 Internal Server Error
 * (ie due to being malformed or containing directives that are unknown or not allowed)
 *
 * The tester reports success when:
 * - A request to a certain file in the directory does not result in a 500 Internal Server Error
 *
 * The tester reports failure when:
 * - A request to a certain file in the directory results in a 500 Internal Server Error
 *
 * The tester reports indeterminate (null) when:
 * - get_headers() call fails (What kind of failure could this be, I wonder?)
 *
 * Notes:
 * - There might be false negatives, as there could be other reasons behind a 501 error than
 *       than a malformed .htaccess.
 * - The tester only reports failure on a 500 Internal Server Error. All other status codes (even server errors)
 *       are treated as a success. The assumption here is that malformed .htaccess files / .htaccess
 *       files containing unknown or disallowed directives always results in a 500
 * - If your purpose is to test if a request succeeds (response 200 Ok), you should create your own class.
 *       (note that if you want to ensure that a php will succeed, make sure that a php is requested)
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since the beginning
 */
abstract class AbstractCrashTester extends AbstractTester
{

    /**
     * Get the .htaccess content to crash-test.
     *
     * @return  string  The file content of the .htaccess
     */
    abstract protected function getHtaccessToCrashTest();

    /**
     * Register the test files using the "registerTestFile" method
     *
     * @return  void
     */
    public function registerTestFiles() {

        $file = $this->getHtaccessToCrashTest();

        $this->registerTestFile('.htaccess', $file);
        $this->registerTestFile('ping.txt', "pong");
    }

    /**
     *  Run the rewrite test.
     *
     *  @return bool|null  Returns true if rewriting works in the directory being tested, false if
     *                        does not.
     */
    public function runTest() {
        $headers = get_headers($this->baseUrl . '/' . $this->subDir . '/ping.txt', 1);
        if ($headers === false) {
            // What kind of failure could this be, I wonder?
            return null;
        }
        $responseCode = explode(' ', $headers[0])[1];
        return ($responseCode != '500');

        // Could for example be:
        // 200 Ok
        // 403 Forbidden
        // 500 Internal Server Error
        // - and a lot more: https://restfulapi.net/http-status-codes/

        //print_r('code:' . $responseCode);
        /*
        $responseText = $this->makeHTTPRequest($this->baseUrl . '/' . $this->subDir . '/ping.txt');
        //echo $this->baseUrl . '/' . $this->subDir . '/0.txt' . ':' .$responseText;
        if ($responseText == 'pong') {
            return true;
        };
        return false;
        */
    }

}
