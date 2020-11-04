<?php

namespace HtaccessCapabilityTester\Testers;

use \HtaccessCapabilityTester\TestResult;

/**
 * Class for testing if a .htaccess results in a 500 Internal Server Error
 * (ie due to being malformed or containing directives that are unknown or not allowed)
 *
 * Notes:
 * - The tester only reports failure on a 500 Internal Server Error. All other status codes (even server errors)
 *       are treated as a success. The assumption here is that malformed .htaccess files / .htaccess
 *       files containing unknown or disallowed directives always results in a 500
 * - If your purpose is to test if a request succeeds (response 200 Ok), you should create your own class.
 *       (note that if you want to ensure that a php will succeed, make sure that a php is requested)
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since 0.7
 */
class CrashTester extends CustomTester
{

    /**
     * @param string $htaccessRules  The rules to check
     * @param string $subSubDir      subdir for the test files. If not supplied, a fingerprint of the rules will be used
     */
    public function __construct($htaccessRules, $subSubDir = null)
    {
        if (is_null($subSubDir)) {
            $subSubDir = hash('md5', $htaccessRules);
        }

        $test = [
            'subdir' => 'crash-tester/' . $subSubDir,
            'subtests' => [
                [
                    'subdir' => 'the-suspect',
                    'files' => [
                        ['.htaccess', $htaccessRules],
                        ['request-me.txt', 'thanks'],
                    ],
                    'request' => [
                        'url' => 'request-me.txt',
                        'bypass-standard-error-handling' => ['403', '404', '500']
                    ],
                    'interpretation' => [
                        ['success', 'status-code', 'not-equals', '500'],
                        // Otherwise fall through to next subtest
                    ]
                ],
                [
                    'subdir' => 'the-innocent',
                    'files' => [
                        ['.htaccess', '# I am no trouble'],
                        ['request-me.txt', 'thanks'],
                    ],
                    'request' => [
                        'url' => 'request-me.txt',
                        'bypass-standard-error-handling' => ['403', '404', '500']
                    ],
                    'interpretation' => [
                        // The suspect crashed. But if the innocent crashes too, we cannot judge
                        ['inconclusive', 'status-code', 'equals', '500'],

                        // The innocent did not crash. The suspect is guilty!
                        ['failure'],
                    ]
                ],
            ]
        ];

        parent::__construct($test);
    }

    /**
     * Child classes must implement this method, which tells which subdir the
     * test files are to be put.
     *
     * @return  string  A subdir for the test files
     */
    public function getSubDir()
    {
        return 'crash-tester';
    }
}
