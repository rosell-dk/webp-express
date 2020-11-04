<?php

namespace HtaccessCapabilityTester;

interface HttpRequesterInterface
{
    /**
     * Make a HTTP request to a URL.
     *
     * @return  HttpResponse  A HttpResponse object, which simply contains body, status code and response headers.
     *                        In case the request itself fails, the status code is "0" and the body should contain
     *                        error description (if available)
     */
    public function makeHttpRequest($url);
}
