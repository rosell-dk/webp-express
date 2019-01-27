<?php

/**
 * WebPConvert - Convert JPEG & PNG to WebP with PHP
 *
 * @link https://github.com/rosell-dk/webp-convert
 * @license MIT
 */

namespace WebPExpressTests;

//use WebPConvert\WebPConvert;
use PHPUnit\Framework\TestCase;
use Sunra\PhpSimple\HtmlDomParser;

use WebPExpress\ImageUrlsReplacer;
use WebPExpressTests\ImageUrlsReplacerExtended;

include __DIR__ . '/../lib/classes/ImageUrlsReplacer.php';
include __DIR__ . '/ImageUrlsReplacerExtended.php';

class ImageUrlsReplacerTest extends TestCase
{

    public function passThrough($str) {
        return $str;
    }


    public function testUntouched()
    {

        // Here we basically test that the DOM manipulation tool is gentle and doesn't alter
        // anything other that what it is told to.

        $untouchedTests = [
            'a', 'a',
            'a<p></p>b<p></p>c',
            '',
            '<body!><p><!-- bad html here!--><img src="http://example.com/2.jpg"></p></a>',
            '<img src="/3.jpg">',
            '<img src="http://example.com/4.jpeg" alt="">',
            '<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US"><head profile="http://gmpg.org/xfn/11"><meta charset="utf-8" /></head><body></body></html>',
            '<H1>hi</H1>',
            'blah<BR>blah<br>blah',
            "<pre>hello\nline</pre>",
            "① <p>并來朝貢</p>"
        ];



        ImageUrlsReplacer::$handleAttributeFunction = '\WebPExpressTests\ImageUrlsReplacerTest::passThrough';

        foreach ($untouchedTests as $html) {
            $this->assertEquals($html, ImageUrlsReplacer::replace($html));
        }
    }

    public function star($str) {
        return '*';
    }

    public function testBasic1()
    {

        $starTests = [
            ['<img src="http://example.com/1.jpg">', '<img src="*">'],
            ['<body!><p><!-- bad html here!--><img src="http://example.com/2.jpg"></p></a>', '<body!><p><!-- bad html here!--><img src="*"></p></a>'],
            ['<img src="/3.jpg">', '<img src="*">'],
            ['<img src="http://example.com/4.jpeg" alt="">', '<img src="*" alt="">'],
            ['', ''],
            ['a', 'a'],
            ['a<p></p>b<p></p>c', 'a<p></p>b<p></p>c'],
            ['<img src="xx" alt="并來朝貢">', '<img src="*" alt="并來朝貢">'],
            ['<并來><img data-x="aoeu"></并來>', '<并來><img data-x="*"></并來>'],
        ];

        ImageUrlsReplacer::$handleAttributeFunction = '\WebPExpressTests\ImageUrlsReplacerTest::star';

        foreach ($starTests as list($html, $expectedOutput)) {
            $output = ImageUrlsReplacer::replace($html);
            $this->assertEquals($expectedOutput, $output);
        }
    }

    public function appendWebP($str) {
        return $str . '.webp';
    }

