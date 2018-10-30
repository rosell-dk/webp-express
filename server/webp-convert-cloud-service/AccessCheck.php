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

/*
        if (isset($accessOptions['allowed-ips']) && count($accessOptions['allowed-ips']) > 0) {
            $ipCheckPassed = false;
            foreach ($accessOptions['allowed-ips'] as $ip) {
                if ($ip == $_SERVER['REMOTE_ADDR']) {
                    $ipCheckPassed = true;
                    break;
                }
            }
            if (!$ipCheckPassed) {
                self::accessDenied('Restricted access. Not on IP whitelist');
            }
        }

        if (isset($accessOptions['allowed-hosts']) && count($accessOptions['allowed-hosts']) > 0) {
            $h = $_SERVER['REMOTE_HOST'];
            if ($h == '') {
                // Alternatively, we could catch the notice...
                $wpc->exitWithError(WebPConvertCloudService::ERROR_SERVER_SETUP, 'WPC is configured with allowed-hosts option. But the server is not set up to resolve host names. For example in Apache you will need HostnameLookups On inside httpd.conf. See also PHP documentation on gethostbyaddr().');
            }
            $hostCheckPassed = false;
            foreach ($accessOptions['allowed-hosts'] as $hostName) {
                if ($hostName == $_SERVER['REMOTE_HOST']) {
                    $hostCheckPassed = true;
                    break;
                }
            }
            if (!$hostCheckPassed) {
                self::accessDenied('Restricted access. Hostname is not on whitelist');
            }
        }
*/
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
                    $hashingRequired = (isset($whitelistItem['require-api-key-to-be-crypted-in-transfer']) && $whitelistItem['require-api-key-to-be-crypted-in-transfer']);
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
                    self::accessDenied('Either api key is invalid, or you must hash the api key (supply "api-key-crypted" and "salt" instead of simply "api-key")');
                } else {
                    self::accessDenied('You need to supply an api key');
                }
            }
        } else {
            self::accessDenied('Access denied');
        }

    }


}
