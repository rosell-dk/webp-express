<?php

namespace HtaccessCapabilityTester\Testers;

/**
 * Class for testing if ServerSignature works
 *
 * Testing the ServerSignature directive is of interest because the directive is a core feature.
 * If a core feature doesn't work, well, it it would seem that .htaccess files are disabled completely.
 * The test is thus special. If it returns *failure* it is highly probable that the .htaccess file has
 * not been read.
 *
 * Unfortunately, the test requires PHP to examine if a server variable has been set. So the test is not
 * unlikely to come out inconclusive due to a 403 Forbidden.
 *
 * Note that the test assumes that the ServerSignature directive has not been disallowed even though
 * it is technically possible to do so by setting *AllowOverride* to *None* and by setting *AllowOverrideList*
 * to a list that does not include *ServerSignature*.
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since 0.7
 */
class ServerSignatureTester extends CustomTester
{

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $phpOn = <<<'EOD'
<?php
if (isset($_SERVER['SERVER_SIGNATURE']) && ($_SERVER['SERVER_SIGNATURE'] != '')) {
    echo 1;
} else {
    echo 0;
}
EOD;

        $phpOff = <<<'EOD'
<?php
if (isset($_SERVER['SERVER_SIGNATURE']) && ($_SERVER['SERVER_SIGNATURE'] != '')) {
    echo 0;
} else {
    echo 1;
}
EOD;

        // PS:
        // There is a little edge case: When .htaccess is disabled AND phps are either not processed
        // or access is denied. This ought to return *failure*, but it currently returns *inconclusive*.

        $test = [
            'subdir' => 'server-signature',
            'subtests' => [
                [
                    'subdir' => 'on',
                    'files' => [
                        ['.htaccess', 'ServerSignature On'],
                        ['test.php', $phpOn],
                    ],
                    'request' => [
                        'url' => 'test.php',
                    ],
                    'interpretation' => [
                        ['inconclusive', 'body', 'isEmpty'],
                        ['inconclusive', 'status-code', 'not-equals', '200'],
                        ['failure', 'body', 'equals', '0'],
                    ],
                ],
                [
                    'subdir' => 'off',
                    'files' => [
                        ['.htaccess', 'ServerSignature Off'],
                        ['test.php', $phpOff],
                    ],
                    'request' => 'test.php',
                    'interpretation' => [
                        ['inconclusive', 'body', 'isEmpty'],
                        ['success', 'body', 'equals', '1'],
                        ['failure', 'body', 'equals', '0'],
                        ['inconclusive']
                    ]
                ]
            ]
        ];

        parent::__construct($test);
    }
}
