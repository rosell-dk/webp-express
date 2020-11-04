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

        if (is_wp_error($response)) {
            return new HttpResponse($response->get_error_message(), '0', []);
        } else {
            $body = wp_remote_retrieve_body($response);
            $statusCode = wp_remote_retrieve_response_code($response);
            $headersDict = wp_remote_retrieve_headers($response);
            if (method_exists($headersDict, 'getAll')) {
                $headersMap = $headersDict->getAll();
            } else {
                $headersMap = [];
            }
            return new HttpResponse($body, $statusCode, $headersMap);
        }
    }
}
