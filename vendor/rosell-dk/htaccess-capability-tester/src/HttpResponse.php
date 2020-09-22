<?php

namespace HtaccessCapabilityTester;

/**
 * Class for holding properties of a HttpResponse
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since 0.7
 */
class HttpResponse
{

    /* @var string  the body of the response */
    public $body;

    /* @var string  the status code of the response */
    public $statusCode;

    /* @var array  the response headers */
    public $headers;

    /**
     * Constructor.
     *
     * @param  string  $body
     * @param  string  $statusCode
     * @param  array   $headers
     *
     * @return void
     */
    public function __construct($body, $statusCode, $headers)
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     *  Get headers as a hash.
     *
     *  Note: This little function does not handle multiple headers with the
     *        same name.
     *  @return  array  Hashed headers
     */
    public function getHeadersHash()
    {
        $hash = [];
        foreach ($this->headers as $header) {
            $pos = strpos($header, ':');
            if ($pos > 0) {
                $key = trim(substr($header, 0, $pos));
                $value = trim(substr($header, $pos + 1));
                $hash[$key] = $value;
            }
        }
        return $hash;
    }
}