    public function testBasic2()
    {
        $appendWebPTests = [
            ['<img src="http://example.com/1.jpg">', '<img src="http://example.com/1.jpg.webp">'],
            ['<img src="3.jpg"><img src="4.jpg">', '<img src="3.jpg.webp"><img src="4.jpg.webp">'],
            ['<img src="5.jpg" data-src="6.jpg">', '<img src="5.jpg.webp" data-src="6.jpg.webp">'],
            ['<img src="7.jpg" data-cvpsrc="8.jpg">', '<img src="7.jpg.webp" data-cvpsrc="8.jpg.webp">'],
            ['<img src="/5.jpg">', '<img src="/5.jpg.webp">'],
            ['<img src="/6.jpg"/>', '<img src="/6.jpg.webp"/>'],
            ['<img src = "/7.jpg">', '<img src = "/7.jpg.webp">'],
            ['<img src=/8.jpg alt="">', '<img src=/8.jpg.webp alt="">'],
            ['<img src=/9.jpg>', '<img src=/9.jpg.webp>'],
            ['<img src=/10.jpg alt="hello">', '<img src=/10.jpg.webp alt="hello">'],
            ['<img src=/11.jpg />', '<img src=/11.jpg.webp />'],
            //['<img src=/12_.jpg/>', '<img src=/12_.jpg.webp>'],
            ['<input type="image" src="/flamingo13.jpg">', '<input type="image" src="/flamingo13.jpg.webp">'],
            ['<iframe src="/image14.jpg"></iframe>', '<iframe src="/image14.jpg.webp"></iframe>'],
            ['<img data-cvpsrc="/15.jpg">', '<img data-cvpsrc="/15.jpg.webp">'],
            ['<picture><source src="16.jpg"><img src="17.jpg"></picture>', '<picture><source src="16.jpg.webp"><img src="17.jpg.webp"></picture>'],
            ['<img src="18.jpg" srcset="19.jpg 1000w">', '<img src="18.jpg.webp" srcset="19.jpg 1000w.webp">'],
            ['', ''],
//            ['<img src="http://example.com/102.jpg" srcset="http://example.com/103.jpg 1000w">', '<img src="http://example.com/102.jpg.webp" srcset="http://example.com/103.jpg.webp 1000w">']
        ];

        ImageUrlsReplacer::$handleAttributeFunction = '\WebPExpressTests\ImageUrlsReplacerTest::appendWebP';
        foreach ($appendWebPTests as list($html, $expectedOutput)) {
            $output = ImageUrlsReplacer::replace($html);
            $this->assertEquals($expectedOutput, $output);
        }
    }

    public function isSrcSetOut($value)
    {
        return ImageUrlsReplacer::looksLikeSrcSet($value) ? 'yes' : 'no';
    }

    public function testSrcSetDetection()
    {

        $isSrcSettests = [
            ['<img data-x="1.jpg 1000w">', '<img data-x="yes">'],
            ['<img data-x="2.jpg">', '<img data-x="no">'],
            ['<img src="3.jpg" data-x="/4.jpg 1000w,/header.jpg 1000w, /header.jpg 2000w">', '<img src="no" data-x="yes">'],
            ['<img data-x="5.jpg 1000w, 6.jpg">', '<img data-x="yes">'],
        ];

        ImageUrlsReplacer::$handleAttributeFunction = '\WebPExpressTests\ImageUrlsReplacerTest::isSrcSetOut';
        foreach ($isSrcSettests as list($html, $expectedOutput)) {
            $output = ImageUrlsReplacer::replace($html);
            $this->assertEquals($expectedOutput, $output);
        }
    }

    public function customUrlReplacer($url)
    {
        return $url . '.***';
    }

    public function testCustomUrlProcessor()
    {
        $tests = [
            ['<img data-x="1.jpg">', '<img data-x="1.jpg.***">'],
        ];
        ImageUrlsReplacer::$handleAttributeFunction = 'self::handleAttribute';
        ImageUrlsReplacer::$urlReplacerFunction = '\WebPExpressTests\ImageUrlsReplacerTest::customUrlReplacer';

        foreach ($tests as list($html, $expectedOutput)) {
            $output = ImageUrlsReplacer::replace($html);
            $this->assertEquals($expectedOutput, $output);
        }
    }

/*
    public function customUrlValidator($url)
    {
        return preg_match('#(png|jpe?g|gif)$#', $url);  // here we also accept gif
    }


    public function testCustomUrlValidator()
    {
        $tests = [
            ['<img src="1.gif">', '<img src="1.gif.webp">'],    // gif is alright with in this custom validator
            ['<img src="1.buff">', '<img src="1.buff">'],
        ];
        ImageUrlsReplacer::$handleAttributeFunction = 'self::handleAttribute';
        ImageUrlsReplacer::$urlReplacerFunction = 'self::replaceUrl';
        ImageUrlsReplacer::$urlValidatorFunction = '\WebPExpressTests\ImageUrlsReplacerTest::customUrlValidator';

        foreach ($tests as list($html, $expectedOutput)) {
            $output = ImageUrlsReplacer::replace($html);
            $this->assertEquals($expectedOutput, $output);
        }
    }*/

