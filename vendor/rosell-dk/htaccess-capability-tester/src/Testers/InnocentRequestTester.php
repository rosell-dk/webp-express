<?php

namespace HtaccessCapabilityTester\Testers;

use \HtaccessCapabilityTester\TestResult;

/**
 * Class for testing if an innocent request for a txt file succeeds
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since 0.7
 */
class InnocentRequestTester extends CustomTester
{

    public function __construct()
    {
        $test = [
            'subdir' => 'innocent-request',
            'files' => [
                ['request-me.txt', 'thank you my dear'],
            ],
            'request' => [
                'url' => 'request-me.txt',
                'bypass-standard-error-handling' => ['all']
            ],
            'interpretation' => [
                ['success', 'status-code', 'equals', '200'],
                ['inconclusive', 'status-code', 'equals', '0'],
                ['inconclusive', 'status-code', 'equals', '403'],
                ['inconclusive', 'status-code', 'equals', '404'],
                ['failure'],
            ]
        ];

        parent::__construct($test);
    }
}
