<?php

namespace HtaccessCapabilityTester\Testers;

/**
 * Say you have a rewrite rule that points to a PHP script and you would like to pass some information
 * along to the PHP. Usually, you will just pass it in the query string. But this won't do if the information
 * is sensitive. In that case, there are some tricks available. The trick being tested here sets tells the
 * RewriteRule directive to set an environment variable which a RequestHeader directive picks up on and passes
 * on to the script in a request header.
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since 0.7
 */
class PassInfoFromRewriteToScriptThroughRequestHeaderTester extends CustomTester
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
    RewriteEngine On
    # We pass document root, because that can easily be checked by the script
    RewriteRule ^test\.php$ - [E=PASSTHROUGHHEADER:%{DOCUMENT_ROOT},L]

    <IfModule mod_headers.c>
      RequestHeader set PASSTHROUGHHEADER "%{PASSTHROUGHHEADER}e" env=PASSTHROUGHHEADER
    </IfModule>

</IfModule>
EOD;

        $phpFile = <<<'EOD'
<?php
if (isset($_SERVER['HTTP_PASSTHROUGHHEADER'])) {
    echo ($_SERVER['HTTP_PASSTHROUGHHEADER'] == $_SERVER['DOCUMENT_ROOT'] ? 1 : 0);
    exit;
}
echo '0';
EOD;

        $test = [
            'subdir' => 'pass-info-from-rewrite-to-script-through-request-header',
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
