<?php

namespace HtaccessCapabilityTester;

/**
 * Class for holding properties of a TestResult
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since the beginning
 */
class TestResult
{

    /* @var bool|null   The result, null if inconclusive */
    public $status;

    /* @var string   Information about how the test failed / became inconclusive */
    public $info;

    /* @var string   Status code of last request in the test */
    public $statusCodeOfLastRequest;

    /**
     * Constructor.
     *
     * @param  bool|null  $status
     * @param  string     $info
     * @param  string     $statusCodeOfLastRequest  (optional)
     *
     * @return void
     */
    public function __construct($status, $info, $statusCodeOfLastRequest = null)
    {
        $this->status = $status;
        $this->info = $info;
        $this->statusCodeOfLastRequest = $statusCodeOfLastRequest;
    }
}
