<?php

namespace WebPConvertCloudService;

use \WebPConvertCloudService\WebPConvertCloudService;

class AccessCheck
{

    private static function accessDenied($msg)
    {
        WebPConvertCloudService::exitWithError(WebPConvertCloudService::ERROR_ACCESS_DENIED, $msg);
    }

    /**
     *  Test an IP (ie "212.67.80.1") against a pattern (ie "212.*")
     */
    private static function testIpPattern($ip, $pattern)
    {
        $regEx = '/^' . str_replace('*', '.*', $pattern) . '$/';

        if (preg_match($regEx, $ip)) {
            return true;
        }
        return false;
    }

    public static function runAccessChecks($options)
    {
        $accessOptions = $options['access'];

        $onWhitelist = false;
        if (isset($accessOptions['whitelist']) && count($accessOptions['whitelist']) > 0) {
            foreach ($accessOptions['whitelist'] as $whitelistItem) {
                if (isset($whitelistItem['ip'])) {
                    if (!self::testIpPattern($_SERVER['REMOTE_ADDR'], $whitelistItem['ip'])) {
                        continue;
                    }
                }
                $onWhitelist = true;

                if (!isset($whitelistItem['api-key']) || $whitelistItem['api-key'] == '') {
                    // This item requires no api key
                    // Access granted!
                    return;
                }

                if (isset($_POST['salt']) && isset($_POST['api-key-crypted'])) {
                    if (CRYPT_BLOWFISH == 1) {
                        // Strip off the first 28 characters (the first 6 are always "$2y$10$". The next 22 is the salt)
                        $cryptedKey = substr(crypt($whitelistItem['api-key'], '$2y$10$' . $_POST['salt'] . '$'), 28);
                        if ($_POST['api-key-crypted'] == $cryptedKey) {
                            // Access granted!
                            return;
                        }
                    } else {
                        // trouble...
                    }
                } else {
                    $hashingRequired = (
                        isset($whitelistItem['require-api-key-to-be-crypted-in-transfer']) &&
                        $whitelistItem['require-api-key-to-be-crypted-in-transfer']
                    );
                    if (!$hashingRequired && isset($_POST['api-key'])) {
                        if ($_POST['api-key'] == $whitelistItem['api-key']) {
                            // Access granted!
                            return;
                        }
                    }
                }
            }
        }


        if ($onWhitelist) {
            if (isset($_POST['salt']) && isset($_POST['api-key-crypted'])) {
                self::accessDenied('Invalid api key');
            } else {
                if (isset($_POST['api-key'])) {
                    self::accessDenied('Either api key is invalid, or you must crypt the api key');
                } else {
                    if (isset($_POST['salt']) && isset($_POST['api-key-crypted'])) {
                        self::accessDenied('You need to supply a valid api key');
                    } else {
                        if (!isset($_POST['api-key-crypted'])) {
                            self::accessDenied('You need to supply an api key');
                        } else {
                            if (!isset($_POST['salt'])) {
                                self::accessDenied('You must supply salt to go with you encripted api key');
                            }
                        }
                    }
                }
            }
        } else {
            self::accessDenied('Access denied');
        }
    }
}
