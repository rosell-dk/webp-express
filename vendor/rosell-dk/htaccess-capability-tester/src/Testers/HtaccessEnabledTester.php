<?php

namespace HtaccessCapabilityTester\Testers;

use \HtaccessCapabilityTester\HtaccessCapabilityTester;
use \HtaccessCapabilityTester\TestResult;

/**
 * Class for testing if .htaccess files are processed
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since 0.7
 */
class HtaccessEnabledTester extends AbstractTester
{

    /**
     * Child classes must implement this method, which tells which subdir the
     * test files are to be put.
     *
     * @return  string  A subdir for the test files
     */
    public function getSubDir()
    {
        return 'htaccess-enabled';
    }

    /**
     * Register the test files using the "registerTestFile" method
     *
     * @return  void
     */
    public function registerTestFiles()
    {
        // No test files for this test
    }

    /**
     *  Run the test.
     *
     * @param  string  $baseDir  Directory on the server where the test files can be put
     * @param  string  $baseUrl  The base URL of the test files
     *
     * @return TestResult   Returns a test result
     */
    public function run($baseDir, $baseUrl)
    {
        $this->prepareForRun($baseDir, $baseUrl);

        /*
        PS: We could implement this as a definition:


        - [success, serverSignatureWorks, is-success]
        - [success, contentDigestWorks, is-success]
        - [failure, serverSignatureWorks, is-failure]
        - [success, canCrash, is-success]
        */


        $status = null;
        $info = '';
        $hct = $this->getHtaccessCapabilityTester();

        // If we can find anything that works, well the .htaccess must have been proccesed!
        if ($hct->serverSignatureWorks()    // Override: None,  Status: Core, REQUIRES PHP
            || $hct->contentDigestWorks()   // Override: Options,  Status: Core
            || $hct->addTypeWorks()         // Override: FileInfo, Status: Base, Module: mime
            || $hct->directoryIndexWorks()  // Override: Indexes,  Status: Base, Module: mod_dir
            || $hct->rewriteWorks()         // Override: FileInfo, Status: Extension, Module: rewrite
            || $hct->headerSetWorks()       // Override: FileInfo, Status: Extension, Module: headers
        ) {
            $status = true;
        } else {
            // The serverSignatureWorks() test is special because if it comes out as a failure,
            // we can be *almost* certain that the .htaccess has been completely disabled

            $serverSignatureWorks = $hct->serverSignatureWorks();
            if ($serverSignatureWorks === false) {
                $status = false;
                $info = 'ServerSignature directive does not work - and it is in core';
            } else {
                // Last bullet in the gun:
                // Try an .htaccess with syntax errors in it.
                // (we do this lastly because it may generate an entry in the error log)
                $crashTestResult = $hct->crashTest('aoeu', 'htaccess-enabled-malformed-htaccess');
                if (is_null($crashTestResult)) {
                    // Two scenarios:
                    // 1: All requests fails (without response code)
                    // 2: The crash test could not figure it out (ie if even innocent requests crashes)
                    $status = null;
                    $info = 'all requests fails (even innocent ones)';
                } elseif ($crashTestResult === false) {
                    // It crashed, - which means .htaccess is processed!
                    $status = true;
                    $info = 'syntax error in an .htaccess causes crash';
                } else {
                    // It did not crash. So the .htaccess is not processed, as syntax errors
                    // makes servers crash
                    $status = false;
                    $info = 'syntax error in an .htaccess does not cause crash';
                }
            }
        }
        return new TestResult($status, $info);
    }
}
