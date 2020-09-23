<?php

namespace WebPExpress;

class SelfTestRedirectToConverter extends SelfTestRedirectAbstract
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

        $htaccessFile = Paths::getAbsDirById($rootId) . '/.htaccess';
        if (!FileHelper::fileExists($htaccessFile)) {
            $log[] = '**Warning: There is no .htaccess file in the ' . $rootId . ' folder!**{: .warn} (did you save settings yet?)';
            $noWarningsYet = false;
        } elseif (!HTAccess::haveWeRulesInThisHTAccess($htaccessFile)) {
            $log[] = '**Warning: There are no WebP Express rules in the .htaccess file in the ' . $rootId . ' folder!**{: .warn}';
            $noWarningsYet = false;
        }

        // Copy test image (jpeg)
        list($subResult, $success, $sourceFileName) = SelfTestHelper::copyTestImageToRoot($rootId, $imageType);
        $log = array_merge($log, $subResult);
        if (!$success) {
            $log[] = 'The test cannot be completed';
            return [false, $log, $createdTestFiles];
        }
        $createdTestFiles = true;

        $requestUrl = Paths::getUrlById($rootId) . '/webp-express-test-images/' . $sourceFileName;

        $log[] = '### Lets check that browsers supporting webp gets a freshly converted WEBP ' .
            'when the ' . $imageType . ' is requested';
        $log[] = 'Making a HTTP request for the test image (pretending to be a client that supports webp, by setting the "Accept" header to "image/webp")';
        $requestArgs = [
            'headers' => [
                'ACCEPT' => 'image/webp'
            ],
        ];
        list($success, $remoteGetLog, $results) = SelfTestHelper::remoteGet($requestUrl, $requestArgs);
        $headers = $results[count($results)-1]['headers'];
        $log = array_merge($log, $remoteGetLog);

        if (!$success) {
            //$log[count($log) - 1] .= '. FAILED';
            $log[] = 'The request FAILED';
            //$log = array_merge($log, $remoteGetLog);

            if (isset($results[0]['response']['code'])) {
                $responseCode = $results[0]['response']['code'];
                if (($responseCode == 500) || ($responseCode == 403)) {

                    $log = array_merge($log, SelfTestHelper::diagnoseWod403or500($this->config, $rootId, $responseCode));

                    //$log[] = 'or that there is an .htaccess file in the ';
                }
//                $log[] = print_r($results[0]['response']['code'], true);
            }
            //$log[] = 'The test cannot be completed';
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

        if (isset($headers['x-webp-convert-log'])) {
            $log[] = 'Alrighty. We got a webp, and we got it from the PHP script. **Great!**{: .ok}';
        } else {
            if (count($results) > 1) {
                if (isset($results[0]['headers']['x-webp-convert-log'])) {
                    $log[] = '**Great!**{: .ok}. The PHP script created a webp and redirected the image request ' .
                        'back to itself. A refresh, if you wish. The refresh got us the webp (relying on there being ' .
                        'a rule which redirect images to existing converted images for webp-enabled browsers - which there is!). ' .
                        (SelfTestHelper::hasVaryAcceptHeader($headers) ? 'And we got the Vary:Accept header set too. **Super!**{: .ok}!' : '');
                }
            } else {
                $log[] = 'We got a webp. However, it seems we did not get it from the PHP script.';

            }

            //$log[] = print_r($return, true);
            //error_log(print_r($return, true));
        }

        if (!SelfTestHelper::hasVaryAcceptHeader($headers)) {
            $log = array_merge($log, SelfTestHelper::diagnoseNoVaryHeader($rootId, 'webp-on-demand'));
            $noWarningsYet = false;
        }
        if (!SelfTestHelper::hasCacheControlOrExpiresHeader($headers)) {
            $log[] = '**Notice: No cache-control or expires header has been set. ' .
                'It is recommended to do so. Set it nice and big once you are sure the webps have a good quality/compression compromise.**{: .warn}';
        }
        $log[] = '';


        // Check browsers NOT supporting webp
        // -----------------------------------
        $log[] = '### Now lets check that browsers *not* supporting webp gets the ' . strtoupper($imageType);
        $log[] = 'Making a HTTP request for the test image (without setting the "Accept" header)';
        list($success, $remoteGetLog, $results) = SelfTestHelper::remoteGet($requestUrl);
        $headers = $results[count($results)-1]['headers'];
        $log = array_merge($log, $remoteGetLog);

        if (!$success) {
            $log[] = 'The request FAILED';
            $log[] = 'The test cannot be completed';
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

        if ($headers['content-type'] == 'image/webp') {
            $log[] = '**Bummer**{: .error}. As the "content-type" header reveals, we got the webp. ' .
                'So even browsers not supporting webp gets webp. Not good!';
            $log[] = 'The test FAILED.';

            $log[] = '### What to do now?';
            // TODO: We could examine the headers for common CDN responses

            $log[] = 'First, examine the response headers above. Is there any indication that ' .
                 'the image is returned from a CDN cache? ' .
            $log[] = 'If there is: Check out the ' .
                 '*How do I configure my CDN in “Varied image responses” operation mode?* section in the FAQ ' .
                 '(https://wordpress.org/plugins/webp-express/)';

            if (PlatformInfo::isApache()) {
                $log[] = 'If not: please report this in the forum, as it seems the .htaccess rules ';
                $log[] = 'just arent working on your system.';
            } elseif (PlatformInfo::isNginx()) {
                 $log[] = 'Also, as you are on Nginx, check out the ' .
                     ' "I am on Nginx" section in the FAQ (https://wordpress.org/plugins/webp-express/)';
            } else {
                $log[] = 'If not: please report this in the forum, as it seems that there is something ' .
                    'in the *.htaccess* rules generated by WebP Express that are not working.';
            }

            $log[] = '### System info (for manual diagnosing):';
            $log = array_merge($log, SelfTestHelper::allInfo($this->config));


            return [false, $log, $createdTestFiles];
        }

        if ($headers['content-type'] != 'image/' . $imageType) {
            $log[] = 'Bummer. As the "content-type" header reveals, we did not get the ' . $imageType .
                'Surprisingly we got: "' . $headers['content-type'] . '"';
            $log[] = 'The test FAILED.';
            return [false, $log, $createdTestFiles];
        }
        $log[] = 'Alrighty. We got the ' . $imageType . '. **Great!**{: .ok}.';

        if (!SelfTestHelper::hasVaryAcceptHeader($headers)) {
            $log = array_merge($log, SelfTestHelper::diagnoseNoVaryHeader($rootId, 'webp-on-demand'));
            $noWarningsYet = false;
        }

        return [$noWarningsYet, $log, $createdTestFiles];
    }

    protected function getSuccessMessage()
    {
        return 'Everything **seems to work**{: .ok} as it should. ' .
            'However, a check is on the TODO: ' .
            'TODO: Check that disabled image types does not get converted. ';
    }

    public function startupTests()
    {
        $log[] = '# Testing redirection to converter';
        if (!$this->config['enable-redirection-to-converter']) {
            $log[] = 'Turned off, nothing to test (if you just turned it on without saving, remember: this is a live test so you need to save settings)';
            return [false, $log];
        }
        return [true, $log];
    }

    public static function runTest()
    {
        $config = Config::loadConfigAndFix(false);
        $me = new SelfTestRedirectToConverter($config);
        return $me->startTest();
    }

}
