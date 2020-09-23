<?php

namespace HtaccessCapabilityTester\Tests\Testers;

use HtaccessCapabilityTester\HttpResponse;
use HtaccessCapabilityTester\Tests\FakeServer;
use PHPUnit\Framework\TestCase;

class HttpResponseTest extends TestCase
{

    public function test1()
    {
        $r = new HttpResponse('hi', '200', [
            'x-test' => 'test'
        ]);
        $this->assertTrue($r->hasHeader('x-test'));
        $this->assertTrue($r->hasHeader('X-Test'));
        $this->assertTrue($r->hasHeaderValue('X-Test', 'test'));
    }

    public function test2()
    {
        $r = new HttpResponse('hi', '200', [
            'x-test1' => 'value1, value2',
            'x-test2' => 'value1,value2'
        ]);
        $this->assertTrue($r->hasHeaderValue('X-Test1', 'value2'));
        $this->assertTrue($r->hasHeaderValue('X-Test2', 'value2'));
    }

    public function test3()
    {
        $r = new HttpResponse('hi', '200', [
            'content-md5' => 'aaoeu'
        ]);
        $this->assertTrue($r->hasHeader('Content-MD5'));
        $this->assertTrue($r->hasHeader('content-md5'));
    }

    public function test4()
    {
        $r = new HttpResponse('hi', '200', [
            'Content-MD5' => 'aaoeu'
        ]);
        $this->assertTrue($r->hasHeader('Content-MD5'));
        $this->assertTrue($r->hasHeader('content-md5'));
    }

//
}
