<?php

namespace WebPExpress;

class SelfTestRedirectToExisting extends SelfTestRedirectAbstract
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

        $log[] = '### Copying files for testing';

        // Copy test image
        list($subResult, $success, $sourceFileName) = SelfTestHelper::copyTestImageToRoot($rootId, $imageType);
        $log = array_merge($log, $subResult);
        if (!$success) {
            $log[] = 'The test cannot be completed';
            return [false, $log, $createdTestFiles];
        }
        $createdTestFiles = true;

        $log[] = '';

        // Copy dummy webp
        list($subResult, $success, $destinationFile) = SelfTestHelper::copyDummyWebPToCacheFolder(
            $rootId,
            $this->config['destination-folder'],
            $this->config['destination-extension'],
            $this->config['destination-structure'],
            $sourceFileName,
            $imageType
        );
        $log = array_merge($log, $subResult);
        if (!$success) {
            $log[] = 'The test cannot be completed';
            return [false, $log, $createdTestFiles];
        }

        $requestUrl = Paths::getUrlById($rootId) . '/webp-express-test-images/' . $sourceFileName;
        $log[] = '### Lets check that browsers supporting webp gets the WEBP when the ' . strtoupper($imageType) . ' is requested';
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
            $log[] = 'The test cannot be completed, as the HTTP request failed. This does not neccesarily mean that the redirections ' .
                "aren't" . ' working, but it means you will have to check it manually. Check out the FAQ on how to do this. ' .
                'You might also want to check out why a simple HTTP request could not be issued. WebP Express uses such requests ' .
                'for detecting system capabilities, which are used when generating .htaccess files. These tests are not essential, but ' .
                'it would be best to have them working. I can inform that the Wordpress function *wp_remote_get* was used for the HTTP request ' .
                'and the URL was: ' . $requestUrl;

            return [false, $log, $createdTestFiles];
        }
        //$log[count($log) - 1] .= '. ok!';
        //$log[] = '*' . $requestUrl . '*';

        //$log = array_merge($log, SelfTestHelper::printHeaders($headers));

        if (!isset($headers['content-type'])) {
            $log[] = 'Bummer. There is no "content-type" response header. The test FAILED';
            return [false, $log, $createdTestFiles];
        }
        if ($headers['content-type'] != 'image/webp') {

            if ($headers['content-type'] == 'image/' . $imageType) {
                $log[] = 'Bummer. As the "content-type" header reveals, we got the ' . $imageType . '. ';
            } else {
                $log[] = 'Bummer. As the "content-type" header reveals, we did not get a webp' .
                    'Surprisingly we got: "' . $headers['content-type'] . '"';
            }

            if (isset($headers['content-length'])) {
                if ($headers['content-length'] == '6964') {
                    $log[] = 'However, the content-length reveals that we actually GOT the webp ' .
                        '(we know that the file we put is exactly 6964 bytes). ' .
                        'So it is "just" the content-type header that was not set correctly.';

                    if (PlatformInfo::isNginx()) {
                        $log[] = 'As you are on Nginx, you probably need to add the following line ' .
                            'in your *mime.types* configuration file: ';
                        $log[] = '```image/webp webp;```';
                    } else {
                        $log[] = 'Perhaps you dont have *mod_mime* installed, or the following lines are not in a *.htaccess* ' .
                        'in the folder containing the webp (or a parent):';
                        $log[] = "```<IfModule mod_mime.c>\n  AddType image/webp .webp\n</IfModule>```";

                        $log[] = '### .htaccess status';
                        $log = array_merge($log, SelfTestHelper::htaccessInfo($this->config, true));
                    }

                    $log[] = 'The test **FAILED**{: .error}.';
                } else {
                    $log[] = 'Additionally, the content-length reveals that we did not get the webp ' .
                        '(we know that the file we put is exactly 6964 bytes). ' .
                        'So we can conclude that the rewrite did not happen';
                    $log[] = 'The test **FAILED**{: .error}.';
                    $log[] = '#### Diagnosing rewrites';
                    $log = array_merge($log, SelfTestHelper::diagnoseFailedRewrite($this->config, $headers));
                }
            } else {
                $log[] = 'In addition, we did not get a *content-length* header either.' .
                $log[] = 'It seems we can conclude that the rewrite did not happen.';
                $log[] = 'The test **FAILED**{: .error}.';
                $log[] = '#### Diagnosing rewrites';
                $log = array_merge($log, SelfTestHelper::diagnoseFailedRewrite($this->config, $headers));
            }

            return [false, $log, $createdTestFiles];
        }

        if (isset($headers['x-webp-convert-log'])) {
            $log[] = 'Bummer. Although we did get a webp, we did not get it as a result of a direct ' .
                'redirection. This webp was returned by the PHP script. Although this works, it takes more ' .
                'resources to ignite the PHP engine for each image request than redirecting directly to the image.';
            $log[] = 'The test FAILED.';

            $log[] = 'It seems something went wrong with the redirection.';
            $log[] = '#### Diagnosing redirects';
            $log = array_merge($log, SelfTestHelper::diagnoseFailedRewrite($this->config, $headers));

            return [false, $log, $createdTestFiles];
        } else {
            $log[] = 'Alrighty. We got a webp. Just what we wanted. **Great!**{: .ok}';
        }

        if (!SelfTestHelper::hasVaryAcceptHeader($headers)) {
            $log = array_merge($log, SelfTestHelper::diagnoseNoVaryHeader($rootId, 'existing'));
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

            $log[] = '#### What to do now?';
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
            $log = array_merge($log, SelfTestHelper::diagnoseNoVaryHeader($rootId, 'existing'));
            $noWarningsYet = false;
        }

        return [$noWarningsYet, $log, $createdTestFiles];
    }

    protected function getSuccessMessage()
    {
        return 'Everything **seems to work**{: .ok} as it should. ' .
            'However, a couple of things were not tested (it is on the TODO). ' .
            'TODO 1: If one image type is disabled, check that it does not redirect to webp (unless redirection to converter is set up). ' .
            'TODO 2: Test that redirection to webp only is triggered when the webp exists. ';
    }

    public function startupTests()
    {
        $log[] = '# Testing redirection to existing webp';
        if (!$this->config['redirect-to-existing-in-htaccess']) {
            $log[] = 'Turned off, nothing to test (if you just turned it on without saving, remember: this is a live test so you need to save settings)';
            return [false, $log];
        }
        return [true, $log];
    }

    public static function runTest()
    {
        $config = Config::loadConfigAndFix(false);
        $me = new SelfTestRedirectToExisting($config);
        return $me->startTest();
    }
}
