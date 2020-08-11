<?php

namespace WebPExpress;

abstract class SelfTestRedirectAbstract
{
    protected $config;

    public function __construct($config) {
        $this->config = $config;
    }

    /**
     * Run test for either jpeg or png
     *
     * @param  string  $rootId    (ie "uploads" or "themes")
     * @param  string  $imageType  ("jpeg" or "png")
     * @return array   [$success, $result, $createdTestFiles]
     */
    abstract protected function runTestForImageType($rootId, $imageType);

    abstract protected function getSuccessMessage();

    private function doRunTestForRoot($rootId)
    {
    //    return [true, ['hello'], false];
//        return [false, SelfTestHelper::diagnoseFailedRewrite($this->config, $headers), false];

        $result = [];

        //$result[] = '*hello* with *you* and **you**. ok! FAILED';
        $result[] = '## ' . $rootId;
        //$result[] = 'This test examines image responses "from the outside".';

        $createdTestFiles = false;

        if ($this->config['image-types'] & 1) {
            list($success, $subResult, $createdTestFiles) = $this->runTestForImageType($rootId, 'jpeg');
            $result = array_merge($result, $subResult);

            if ($success) {
                if ($this->config['image-types'] & 2) {
                    $result[] = '### Performing same tests for PNG';
                    list($success, $subResult, $createdTestFiles2) = $this->runTestForImageType($rootId, 'png');
                    $createdTestFiles = $createdTestFiles || $createdTestFiles2;
                    if ($success) {
                        //$result[count($result) - 1] .= '. **ok**{: .ok}';
                        $result[] .= 'All tests passed for PNG as well.';
                        $result[] = '(I shall spare you for the report, which is almost identical to the one above)';
                    } else {
                        $result = array_merge($result, $subResult);
                    }
                }
            }
        } else {
            list($success, $subResult, $createdTestFiles) = $this->runTestForImageType($rootId, 'png');
            $result = array_merge($result, $subResult);
        }

        if ($success) {
            $result[] = '### Results for ' . strtoupper($rootId);

            $result[] = $this->getSuccessMessage();
        }
        return [true, $result, $createdTestFiles];
    }

    private function runTestForRoot($rootId)
    {
        // TODO: move that method to here
        SelfTestHelper::cleanUpTestImages($rootId, $this->config);

        // Run the actual test
        list($success, $result, $createdTestFiles) = $this->doRunTestForRoot($rootId);

        // Clean up test images again. We are very tidy around here
        if ($createdTestFiles) {
            $result[] = 'Deleting test images';
            SelfTestHelper::cleanUpTestImages($rootId, $this->config);
        }

        return [$success, $result];
    }

    abstract protected function startupTests();

    protected function startTest()
    {

        list($success, $result) = $this->startupTests();

        if (!$success) {
            return [false, $result];
        }

        if (!file_exists(Paths::getConfigFileName())) {
            $result[] = 'Hold on. You need to save options before you can run this test. There is no config file yet.';
            return [true, $result];
        }

        if ($this->config['image-types'] == 0) {
            $result[] = 'No image types have been activated, nothing to test';
            return [true, $result];
        }

        foreach ($this->config['scope'] as $rootId) {
            list($success, $subResult) = $this->runTestForRoot($rootId);
            $result = array_merge($result, $subResult);
        }
        //list($success, $result) = self::runTestForRoot('uploads', $this->config);

        return [$success, $result];
    }

}
