<?php

namespace HtaccessCapabilityTester\Testers;

use \HtaccessCapabilityTester\TestResult;

/**
 * Class for testing if a module is loaded.
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since 0.7
 */
class ModuleLoadedTester extends AbstractTester
{

    /* @var string A valid Apache module name (ie "rewrite") */
    protected $moduleName;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct($moduleName)
    {
        $this->moduleName = $moduleName;
    }

    /**
     * Child classes must implement this method, which tells which subdir the
     * test files are to be put.
     *
     * @return  string  A subdir for the test files
     */
    public function getSubDir()
    {
        return 'module-loaded/' . $this->moduleName;
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

    private function getServerSignatureBasedTest()
    {
        // Test files, method : Using ServerSignature
        // --------------------------------------------------
        // Requires (in order not to be inconclusive):
        // - Override: All
        // - Status: Core
        // - Directives: ServerSignature, IfModule
        // - PHP?: Yes

        $php = <<<'EOD'
<?php
if (isset($_SERVER['SERVER_SIGNATURE']) && ($_SERVER['SERVER_SIGNATURE'] != '')) {
    echo 1;
} else {
    echo 0;
}
EOD;

        $htaccess = <<<'EOD'
# The beauty of this trick is that ServerSignature is available in core.
# (it requires no modules and cannot easily be made forbidden)
# However, it requires PHP to check for the effect

ServerSignature Off
<IfModule mod_xxx.c>
ServerSignature On
</IfModule>
EOD;

        $htaccess = str_replace('mod_xxx', 'mod_' . $this->moduleName, $htaccess);

        return [
            'subdir' => $this->getSubDir() . '/server-signature',
            'files' => [
                ['.htaccess', $htaccess],
                ['test.php', $php],
            ],
            'request' => 'test.php',
            'interpretation' => [
                ['success', 'body', 'equals', '1'],
                ['failure', 'body', 'equals', '0'],
                // This time we do not fail for 500 because it is very unlikely that any of the
                // directives used are forbidden
            ]
        ];
    }

    /**
     *  @return  array
     */
    private function getRewriteBasedTest()
    {
        // Test files, method: Using Rewrite
        // --------------------------------------------------
        // Requires (in order not to be inconclusive)
        // - Module: mod_rewrite
        // - Override: FileInfo
        // - Directives: RewriteEngine, RewriteRule and IfModule
        // - PHP?: No

        $htaccess = <<<'EOD'
RewriteEngine On
<IfModule mod_xxx.c>
    RewriteRule ^request-me\.txt$ 1.txt [L]
</IfModule>
<IfModule !mod_xxx.c>
    RewriteRule ^request-me\.txt$ 0.txt [L]
</IfModule>
EOD;

        $htaccess = str_replace('mod_xxx', 'mod_' . $this->moduleName, $htaccess);

        return [
            'subdir' => $this->getSubDir() . '/rewrite',
            'files' => [
                ['.htaccess', $htaccess],
                ['0.txt', '0'],
                ['1.txt', '1'],
                ['request-me.txt', 'Redirect failed even though rewriting has been proven to work. Strange!'],
            ],
            'request' => 'request-me.txt',
            'interpretation' => [
                ['success', 'body', 'equals', '1'],
                ['failure', 'body', 'equals', '0'],
                //['inconclusive', 'status-code', 'not-equals', '200'],
            ]
        ];
    }

    /**
     *  @return  array
     */
    private function getHeaderSetBasedTest()
    {

        // Test files, method: Using Response Header
        // --------------------------------------------------
        // Requires (in order not to be inconclusive)
        // - Module: mod_headers
        // - Override: FileInfo
        // - Directives: Header and IfModule
        // - PHP?: No

        $htaccess = <<<'EOD'
<IfModule mod_xxx.c>
    Header set X-Response-Header-Test: 1
</IfModule>
<IfModule !mod_xxx.c>
    Header set X-Response-Header-Test: 0
</IfModule>
EOD;

        $htaccess = str_replace('mod_xxx', 'mod_' . $this->moduleName, $htaccess);

        return [
            'subdir' => $this->getSubDir() . '/header-set',
            'files' => [
                ['.htaccess', $htaccess],
                ['request-me.txt', 'thanks'],
            ],
            'request' => 'request-me.txt',
            'interpretation' => [
                ['success', 'headers', 'contains-key-value', 'X-Response-Header-Test', '1'],
                ['failure', 'headers', 'contains-key-value', 'X-Response-Header-Test', '0'],
            ]
        ];
    }

    /**
     *  @return  array
     */
    private function getContentDigestBasedTest()
    {
        // Test files, method: Using ContentDigest
        // --------------------------------------------------
        //
        // Requires (in order not to be inconclusive)
        // - Module: None - its in core
        // - Override: Options
        // - Directives: ContentDigest
        // - PHP?: No

        $htaccess = <<<'EOD'
<IfModule mod_xxx.c>
    ContentDigest On
</IfModule>
<IfModule !mod_xxx.c>
    ContentDigest Off
</IfModule>
EOD;

        $htaccess = str_replace('mod_xxx', 'mod_' . $this->moduleName, $htaccess);

        return [
            'subdir' => $this->getSubDir() . '/content-digest',
            'files' => [
                ['.htaccess', $htaccess],
                ['request-me.txt', 'thanks'],
            ],
            'request' => 'request-me.txt',
            'interpretation' => [
                ['success', 'headers', 'contains-key', 'Content-MD5'],
                ['failure', 'headers', 'not-contains-key', 'Content-MD5'],
            ]
        ];
    }

    /**
     *  @return  array
     */
    private function getDirectoryIndexBasedTest()
    {
        // Test files, method: Using DirectoryIndex
        // --------------------------------------------------
        //
        // Requires (in order not to be inconclusive)
        // - Module: mod_dir (Status: Base)
        // - Override: Indexes
        // - Directives: DirectoryIndex
        // - PHP?: No

        $htaccess = <<<'EOD'
<IfModule mod_xxx.c>
    DirectoryIndex 1.html
</IfModule>
<IfModule !mod_xxx.c>
    DirectoryIndex 0.html
</IfModule>
EOD;

        $htaccess = str_replace('mod_xxx', 'mod_' . $this->moduleName, $htaccess);

        return [
            'subdir' => $this->getSubDir() . '/directory-index',
            'files' => [
                ['.htaccess', $htaccess],
                ['0.html', '0'],
                ['1.html', '1'],
            ],
            'request' => '',        // empty - in order to request the index
            'interpretation' => [
                ['success', 'body', 'equals', '1'],
                ['failure', 'body', 'equals', '0'],
            ]
        ];
    }


    /**
     *  @return  array
     */
    private function getAddTypeBasedTest()
    {
        // Test files, method: Using AddType
        // --------------------------------------------------
        //
        // Requires (in order not to be inconclusive)
        // - Module: mod_mime
        // - Override: FileInfo
        // - Directives: AddType and IfModule
        // - PHP?: No

        $htaccess = <<<'EOD'
<IfModule mod_xxx.c>
    AddType image/gif .test
</IfModule>
<IfModule !mod_xxx.c>
    AddType image/jpeg .test
</IfModule>
EOD;

        $htaccess = str_replace('mod_xxx', 'mod_' . $this->moduleName, $htaccess);

        return [
            'subdir' => $this->getSubDir() . '/add-type',
            'files' => [
                ['.htaccess', $htaccess],
                ['request-me.test', 'hi'],
            ],
            'request' => 'request-me.test',
            'interpretation' => [
                ['success', 'headers', 'contains-key-value', 'Content-Type', 'image/gif'],
                ['failure', 'headers', 'contains-key-value', 'Content-Type', 'image/jpeg'],
            ]
        ];
    }

    /**
     * @return  bool|null
     */
    private function run2()
    {
        $hct = $this->getHtaccessCapabilityTester();

        $testResult = $hct->customTest($this->getServerSignatureBasedTest());
        if (!is_null($testResult)) {
            // PHP
            return $testResult;
        }

        if ($hct->contentDigestWorks()) {
            // Override: Options
            return $hct->customTest($this->getContentDigestBasedTest());
        }

        if ($hct->addTypeWorks()) {
            // Override: FileInfo, Status: Base (mod_mime)
            return $hct->customTest($this->getAddTypeBasedTest());
        }

        if ($hct->directoryIndexWorks()) {
            // Override: Indexes, Status: Base (mod_dir)
            return $hct->customTest($this->getDirectoryIndexBasedTest());
        }

        if ($hct->rewriteWorks()) {
            // Override: FileInfo, Module: mod_rewrite
            return $hct->customTest($this->getRewriteBasedTest());
        }

        if ($hct->headerSetWorks()) {
            //Override: FileInfo, Module: mod_headers
            return $hct->customTest($this->getHeaderSetBasedTest());
        }
        return null;
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

        $hct = $this->getHtaccessCapabilityTester();

        $htaccessEnabledTest = $hct->htaccessEnabled();
        if ($htaccessEnabledTest === false) {
            return new TestResult(false, '.htaccess files are ignored');
        } elseif (is_null($htaccessEnabledTest)) {
            // We happen to know that if that test cannot establish anything,
            // then none of the usual weapons works - we can surrender right away
            return new TestResult(null, 'no methods available - we surrender early');
        }

        $status = $this->run2();
        if (is_null($status)) {
            return new TestResult(null, 'no methods worked');
        } else {
            return new TestResult($status, '');
        }
    }
}
