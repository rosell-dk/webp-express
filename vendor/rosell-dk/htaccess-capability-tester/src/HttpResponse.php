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

    /* @var array  the response headers keyed by lowercased field name */
    public $headersMapLowerCase;

    /**
     * Constructor.
     *
     * @param  string  $body
     * @param  string  $statusCode
     * @param  array   $headersMap     Map of headers, keyed by field name.
     *                                 There is only one value (string) for each key.
     *                                 If there are multiple values, they must be separated by comma
     *
     * @return void
     */
    public function __construct($body, $statusCode, $headersMap)
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headersMapLowerCase = array_change_key_case($headersMap, CASE_LOWER);
    }

    /**
     *  Check if the response has a header
     *
     *  @param  string $fieldName
     *  @return bool
     */
    public function hasHeader($fieldName)
    {
        $fieldName = strtolower($fieldName);
        return (isset($this->headersMapLowerCase[$fieldName]));
    }

    /**
     *  Check if the response has a header with a given value
     *
     *  @param  string $fieldName
     *  @param  string $fieldValue
     *  @return bool
     */
    public function hasHeaderValue($fieldName, $fieldValue)
    {
        $fieldName = strtolower($fieldName);
        if (!isset($this->headersMapLowerCase[$fieldName])) {
            return false;
        }
        $values = explode(',', $this->headersMapLowerCase[$fieldName]);
        foreach ($values as $value) {
            if (trim($value) == $fieldValue) {
                return true;
            }
        }
        return false;
    }
}
