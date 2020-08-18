<?php

namespace HtaccessCapabilityTester;

interface HTTPRequesterInterface
{
    /**
     * Make a HTTP request to a URL.
     *
     * @return  string  The response text
     */
    public function makeHTTPRequest($url);
}
