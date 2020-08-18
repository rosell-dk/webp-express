<?php

namespace WebPExpress;

use \HtaccessCapabilityTester\HTTPRequesterInterface;

class WPHTTPRequester implements HTTPRequesterInterface
{
    /**
     * Make a HTTP request to a URL.
     *
     * @return  string  The response text
     */
    public function makeHTTPRequest($url) {
        $response = wp_remote_get($url, ['timeout' => 10]);
        //echo '<pre>' . print_r($response, true) . '</pre>';
        if (wp_remote_retrieve_response_code($response) != '200') {
            return 'null';
        }
        return wp_remote_retrieve_body($response);
    }
}
