<?php

namespace HtaccessCapabilityTester;

class SimpleHttpRequester implements HttpRequesterInterface
{
    /**
     * Make a HTTP request to a URL.
     *
     * @param  string  $url  The URL to make the HTTP request to
     *
     * @return  HttpResponse  A HttpResponse object, which simply contains body, status code
     *                        and response headers
     */
    public function makeHttpRequest($url)
    {
        // PS: We suppress the E_WARNING level error generated on failure
        $body = @file_get_contents($url);
        if ($body === false) {
            $body = '';
        }

        // $http_response_header materializes out of thin air when file_get_contents() is called

        // Get status code
        $statusLine = $http_response_header[0];
        preg_match('{HTTP\/\S*\s(\d{3})}', $statusLine, $match);
        $statusCode = $match[1];

        // Create headers map
        $headersMap = [];
        foreach ($http_response_header as $header) {
            $pos = strpos($header, ':');
            if ($pos > 0) {
                $fieldName = strtolower(trim(substr($header, 0, $pos)));
                $value = trim(substr($header, $pos + 1));
                if (!isset($headersMap[$fieldName])) {
                    $headersMap[$fieldName] = $value;
                } else {
                    $headersMap[$fieldName] .= ', ' . $value;
                }
            }
        }
        return new HttpResponse($body, $statusCode, $headersMap);
    }
}
