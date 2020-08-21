<?php

namespace HtaccessCapabilityTester;

class SimpleHttpRequester implements HTTPRequesterInterface
{
    /**
     * Make a HTTP request to a URL.
     *
     * @return  string  The response text
     */
    public function makeHTTPRequest($url)
    {
        // PS: We suppress the E_WARNING level error generated on failure
        $text = @file_get_contents($url);
        if ($text === false) {
            return '';
        }

        // var_dump($http_response_header);
        return $text;
    }
}
