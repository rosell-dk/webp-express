<?php

namespace HtaccessCapabilityTester;

/**
 * Abstract class for making it easy to test if a .htaccess results in a 500 Internal Server Error
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since the beginning
 */
abstract class AbstractCrashTester extends AbstractTester
{

    use TraitStandardTestRunner;

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

        $this->registerTestFile('.htaccess', $file, 'subtest');

        $file = <<<'EOD'
<?php
echo '1';
EOD;
        $this->registerTestFile('subtest.php', $file, 'subtest');

        // The test.php file will test if the subtest "crashes" or not
        $file = '<?php' . "\n" .
            '$response = file_get_contents(\'' . $this->baseUrl . '/' . $this->subDir . '/subtest/subtest.php' . '\');' . "\n" .
            'echo ($response === false ? 0 : $response);' . "\n";

        $this->registerTestFile('test.php', $file);

    }
}
