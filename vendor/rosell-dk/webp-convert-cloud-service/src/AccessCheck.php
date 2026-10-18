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
                      $salt = $_POST['salt'];
                      $apiKeyCrypted = $_POST['api-key-crypted'];
              
                      // Step 1: salt must be exactly 22 bcrypt-alphabet characters
                      if (!is_string($salt) || !preg_match('/^[.\/0-9A-Za-z]{22}$/D', $salt)) {
                          continue;
                      }
              
                      // Step 2a: the submitted crypted key must be a non-empty string
                      if (!is_string($apiKeyCrypted) || $apiKeyCrypted === '') {
                          continue;
                      }
              
                      $fullHash = crypt($whitelistItem['api-key'], '$2y$10$' . $salt . '$');
              
                      // Step 2b: crypt() must have returned a genuine bcrypt hash.
                      // (On failure it returns "*0" or "*1")
                      if (!is_string($fullHash) || strlen($fullHash) !== 60 || strpos($fullHash, '$2y$10$') !== 0) {
                          continue;
                      }
              
                      // Strip off the first 28 characters (7 for "$2y$10$", then the 22-char salt)
                      $cryptedKey = substr($fullHash, 28);
                      if (hash_equals($cryptedKey, $apiKeyCrypted)) {
                          // Access granted!
                          return;
                      }
                  } else {
                      // crypt_blowfish not available, access cannot be granted
                      // we fail silently
                  }
              } else {
                    $hashingRequired = (
                        isset($whitelistItem['require-api-key-to-be-crypted-in-transfer']) &&
                        $whitelistItem['require-api-key-to-be-crypted-in-transfer']
                    );
                    if (!$hashingRequired && isset($_POST['api-key'])) {
                      if (
                          is_string($_POST['api-key']) &&
                          is_string($whitelistItem['api-key']) &&
                          hash_equals($whitelistItem['api-key'], $_POST['api-key'])
                      ) {
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