    public function testCSS()
    {
        $tests = [
            ['<style>a {color:white}; b {color: black}</style>', '<style>a {color:white}; b {color: black}</style>'],
            ['<style>background: url("/image.jpg"); a {}</style>', '<style>background: url("/image.jpg.webp"); a {}</style>'],
            ['<style>a {};background-image: url("/image.jpg")</style>', '<style>a {};background-image: url("/image.jpg.webp")</style>'],
            ['<style>background-image: url("/image.jpg"), url("/image2.png"));</style>', '<style>background-image: url("/image.jpg.webp"), url("/image2.png.webp"));</style>'],
            ['<style>background-image:url(/image.jpg), url("/image2.png"));</style>', '<style>background-image:url(/image.jpg.webp), url("/image2.png.webp"));</style>'],
            ['<p style="background-image:url(/image.jpg)"></p>', '<p style="background-image:url(/image.jpg.webp)"></p>'],
            ['<p style="a:{},background:url(/image.jpg)"></p>', '<p style="a:{},background:url(/image.jpg.webp)"></p>'],
            ["<style>background-image:\nurl(/image.jpg);</style>", "<style>background-image:\nurl(/image.jpg.webp);</style>"],
        ];
        //ImageUrlsReplacer::$handleAttributeFunction = '\WebPExpressTests\ImageUrlsReplacerTest::star';
        ImageUrlsReplacer::$handleAttributeFunction = 'self::handleAttribute';
        //ImageUrlsReplacer::$urlReplacerFunction = 'self::replaceUrl';
        //ImageUrlsReplacer::$urlReplacerFunction = '\WebPExpressTests\ImageUrlsReplacerTest::star';
        ImageUrlsReplacer::$urlReplacerFunction = '\WebPExpressTests\ImageUrlsReplacerTest::appendWebP';
        //ImageUrlsReplacer::$urlValidatorFunction = 'self::urlValidatorFunction';

        foreach ($tests as list($html, $expectedOutput)) {
            $output = ImageUrlsReplacer::replace($html);
            $this->assertEquals($expectedOutput, $output);
        }
    }

    public function testWholeEngine()
    {

        $tests = [
            ['<img data-x="1.png">', '<img data-x="1.png.webp">'],
            ['<img data-x="2.jpg 1000w">', '<img data-x="2.jpg.webp 1000w">'],
            ['<img data-x="3.jpg 1000w, 4.jpg 2000w">', '<img data-x="3.jpg.webp 1000w, 4.jpg.webp 2000w">'],
            ['<img data-x="5.jpg 1000w, 6.jpg">', '<img data-x="5.jpg.webp 1000w, 6.jpg.webp">'],
            ['<img data-x="7.gif 1000w, 8.jpg">', '<img data-x="7.gif 1000w, 8.jpg.webp">'],
            ['<img data-lazy-original="9.jpg">', '<img data-lazy-original="9.jpg.webp">'],

        ];

        $theseShouldBeLeftUntouchedTests = [
            '<img src="7.gif">',                // wrong ext
            '<img src="8.jpg.webp">',           // wrong ext again (its the last part that counts)
            '<img src="9.jpg?width=200">',      // leave these guys alone
            '<img src="10.jpglilo">',           // wrong ext once again
            'src="header.jpeg"',                // whats that, not in tag!
            '<script src="http://example.com/script.js?preload=image.jpg">',        // wrong tag
            '<img><script src="http://example.com/script.js?preload=image.jpg">',   // wrong tag
        ];

        foreach ($theseShouldBeLeftUntouchedTests as $skipThis) {
            $tests[] = [$skipThis, $skipThis];
        }

        // Use defaults
        ImageUrlsReplacer::$handleAttributeFunction = 'self::handleAttribute';
        ImageUrlsReplacer::$urlReplacerFunction = 'self::replaceUrl';
        //ImageUrlsReplacer::$urlValidatorFunction = 'self::isValidUrl';

        foreach ($tests as list($html, $expectedOutput)) {
            $output = ImageUrlsReplacer::replace($html);
            $this->assertEquals($expectedOutput, $output);
        }
    }
}
