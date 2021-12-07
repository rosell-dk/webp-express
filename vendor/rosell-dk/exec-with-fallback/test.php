<?php
include 'vendor/autoload.php';
//include 'src/ExecWithFallback.php';
use ExecWithFallback\ExecWithFallback;
use ExecWithFallback\Tests\ExecWithFallbackTest;

ExecWithFallback::exec('echo hello');

ExecWithFallbackTest::testExec();
