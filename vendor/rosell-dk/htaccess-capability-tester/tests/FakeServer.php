<?php

namespace HtaccessCapabilityTester\Tests;

use HtaccessCapabilityTester\HttpResponse;
use HtaccessCapabilityTester\HttpRequesterInterface;
use HtaccessCapabilityTester\TestFilesLineUpperInterface;
use HtaccessCapabilityTester\TestResult;
use HtaccessCapabilityTester\TestResultCache;
use HtaccessCapabilityTester\Testers\AbstractTester;

class FakeServer implements TestFilesLineUpperInterface, HttpRequesterInterface
{

    /** @var array  Files on the server */
    private $files;

    /** @var array  Files as a map, by filename */
    private $filesMap;

    /** @var bool  If .htaccess processing is disabled */
    private $htaccessDisabled = false;

    /** @var bool  If all directives should be disallowed (but .htaccess still read) */
    private $disallowAllDirectives = false;

    /** @var bool  If server should go fatal about forbidden directives */
    private $fatal = false;

    /** @var bool  If all requests should crash! (500) */
    private $crashAll = false;

    /** @var bool  If access is denied for all requests */
    private $accessAllDenied = false;

    /** @var bool  Returns the php text file rather than "Sorry, this server cannot process PHP!" */
    private $handlePHPasText = false;

    /** @var bool  If all requests fail (without response code) */
    private $failAll = false;

    /** @var array  Predefined responses for certain urls */
    private $responses;


    public function lineUp($files)
    {
        $this->files = $files;
        $this->filesMap = [];
        foreach ($files as $file) {
            list($filename, $content) = $file;
            $this->filesMap[$filename] = $content;
        }
        //$m = new SetRequestHeaderTester();
        //$m->putFiles('');
        //print_r($files);
    }

    public function makeHttpRequest($url)
    {
        $body = '';
        $statusCode = '200';
        $headers = [];

        if ($this->failAll) {
            return new HttpResponse('', '0', []);
        }

        //echo 'Fakeserver request:' . $url . "\n";
        if (isset($this->responses[$url])) {
            //echo 'predefined: ' . $url . "\n";
            return $this->responses[$url];
        }

        if ($this->crashAll) {
            return new HttpResponse('', '500', []);
        }

        if (($this->disallowAllDirectives) && ($this->fatal)) {

            $urlToHtaccessInSameFolder = dirname($url) . '/.htaccess';
            $doesFolderContainHtaccess = isset($this->filesMap[$urlToHtaccessInSameFolder]);

            if ($doesFolderContainHtaccess) {
                return new HttpResponse('', '500', []);
            }
        }

        if ($this->accessAllDenied) {
            // TODO: what body?
            return new HttpResponse('', '403', []);
        }


        //$simplyServeRequested = ($this->htaccessDisabled || ($this->disallowAllDirectives && (!$this->fatal)));

        // Simply return the file that was requested
        if (isset($this->filesMap[$url])) {

            $isPhpFile = (strrpos($url, '.php') == strlen($url) - 4);
            if ($isPhpFile && ($this->handlePHPasText)) {
                return new HttpResponse('Sorry, this server cannot process PHP!', '200', []); ;
            } else {
                return new HttpResponse($this->filesMap[$url], '200', []); ;
            }
        } else {
            return new HttpResponse('Not found', '404', []);
        }

        //return new HttpResponse('Not found', '404', []);
    }

    /**
     * Disallows all directives, but do still process .htaccess.
     *
     * In essence: Fail, if the folder contains an .htaccess file
     *
     * @param string $fatal  fatal|nonfatal
     */
    public function disallowAllDirectives($fatal)
    {
        $this->disallowAllDirectives = true;
        $this->fatal = ($fatal = 'fatal');
    }

    public function disableHtaccess()
    {
        $this->htaccessDisabled = true;
    }

    public function denyAllAccess()
    {
        $this->accessAllDenied = true;
    }

    public function makeAllCrash()
    {
        $this->crashAll = true;
    }

    public function failAllRequests()
    {
        $this->failAll = true;
    }

    public function handlePHPasText()
    {
        $this->handlePHPasText = true;
    }

    // TODO: denyAccessToPHP

    /**
     * @param array $responses
     */
    public function setResponses($responses)
    {
        $this->responses = $responses;
    }

    public function connectHCT($hct)
    {
        TestResultCache::clear();
        $hct->setTestFilesLineUpper($this);
        $hct->setHttpRequester($this);
    }


    /**
     * @param  AbstractTester $tester
     * @return TestResult
     */
    public function runTester($tester)
    {
        TestResultCache::clear();
        $tester->setTestFilesLineUpper($this);
        $tester->setHttpRequester($this);

        return $tester->run('', '');
    }
}
