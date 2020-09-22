<?php

namespace HtaccessCapabilityTester;

use \HtaccessCapabilityTester\Testers\AbstractTester;

/**
 * Class caching test results
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since the beginning
 */
class TestResultCache
{

    /* @var array   Array for caching */
    protected static $cache;

    /**
     *
     * @param  array       $cacheKeys   Two keys for caching (usually: basedir and the getCacheKey() for the Tester)
     * @param  TestResult  $testResult  The test result to cache
     *
     * @return void
     */
    public static function cache($cacheKeys, $testResult)
    {
        if (!isset(self::$cache)) {
            self::$cache = [];
        }
        list($key1, $key2) = $cacheKeys;
        if (!isset(self::$cache[$key1])) {
            self::$cache[$key1] = [];
        }
        self::$cache[$key1][$key2] = $testResult;
    }

    /**
     * Check if in cache.
     *
     * @param  array       $cacheKeys   Keys for caching (usually: basedir and the getCacheKey() for the Tester)
     *
     * @return bool
     */
    public static function isCached($cacheKeys)
    {
        if (!isset(self::$cache)) {
            return false;
        }
        list($key1, $key2) = $cacheKeys;
        if (!isset(self::$cache[$key1])) {
            return false;
        }
        if (!isset(self::$cache[$key1][$key2])) {
            return false;
        }
        return true;
    }

    /**
     * Get from cache.
     *
     * @param  array       $cacheKeys   Keys for caching (usually: basedir and the getCacheKey() for the Tester)
     *
     * @return TestResult   The test result
     */
    public static function getCached($cacheKeys)
    {
        if (!self::isCached($cacheKeys)) {
            throw new \Exception('Not in cache');
        }
        list($key1, $key2) = $cacheKeys;
        return self::$cache[$key1][$key2];
    }

    public static function clear()
    {
        self::$cache = null;
    }
}
