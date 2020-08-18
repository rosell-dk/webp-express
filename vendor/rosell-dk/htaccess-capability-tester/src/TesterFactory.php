<?php

namespace HtaccessCapabilityTester;

/**
 * Class for creating an object of one of the provided Tester classes easily.
 *
 * @package    HtaccessCapabilityTester
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since 0.3
 */
class TesterFactory
{


    /**
     * Child classes must implement this method, which tells which subdir the
     * test files are to be put.
     *
     * @param  string  $id
     * @param  string  $baseDir  Directory on the server where the test files can be put
     * @param  string  $baseUrl  The base URL of the test files
     *
     * @return AbstractTester|null  A Tester instance or null if not found
     */
    public static function create($id, $baseDir, $baseUrl)
    {
        switch ($id) {
            case 'grant-all-crash-tester':
                return new GrantAllCrashTester($baseDir, $baseUrl);
            case 'rewrite-tester':
                return new RewriteTester($baseDir, $baseUrl);
            case 'set-request-header-tester':
                return new SetRequestHeaderTester($baseDir, $baseUrl);
            case 'pass-env-through-request-header-tester':
                return new PassEnvThroughRequestHeaderTester($baseDir, $baseUrl);
            case 'pass-env-through-rewrite-tester':
                return new PassEnvThroughRewriteTester($baseDir, $baseUrl);
        }
        return null;
    }

}
