<?php

namespace WebPExpress;

use \HtaccessCapabilityTester\HttpRequesterInterface;
use \HtaccessCapabilityTester\HttpResponse;

class WPHttpRequester implements HttpRequesterInterface
{
    /**
     * Make a HTTP request to a URL.
     *
     * @param  string  $url  The URL to make the HTTP request to
     *
     * @return  HttpResponse  A HttpResponse object, which simply contains body, status code
     *                        and response headers
     */
    public function makeHTTPRequest($url) {
        $response = wp_remote_get($url, ['timeout' => 10]);
        //echo '<pre>' . print_r($response, true) . '</pre>';

        $body = wp_remote_retrieve_body($response);
        $statusCode = wp_remote_retrieve_response_code($response);
        $headersMap = wp_remote_retrieve_headers($response)->getAll();

        return new HttpResponse($body, $statusCode, $headersMap);
    }
}
