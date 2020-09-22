<?php

namespace HtaccessCapabilityTester\Tests;
use HtaccessCapabilityTester\HtaccessCapabilityTester;

class Helper
{

    public static function getTesterUsingFakeServer($fakeServer)
    {
        $hct = new HtaccessCapabilityTester('', '');
        $hct->setTestFilesLineUpper($fakeServer);
        $hct->setHttpRequester($fakeServer);
        return $hct;
    }


}
