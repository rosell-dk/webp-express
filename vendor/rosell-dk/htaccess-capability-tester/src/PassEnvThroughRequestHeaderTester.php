<?php

namespace HtaccessCapabilityTester;

/**
 * Class for testing if passing an environment variable through a request header in an .htaccess
 * file works.
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since the beginning
 */
class PassEnvThroughRequestHeaderTester extends AbstractTester
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
        return 'pass-env-through-request-header-tester';
    }

    /**
     * Register the test files using the "registerTestFile" method
     *
     * @return  void
     */
    public function registerTestFiles() {

$file = <<<'EOD'
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Testing if we can pass an environment variable through a request header
    # We pass document root, because that can easily be checked by the script

    <IfModule mod_headers.c>
      RequestHeader set PASSTHROUGHHEADER "%{PASSTHROUGHHEADER}e" env=PASSTHROUGHHEADER
    </IfModule>
    RewriteRule ^test\.php$ - [E=PASSTHROUGHHEADER:%{DOCUMENT_ROOT},L]

</IfModule>
EOD;
        $this->registerTestFile('.htaccess', $file);

$file = <<<'EOD'
<?php
if (isset($_SERVER['HTTP_PASSTHROUGHHEADER'])) {
    echo ($_SERVER['HTTP_PASSTHROUGHHEADER'] == $_SERVER['DOCUMENT_ROOT'] ? 1 : 0);
    exit;
}
echo '0';
EOD;
        $this->registerTestFile('test.php', $file);
    }
}
