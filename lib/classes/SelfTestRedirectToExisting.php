<?php

namespace WebPExpress;

class SelfTestRedirectToExisting extends SelfTestRedirectAbstract
{
    /**
     * Run test for either jpeg or png
     *
     * @param  string  $rootId    (ie "uploads" or "themes")
     * @param  string  $imageType  ("jpeg" or "png")
     * @return array   [$success, $result, $createdTestFiles]
     */
    protected function runTestForImageType($rootId, $imageType)
    {
        $result = [];
        $createdTestFiles = false;
        $noWarningsYet = true;

        $result[] = '### Copying files for testing';

        // Copy test image
        list($subResult, $success, $sourceFileName) = SelfTestHelper::copyTestImageToRoot($rootId, $imageType);
        $result = array_merge($result, $subResult);
        if (!$success) {
            $result[] = 'The test cannot be completed';
            return [false, $result, $createdTestFiles];
        }
        $createdTestFiles = true;

        $result[] = '';

        // Copy dummy webp
        list($subResult, $success, $destinationFile) = SelfTestHelper::copyDummyWebPToCacheFolder(
            $rootId,
            $this->config['destination-folder'],
            $this->config['destination-extension'],
            $this->config['destination-structure'],
            $sourceFileName,
            $imageType
        );
        $result = array_merge($result, $subResult);
        if (!$success) {
            $result[] = 'The test cannot be completed';
            return [false, $result, $createdTestFiles];
        }

        $requestUrl = Paths::getUrlById($rootId) . '/webp-express-test-images/' . $sourceFileName;
        $result[] = '### Lets check that browsers supporting webp gets the WEBP when the ' . strtoupper($imageType) . ' is requested';
        $result[] = 'Making a HTTP request for the test image (pretending to be a client that supports webp, by setting the "Accept" header to "image/webp")';
        $requestArgs = [
            'headers' => [
                'ACCEPT' => 'image/webp'
            ]
        ];
        list($success, $errors, $headers, $return) = SelfTestHelper::remoteGet($requestUrl, $requestArgs);

        if (!$success) {
            $result[count($result) - 1] .= '. FAILED';
            $result = array_merge($result, $errors);
            $result[] = 'The test cannot be completed';
            //$result[count($result) - 1] .= '. FAILED';
            return [false, $result, $createdTestFiles];
        }
        //$result[count($result) - 1] .= '. ok!';
        $result[] = '*' . $requestUrl . '*';

        $result = array_merge($result, SelfTestHelper::printHeaders($headers));

        if (!isset($headers['content-type'])) {
            $result[] = 'Bummer. There is no "content-type" response header. The test FAILED';
            return [false, $result, $createdTestFiles];
        }

        if ($headers['content-type'] != 'image/webp') {

            if ($headers['content-type'] == 'image/' . $imageType) {
                $result[] = 'Bummer. As the "content-type" header reveals, we got the ' . $imageType . '. ';
            } else {
                $result[] = 'Bummer. As the "content-type" header reveals, we did not get a webp' .
                    'Surprisingly we got: "' . $headers['content-type'] . '"';
                if (strpos($headers['content-type'], 'text/html') !== false) {
                    $result[] = 'Body:';
                    $result[] = print_r($return['body'], true);
                }
            }

            if (isset($headers['content-length'])) {
                if ($headers['content-length'] == '6964') {
                    $result[] = 'However, the content-length reveals that we actually GOT the webp ' .
                        '(we know that the file we put is exactly 6964 bytes). ' .
                        'So it is "just" the content-type header that was not set correctly.';

                    if (PlatformInfo::isNginx()) {
                        $result[] = 'As you are on Nginx, you probably need to add the following line ' .
                            'in your *mime.types* configuration file: ';
                        $result[] = '```image/webp webp;```';
                    } else {
                        $result[] = 'Perhaps you dont have *mod_mime* installed, or the following lines are not in a *.htaccess* ' .
                        'in the folder containing the webp (or a parent):';
                        $result[] = "```<IfModule mod_mime.c>\n  AddType image/webp .webp\n</IfModule>```";

                        $result[] = '### .htaccess status';
                        $result = array_merge($result, SelfTestHelper::htaccessInfo($this->config, true));
                    }

                    $result[] = 'The test **FAILED**{: .error}.';
                } else {
                    $result[] = 'Additionally, the content-length reveals that we did not get the webp ' .
                        '(we know that the file we put is exactly 6964 bytes). ' .
                        'So we can conclude that the rewrite did not happen';
                    $result[] = 'The test **FAILED**{: .error}.';
                    $result[] = '#### Diagnosing rewrites';
                    $result = array_merge($result, SelfTestHelper::diagnoseFailedRewrite($this->config));
                }
            } else {
                $result[] = 'In addition, we did not get a *content-length* header either.' .
                $result[] = 'It seems we can conclude that the rewrite did not happen.';
                $result[] = 'The test **FAILED**{: .error}.';
                $result[] = '#### Diagnosing rewrites';
                $result = array_merge($result, SelfTestHelper::diagnoseFailedRewrite($this->config));
            }

            return [false, $result, $createdTestFiles];
        }

        if (isset($headers['x-webp-convert-log'])) {
            $result[] = 'Bummer. Although we did get a webp, we did not get it as a result of a direct ' .
                'redirection. This webp was returned by the PHP script. Although this works, it takes more ' .
                'resources to ignite the PHP engine for each image request than redirecting directly to the image.';
            $result[] = 'The test FAILED.';

            $result[] = 'It seems something went wrong with the redirection.';
            $result[] = '#### Diagnosing redirects';
            $result = array_merge($result, SelfTestHelper::diagnoseFailedRewrite($this->config));

            return [false, $result, $createdTestFiles];
        } else {
            $result[] = 'Alrighty. We got a webp. Just what we wanted. **Great!**{: .ok}';
        }
        if (!SelfTestHelper::hasVaryAcceptHeader($headers)) {
            $result[count($result) - 1] .= '. **BUT!**';
            $result[] = '**Warning: We did not receive a Vary:Accept header. ' .
                'That header should be set in order to tell proxies that the response varies depending on the ' .
                'Accept header. Otherwise browsers not supporting webp might get a cached webp and vice versa.**{: .warn}';
            $noWarningsYet = false;
        }
        if (!SelfTestHelper::hasCacheControlOrExpiresHeader($headers)) {
            $result[] = '**Notice: No cache-control or expires header has been set. ' .
                'It is recommended to do so. Set it nice and big once you are sure the webps have a good quality/compression compromise.**{: .warn}';
        }
        $result[] = '';


        // Check browsers NOT supporting webp
        // -----------------------------------
        $result[] = '### Now lets check that browsers *not* supporting webp gets the ' . strtoupper($imageType);
        $result[] = 'Making a HTTP request for the test image (without setting the "Accept" header)';
        list($success, $errors, $headers) = SelfTestHelper::remoteGet($requestUrl);

        if (!$success) {
            $result[count($result) - 1] .= '. FAILED';
            $result = array_merge($result, $errors);
            $result[] = 'The test cannot be completed';
            //$result[count($result) - 1] .= '. FAILED';
            return [false, $result, $createdTestFiles];
        }
        //$result[count($result) - 1] .= '. ok!';
        $result[] = '*' . $requestUrl . '*';

        $result = array_merge($result, SelfTestHelper::printHeaders($headers));

        if (!isset($headers['content-type'])) {
            $result[] = 'Bummer. There is no "content-type" response header. The test FAILED';
            return [false, $result, $createdTestFiles];
        }

        if ($headers['content-type'] == 'image/webp') {
            $result[] = '**Bummer**{: .error}. As the "content-type" header reveals, we got the webp. ' .
                'So even browsers not supporting webp gets webp. Not good!';
            $result[] = 'The test FAILED.';

            $result[] = '#### What to do now?';
            // TODO: We could examine the headers for common CDN responses

            $result[] = 'First, examine the response headers above. Is there any indication that ' .
                 'the image is returned from a CDN cache? ' .
            $result[] = 'If there is: Check out the ' .
                 '*How do I configure my CDN in “Varied image responses” operation mode?* section in the FAQ ' .
                 '(https://wordpress.org/plugins/webp-express/)';

            if (PlatformInfo::isApache()) {
                $result[] = 'If not: please report this in the forum, as it seems the .htaccess rules ';
                $result[] = 'just arent working on your system.';
            } elseif (PlatformInfo::isNginx()) {
                 $result[] = 'Also, as you are on Nginx, check out the ' .
                     ' "I am on Nginx" section in the FAQ (https://wordpress.org/plugins/webp-express/)';
            } else {
                $result[] = 'If not: please report this in the forum, as it seems that there is something ' .
                    'in the *.htaccess* rules generated by WebP Express that are not working.';
            }

            $result[] = '### System info (for manual diagnosing):';
            $result = array_merge($result, SelfTestHelper::allInfo($this->config));


            return [false, $result, $createdTestFiles];
        }

        if ($headers['content-type'] != 'image/' . $imageType) {
            $result[] = 'Bummer. As the "content-type" header reveals, we did not get the ' . $imageType .
                'Surprisingly we got: "' . $headers['content-type'] . '"';
            $result[] = 'The test FAILED.';
            return [false, $result, $createdTestFiles];
        }
        $result[] = 'Alrighty. We got the ' . $imageType . '. **Great!**{: .ok}.';

        if (!SelfTestHelper::hasVaryAcceptHeader($headers)) {
            $result[count($result) - 1] .= '. **BUT!**';
            $result[] = '**We did not receive a Vary:Accept header. ' .
                'That header should be set in order to tell proxies that the response varies depending on the ' .
                'Accept header. Otherwise browsers not supporting webp might get a cached webp and vice versa.**{: .warn}';
            $noWarningsYet = false;
        }

        return [$noWarningsYet, $result, $createdTestFiles];
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
        $result[] = '# Testing redirection to existing webp';
        if (!$this->config['redirect-to-existing-in-htaccess']) {
            $result[] = 'Turned off, nothing to test (if you just turned it on without saving, remember: this is a live test so you need to save settings)';
            return [false, $result];
        }
        return [true, $result];
    }

    public static function runTest()
    {
        $config = Config::loadConfigAndFix(false);
        $me = new SelfTestRedirectToExisting($config);
        return $me->startTest();
    }
}
