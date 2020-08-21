<?php

namespace HtaccessCapabilityTester;

/**
 * Class for testing if rewriting works at the tested location.
 *
 * The tester reports success when:
 * - a rewrite is proven to be working
 *
 * The tester reports failure when:
 * - Server does not have mod_rewrite installed
 * - Server is set up to ignore .htaccess files in the directory
 * - Server disallows any the following directives in the directory: RewriteEngine, Rewrite, IfModule
 *     (if disallowed, the result is either a 500 Internal Server Error or that the directive is
 *     ignored, depending on whether Nonfatal is set)
 * - The request results in a 500 Internal Server Error due to another problem than a disallowed
 *     directive (this is a false negative)
 *
 * The test works by creating an .htaccess which redirects requests to "0.txt"
 * to "1.txt" and then requesting "0.txt".
 *
 * Notes:
 * - The test might result in the following being written to the error log:
 *      "RewriteEngine not allowed here"
 * - We are not redirecting to a php, because that would additionally require phps
 *      to be run in that directory
 * - We are wrapping the .htaccess directives in a "<IfModule mod_rewrite.c>" and therefore this test
 *      also relies on the IfModule directive being allowed. It probably usually is, as it is harmless.
 *      Also, it is good practice to use it, so in most cases it is good that this is checked
 *      too. Actually, the <IfModule> wrap isn't neccessary for our test to work, as the test
 *      identifies a 500 Internal Error as test failure. However, not having the wrap would
 *      cause the test to generate an entry in the error log when mod_rewrite isn't installed
 *      (regardless if overrides are configured to Nonfatal or not):
 *      "Invalid command 'RewriteEngine', perhaps misspelled or defined by a module not included
 *      in the server configuration"
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since the beginning
 */
class RewriteTester extends AbstractTester
{

    /**
     * Child classes must implement this method, which tells which subdir the
     * test files are to be put.
     *
     * @return  string  A subdir for the test files
     */
    public function getSubDir()
    {
        return 'rewrite-tester';
    }

    /**
     * Register the test files using the "registerTestFile" method
     *
     * @return  void
     */
    public function registerTestFiles() {
        $htaccessFile = <<<'EOD'

# Testing for mod_rewrite
# -----------------------
# If mod_rewrite is enabled, redirect to 1.txt, which returns "1".
# If mod_rewrite is disabled, the rewriting fails, and we end at 0.txt, which returns "0".
#
# Notes:
# - We are not redirecting to a php, because that would additionally require phps
#      to be run in that directory
# - We are wrapping it in a "<IfModule mod_rewrite.c>" and therefore this test also relies
#      on the IfModule directive being allowed. It probably usually is, as it is harmless.
#      Also, it is good practice to use it, so in most cases it is good that this is checked
#      too. Actually, the <IfModule> wrap isn't neccessary for our test to work, as the test
#      identifies a 500 Internal Error as test failure. However, not having the wrap would
#      cause the test to generate an entry in the error log when mod_rewrite isn't installed
#      (regardless if overrides are configured to Nonfatal or not):
#      "Invalid command 'RewriteEngine', perhaps misspelled or defined by a module not included
#      in the server configuration"

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^0\.txt$ 1.txt [L]
</IfModule>

EOD;

        $this->registerTestFile('.htaccess', $htaccessFile);
        $this->registerTestFile('0.txt', "0");
        $this->registerTestFile('1.txt', "1");
    }

    /**
     *  Run the rewrite test.
     *
     *  @return bool|null  Returns true if rewriting works in the directory being tested, false if
     *                        does not.
     */
    public function runTest() {
        $responseText = $this->makeHTTPRequest($this->baseUrl . '/' . $this->subDir . '/0.txt');
        //echo $this->baseUrl . '/' . $this->subDir . '/0.txt' . ':' .$responseText;
        if ($responseText == '1') {
            return true;
        };
        return false;
    }

}
