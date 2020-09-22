<?php

namespace HtaccessCapabilityTester\Testers;

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
 *     directive (this is, there is a risk for a false negative)
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
 * @since      Class available since 0.7
 */
class RewriteTester extends CustomTester
{

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
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
#      (regardless if configured to Nonfatal or not): "Invalid command 'RewriteEngine', perhaps
#      misspelled or defined by a module not included
#      in the server configuration"

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^0\.txt$ 1\.txt [L]
</IfModule>
EOD;

        $test = [
            'subdir' => 'rewrite',
            'files' => [
                ['.htaccess', $htaccessFile],
                ['0.txt', "0"],
                ['1.txt', "1"]
            ],
            'request' => '0.txt',
            'interpretation' => [
                ['success', 'body', 'equals', '1'],
                ['failure', 'body', 'equals', '0'],
            ]
        ];

        parent::__construct($test);
    }
}
