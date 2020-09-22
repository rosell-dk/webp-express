<?php

namespace HtaccessCapabilityTester\Testers;

/**
 * Class for testing if an environment variable can be set in a rewrite rule and received in PHP.
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since 0.7
 */
class PassInfoFromRewriteToScriptThroughEnvTester extends CustomTester
{

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $htaccessFile = <<<'EOD'
<IfModule mod_rewrite.c>

    # Testing if we can pass environment variable from .htaccess to script in a RewriteRule
    # We pass document root, because that can easily be checked by the script

    RewriteEngine On
    RewriteRule ^test\.php$ - [E=PASSTHROUGHENV:%{DOCUMENT_ROOT},L]

</IfModule>
EOD;

        $phpFile = <<<'EOD'
<?php

/**
 *  Get environment variable set with mod_rewrite module
 *  Return false if the environment variable isn't found
 */
function getEnvPassedInRewriteRule($envName) {
    // Environment variables passed through the REWRITE module have "REWRITE_" as a prefix
    // (in Apache, not Litespeed, if I recall correctly).
    // Multiple iterations causes multiple REWRITE_ prefixes, and we get many environment variables set.
    // We simply look for an environment variable that ends with what we are looking for.
    // (so make sure to make it unique)
    $len = strlen($envName);
    foreach ($_SERVER as $key => $item) {
        if (substr($key, -$len) == $envName) {
            return $item;
        }
    }
    return false;
}

$result = getEnvPassedInRewriteRule('PASSTHROUGHENV');
if ($result === false) {
    echo '0';
    exit;
}
echo ($result == $_SERVER['DOCUMENT_ROOT'] ? '1' : '0');
EOD;

        $test = [
            'subdir' => 'pass-info-from-rewrite-to-script-through-env',
            'files' => [
                ['.htaccess', $htaccessFile],
                ['test.php', $phpFile],
            ],
            'request' => 'test.php',
            'interpretation' => [
                ['success', 'body', 'equals', '1'],
                ['failure', 'body', 'equals', '0'],
                ['inconclusive', 'body', 'begins-with', '<' . '?php'],
                ['inconclusive']
            ]
        ];

        parent::__construct($test);
    }
}
