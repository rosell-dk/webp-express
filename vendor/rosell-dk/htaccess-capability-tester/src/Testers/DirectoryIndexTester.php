<?php

namespace HtaccessCapabilityTester\Testers;

/**
 * Class for testing if DirectoryIndex works
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since 0.7
 */
class DirectoryIndexTester extends CustomTester
{

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $htaccessFile = <<<'EOD'
<IfModule mod_dir.c>
    DirectoryIndex index2.html
</IfModule>
EOD;

        $test = [
            'subdir' => 'directory-index',
            'files' => [
                ['.htaccess', $htaccessFile],
                ['index.html', "0"],
                ['index2.html', "1"]
            ],
            'request' => [
                'url' => '',    // We request the index, that is why its empty
                'bypass-standard-error-handling' => ['404']
            ],
            'interpretation' => [
                ['success', 'body', 'equals', '1'],
                ['failure', 'body', 'equals', '0'],
                ['failure', 'status-code', 'equals', '404'],  // "index.html" might not be set to index
            ]
        ];

        parent::__construct($test);
    }
}
