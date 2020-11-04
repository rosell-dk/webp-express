<?php

namespace HtaccessCapabilityTester\Testers\Helpers;

use \HtaccessCapabilityTester\HttpResponse;
use \HtaccessCapabilityTester\TestResult;
use \HtaccessCapabilityTester\Testers\AbstractTester;

/**
 * Class for interpreting responses using a defined interpretation table.
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since 0.7
 */
class ResponseInterpreter
{

    /**
     * Parse status string (failure | success | inconclusive) to bool|null.
     *
     * @param  string  $statusString  (failure | success | inconclusive)
     * @return bool|null
     */
    private static function parseStatusString($statusString)
    {
        $status = null;
        switch ($statusString) {
            case 'failure':
                $status = false;
                break;
            case 'inconclusive':
                $status = null;
                break;
            case 'success':
                $status = true;
                break;
        }
        return $status;
    }

    /**
     * Interpret headers line
     *
     * @param  HttpResponse  $response
     * @param  string        $operator (has-key | )
     * @param  string        $fieldName  field name of the header
     * @param  string        $fieldValue (optional) field value to look for. Only required when
     *                             operator is "contains-key-value" or "not-contains-key-value"
     * @return bool          true if the condition matches, false otherwise
     */
    private static function evaluateHeadersLine($response, $operator, $fieldName, $fieldValue)
    {
        switch ($operator) {
            case 'contains-key':
                return $response->hasHeader($fieldName);
            case 'not-contains-key':
                return (!($response->hasHeader($fieldName)));
            case 'contains-key-value':
                return $response->hasHeaderValue($fieldName, $fieldValue);
            case 'not-contains-key-value':
                return (!($response->hasHeaderValue($fieldName, $fieldValue)));
        }
        return false;
    }

    /**
     * Interpret string line (body or status-code)
     *
     * @param  HttpResponse  $response
     * @param  string        $property ("body" or "status-code")
     * @param  string        $operator  (is-empty | equals | not-equals | begins-with)
     * @param  string        $arg1  (only required for some operators)
     *
     * @return bool          true if the condition matches, false otherwise
     */
    private static function evaluateStringLine($response, $property, $operator, $arg1)
    {
        $val = '';
        switch ($property) {
            case 'status-code':
                $val = $response->statusCode;
                break;
            case 'body':
                $val = $response->body;
                break;
        }

        switch ($operator) {
            case 'is-empty':
                return ($val == '');
            case 'equals':
                return ($val == $arg1);
            case 'not-equals':
                return ($val != $arg1);
            case 'begins-with':
                return (strpos($val, $arg1) === 0);
        }
        return false;
    }


    /**
     * Interpret line.
     *
     * @param HttpResponse    $response
     * @param array           $line
     *
     * @return  TestResult|null  If the condition matches, a TestResult is returned, otherwise null
     */
    private static function interpretLine($response, $line)
    {
        // ie:
        // ['inconclusive', 'body', 'is-empty'],
        // ['failure', 'statusCode', 'equals', '500']
        // ['success', 'headers', 'contains-key-value', 'X-Response-Header-Test', 'test'],

        $status = self::parseStatusString($line[0]);

        if (!isset($line[1])) {
            return new TestResult($status, '');
        }

        $propertyToExamine = $line[1];
        $operator = $line[2];
        $arg1 = (isset($line[3]) ? $line[3] : '');
        $arg2 = (isset($line[4]) ? $line[4] : '');

        if ($propertyToExamine == 'headers') {
            $match = self::evaluateHeadersLine($response, $operator, $arg1, $arg2);
        } else {
            $match = self::evaluateStringLine($response, $propertyToExamine, $operator, $arg1);
        }
        if ($match) {
            $reason = $propertyToExamine . ' ' . $operator;
            if (isset($line[3])) {
                $reason .= ' "' . implode('" "', array_slice($line, 3)) . '"';
            }
            /*
            if (($propertyToExamine == 'status-code') && ($operator == 'not-equals') && (gettype($val) == 'string')) {
                $reason .= ' - it was: ' . $val;
            }*/
            return new TestResult($status, $reason);
        }

        return null;
    }

    /**
     * Interpret a response using an interpretation table.
     *
     * @param HttpResponse    $response
     * @param array           $interpretationTable
     *
     * @return TestResult   If there is no match, the test result will have status = false and
     *                      info = "no-match".
     */
    public static function interpret($response, $interpretationTable)
    {
        foreach ($interpretationTable as $i => $line) {
            $testResult = self::interpretLine($response, $line);
            if (!is_null($testResult)) {
                return $testResult;
            }
        }
        return new TestResult(null, 'no-match');
    }
}
