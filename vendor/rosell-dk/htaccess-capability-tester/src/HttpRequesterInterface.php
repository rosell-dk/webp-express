<?php

namespace HtaccessCapabilityTester;

interface HttpRequesterInterface
{
    /**
     * Make a HTTP request to a URL.
     *
     * @return  HttpResponse  A HttpResponse object, which simply contains body and status code.
     */
    public function makeHttpRequest($url);
}
