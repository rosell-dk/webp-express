<?php

namespace HtaccessCapabilityTester;


/**
 * Trait for running standard tests
 *
 * A standard tester contains a "test.php" file, which outputs one of the following:
 * - "1" if the feature works
 * - "0" if the feature does not work
 * - "null" if it could not be established if the feature works or not
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since the beginning
 */
trait TraitStandardTestRunner
{

    /**
     *  Run a standard test (one, which contains a test.php file, which outputs either "0", "1"
     *  or "null".
     *
     *  @return bool|null  Returns true if it can be established that it works, false if it can
     *                       be established that it does not work, or null if nothing could be
     *                       established due to some other failure
     */
    public function runTest() {
        $responseText = $this->makeHTTPRequest($this->baseUrl . '/' . $this->subDir . '/test.php');
        if ($responseText == '1') {
            return true;
        };
        if ($responseText == '0') {
            return false;
        };
        return null;
    }
}
