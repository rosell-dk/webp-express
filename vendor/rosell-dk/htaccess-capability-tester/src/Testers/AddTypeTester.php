<?php

namespace HtaccessCapabilityTester\Testers;

/**
 * Class for testing if AddType works
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since 0.7
 */
class AddTypeTester extends CustomTester
{

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $htaccessFile = <<<'EOD'
<IfModule mod_mime.c>
    AddType image/gif .test
</IfModule>
EOD;

        $test = [
            'subdir' => 'add-type',
            'files' => [
                ['.htaccess', $htaccessFile],
                ['request-me.test', 'hi'],
            ],
            'request' => 'request-me.test',
            'interpretation' => [
                ['success', 'headers', 'contains-key-value', 'Content-Type', 'image/gif'],
                ['inconclusive', 'status-code', 'not-equals', '200'],
                ['failure', 'headers', 'not-contains-key-value', 'Content-Type', 'image/gif'],
            ]
        ];

        parent::__construct($test);
    }
}
