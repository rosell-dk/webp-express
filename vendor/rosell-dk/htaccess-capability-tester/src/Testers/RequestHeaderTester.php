<?php

namespace HtaccessCapabilityTester\Testers;

/**
 * Class for testing if RequestHeader works
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since 0.7
 */
class RequestHeaderTester extends CustomTester
{

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $htaccessFile = <<<'EOD'
<IfModule mod_headers.c>
  	# Certain hosts seem to strip non-standard request headers,
  	# so we use a standard one to avoid a false negative
    RequestHeader set User-Agent "request-header-test"
</IfModule>
EOD;

        $phpFile = <<<'EOD'
<?php
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    echo (($_SERVER['HTTP_USER_AGENT'] == 'request-header-test') ? "1" : "0");
} else {
    echo "0";
}
EOD;

        // PS:
        // There is a little edge case: When .htaccess is disabled AND phps are either not processed
        // or access is denied. This ought to return *failure*, but it currently returns *inconclusive*.

        $test = [
            'subdir' => 'request-header',
            'files' => [
                ['.htaccess', $htaccessFile],
                ['test.php', $phpFile],
            ],
            'request' => 'test.php',
            'interpretation' => [
                ['success', 'body', 'equals', '1'],
                ['failure', 'body', 'equals', '0'],
                ['inconclusive', 'body', 'begins-with', '<' . '?php'],
            ]
        ];

        parent::__construct($test);
    }
}
