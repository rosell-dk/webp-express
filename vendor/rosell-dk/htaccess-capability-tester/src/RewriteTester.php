<?php

namespace HtaccessCapabilityTester;

/**
 * Class for testing if rewriting works.
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since the beginning
 */
class RewriteTester extends AbstractTester
{

    use TraitStandardTestRunner;

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
<IfModule mod_rewrite.c>

    # Testing for mod_rewrite
    # -----------------------
    # If mod_rewrite is enabled, redirect to 1.php, which returns "1".
    # If mod_rewrite is disabled, the rewriting fails, and we end at test.php, which always returns 0.

    RewriteEngine On
    RewriteRule ^test\.php$ 1.php [L]

</IfModule>
EOD;

        $this->registerTestFile('.htaccess', $htaccessFile);
        $this->registerTestFile('1.php', "<?php\n echo '1';");
        $this->registerTestFile('test.php', "<?php\n echo '0';");

    }

}
