<?php

namespace WebPExpress;
use \WebPExpress\Option;


class SelfTestRedirectToWebPRealizer extends SelfTestRedirectAbstract
{

    /**
     * Run test for either jpeg or png
     *
     * @param  string  $rootId    (ie "uploads" or "themes")
     * @param  string  $imageType  ("jpeg" or "png")
     * @return array   [$success, $log, $createdTestFiles]
     */
    protected function runTestForImageType($rootId, $imageType)
    {
        $log = [];
        $createdTestFiles = false;
        $noWarningsYet = true;

        // Copy test image
        list($subResult, $success, $sourceFileName) = SelfTestHelper::copyTestImageToRoot($rootId, $imageType);
        $log = array_merge($log, $subResult);
        if (!$success) {
            $log[] = 'The test cannot be completed';
            return [false, $log, $createdTestFiles];
        }
        $createdTestFiles = true;

        //$requestUrl = Paths::getUploadUrl() . '/' . $sourceFileName;

        // Hacky, I know.
        // AlterHtmlHelper was not meant to be used like this, but it is the only place where we currently
        // have logic for finding destination url from source url.

        //$sourceUrl = Paths::getUploadUrl() . '/' . $sourceFileName;
        $sourceUrl = Paths::getUrlById($rootId) . '/webp-express-test-images/' . $sourceFileName;

        AlterHtmlHelper::$options = json_decode(Option::getOption('webp-express-alter-html-options', null), true);
        AlterHtmlHelper::$options['only-for-webps-that-exists'] = false;

        // TODO: Check that AlterHtmlHelper::$options['scope'] is not empty
        //       - it has been seen to happen

        $requestUrl = AlterHtmlHelper::getWebPUrlInImageRoot(
            $sourceUrl,
            $rootId,
            Paths::getUrlById($rootId),
            Paths::getAbsDirById($rootId)
        );

        if ($requestUrl === false) {
            // PS: this has happened due to AlterHtmlHelper::$options['scope'] being empty...

            $log[] = 'Hm, strange. The source URL does not seem to be in the base root';
            $log[] = 'Source URL:' . $sourceUrl;
            //$log[] = 'Root ID:' . $rootId;
            $log[] = 'Root Url:' . Paths::getUrlById($rootId);
            $log[] = 'Request Url:' . $requestUrl;
            $log[] = 'parsed url:' . print_r(parse_url($sourceUrl), true);
            $log[] = 'parsed url:' . print_r(parse_url(Paths::getUrlById($rootId)), true);
            $log[] = 'scope:' . print_r(AlterHtmlHelper::$options['scope'], true);
            $log[] = 'cached options:' . print_r(AlterHtmlHelper::$options, true);
            $log[] = 'cached options: ' . print_r(Option::getOption('webp-express-alter-html-options', 'not there!'), true);
        }


        $log[] = '### Lets check that browsers supporting webp gets a freshly converted WEBP ' .
            'when a non-existing WEBP is requested, which has a corresponding source';
        $log[] = 'Making a HTTP request for the test image (pretending to be a client that supports webp, by setting the "Accept" header to "image/webp")';
        $requestArgs = [
            'headers' => [
                'ACCEPT' => 'image/webp'
            ]
        ];
        list($success, $remoteGetLog, $results) = SelfTestHelper::remoteGet($requestUrl, $requestArgs);
        $headers = $results[count($results)-1]['headers'];
        $log = array_merge($log, $remoteGetLog);


        if (!$success) {
            //$log[count($log) - 1] .= '. FAILED';
            //$log[] = '*' . $requestUrl . '*';

            $log[] = 'The test **failed**{: .error}';

            if (isset($results[0]['response']['code'])) {
                $responseCode = $results[0]['response']['code'];
                if (($responseCode == 500) || ($responseCode == 403)) {

                    $log = array_merge($log, SelfTestHelper::diagnoseWod403or500($this->config, $rootId, $responseCode));
                    return [false, $log, $createdTestFiles];
                    //$log[] = 'or that there is an .htaccess file in the ';
                }
//                $log[] = print_r($results[0]['response']['code'], true);
            }

            $log[] = 'Why did it fail? It could either be that the redirection rule did not trigger ' .
                'or it could be that the PHP script could not locate a source image corresponding to the destination URL. ' .
                'Currently, this analysis cannot dertermine which was the case and it cannot be helpful ' .
                'if the latter is the case (sorry!). However, if the redirection rules are the problem, here is some info:';

            $log[] = '### Diagnosing redirection problems (presuming it is the redirection to the script that is failing)';
            $log = array_merge($log, SelfTestHelper::diagnoseFailedRewrite($this->config, $headers));


            //$log[count($log) - 1] .= '. FAILED';
            return [false, $log, $createdTestFiles];
        }
        //$log[count($log) - 1] .= '. ok!';
        //$log[] = '*' . $requestUrl . '*';

        //$log = array_merge($log, SelfTestHelper::printHeaders($headers));

        if (!isset($headers['content-type'])) {
            $log[] = 'Bummer. There is no "content-type" response header. The test FAILED';
            return [false, $log, $createdTestFiles];
        }

        if ($headers['content-type'] == 'image/' . $imageType) {
            $log[] = 'Bummer. As the "content-type" header reveals, we got the ' . $imageType . '.';
            $log[] = 'The test **failed**{: .error}.';
            $log[] = 'Now, what went wrong?';

            if (isset($headers['x-webp-convert-log'])) {
                //$log[] = 'Inspect the "x-webp-convert-log" headers above, and you ' .
                //    'should have your answer (it is probably because you do not have any conversion methods working).';
                if (SelfTestHelper::hasHeaderContaining($headers, 'x-webp-convert-log', 'Performing fail action: original')) {
                    $log[] = 'The answer lies in the "x-convert-log" response headers: ' .
                        '**The conversion failed**{: .error}. ';
                }
            } else {
                $log[] = 'Well, there is indication that the redirection isnt working. ' .
                    'The PHP script should set "x-webp-convert-log" response headers, but there are none. ';
                    'While these headers could have been eaten in a Cloudflare-like setup, the problem is ';
                    'probably that the redirection simply failed';

                    $log[] = '### Diagnosing redirection problems';
                    $log = array_merge($log, SelfTestHelper::diagnoseFailedRewrite($this->config, $headers));
            }
            return [false, $log, $createdTestFiles];
        }

        if ($headers['content-type'] != 'image/webp') {
            $log[] = 'However. As the "content-type" header reveals, we did not get a webp' .
                'Surprisingly we got: "' . $headers['content-type'] . '"';
            $log[] = 'The test FAILED.';
            return [false, $log, $createdTestFiles];
        }

        $log[] = '**Alrighty**{: .ok}. We got a webp.';
        if (isset($headers['x-webp-convert-log'])) {
            $log[] = 'The "x-webp-convert-log" headers reveals we got the webp from the PHP script. **Great!**{: .ok}';
        } else {
            $log[] = 'Interestingly, there are no "x-webp-convert-log" headers even though ' .
                'the PHP script always produces such. Could it be you have some weird setup that eats these headers?';
        }

        if (SelfTestHelper::hasVaryAcceptHeader($headers)) {
            $log[] = 'All is however not super-duper:';

            $log[] = '**Notice: We received a Vary:Accept header. ' .
                'That header need not to be set. Actually, it is a little bit bad for performance ' .
                'as proxies are currently doing a bad job maintaining several caches (in many cases they simply do not)**{: .warn}';
            $noWarningsYet = false;
        }
        if (!SelfTestHelper::hasCacheControlOrExpiresHeader($headers)) {
            $log[] = '**Notice: No cache-control or expires header has been set. ' .
                'It is recommended to do so. Set it nice and big once you are sure the webps have a good quality/compression compromise.**{: .warn}';
        }
        $log[] = '';

        return [$noWarningsYet, $log, $createdTestFiles];
    }

/*
    private static function doRunTest($this->config)
    {
        $log = [];
        $log[] = '# Testing redirection to converter';

        $createdTestFiles = false;
        if (!file_exists(Paths::getConfigFileName())) {
            $log[] = 'Hold on. You need to save options before you can run this test. There is no config file yet.';
            return [true, $log, $createdTestFiles];
        }


        if ($this->config['image-types'] == 0) {
            $log[] = 'No image types have been activated, nothing to test';
            return [true, $log, $createdTestFiles];
        }

        if ($this->config['image-types'] & 1) {
            list($success, $subResult, $createdTestFiles) = self::runTestForImageType($this->config, 'jpeg');
            $log = array_merge($log, $subResult);

            if ($success) {
                if ($this->config['image-types'] & 2) {
                    $log[] = '### Performing same tests for PNG';
                    list($success, $subResult, $createdTestFiles2) = self::runTestForImageType($this->config, 'png');
                    $createdTestFiles = $createdTestFiles || $createdTestFiles2;
                    if ($success) {
                        //$log[count($log) - 1] .= '. **ok**{: .ok}';
                        $log[] .= 'All tests passed for PNG as well.';
                        $log[] = '(I shall spare you for the report, which is almost identical to the one above)';
                    } else {
                        $log = array_merge($log, $subResult);
                    }
                }
            }
        } else {
            list($success, $subResult, $createdTestFiles) = self::runTestForImageType($this->config, 'png');
            $log = array_merge($log, $subResult);
        }

        if ($success) {
            $log[] = '### Conclusion';
            $log[] = 'Everything **seems to work**{: .ok} as it should. ' .
                'However, notice that this test only tested an image which was placed in the *uploads* folder. ' .
                'The rest of the image roots (such as theme images) have not been tested (it is on the TODO). ' .
                'Also on the TODO: If one image type is disabled, check that it does not redirect to the conversion script. ' .
                'These things probably work, though.';
        }


        return [true, $log, $createdTestFiles];
    }*/

    protected function getSuccessMessage()
    {
        return 'Everything **seems to work**{: .ok} as it should. ' .
            'However, a check is on the TODO: ' .
            'TODO: Check that disabled image types does not get converted. ';
    }

    public function startupTests()
    {
        $log[] = '# Testing "WebP Realizer" functionality';
        if (!$this->config['enable-redirection-to-webp-realizer']) {
            $log[] = 'Turned off, nothing to test (if you just turned it on without saving, remember: this is a live test so you need to save settings)';
            return [false, $log];
        }
        return [true, $log];
    }

    public static function runTest()
    {
        $config = Config::loadConfigAndFix(false);
        $me = new SelfTestRedirectToWebPRealizer($config);
        return $me->startTest();
    }


}
