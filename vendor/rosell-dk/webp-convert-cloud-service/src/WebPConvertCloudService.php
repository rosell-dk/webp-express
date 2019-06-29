<?php

/**
 *
 * @link https://github.com/rosell-dk/webp-convert-cloud-service
 * @license MIT
 */

namespace WebPConvertCloudService;

use WebPConvertCloudService\Serve;
use WebPConvertCloudService\AccessCheck;

class WebPConvertCloudService
{
    public $options;

    const ERROR_CONFIGURATION = 0;
    const ERROR_ACCESS_DENIED = 1;
    const ERROR_RUNTIME = 2;

    /*
    example yaml:

        destination-dir: '../conversions'
        access:
            allowed-hosts:
                 - bitwise-it.dk
            allowed-ips:
                - 127.0.0.1
            secret: 'my dog is white'

            whitelist:
                -
                    label: 'rosell.dk'
                    ip: 212.14.2.1
                    secret: 'aoeuth8aoeuh'
                -
                    label: 'public'
                    secret: '9tita8hoetua'

        webp-convert:
            quality: 80
            ...
    */
    /*
    public function loadConfig()
    {
        $configDir = __DIR__;

        $parentFolders = explode('/', $configDir);
        $poppedFolders = [];

        while (!(file_exists(implode('/', $parentFolders) . '/wpc-config.yaml')) && count($parentFolders) > 0) {
            array_unshift($poppedFolders, array_pop($parentFolders));
        }
        if (count($parentFolders) == 0) {
            self::exitWithError(
                WebPConvertCloudService::ERROR_SERVER_SETUP,
                'wpc-config.yaml not found in any parent folders.'
            );
        }
        $configFilePath = implode('/', $parentFolders) . '/wpc-config.yaml';

        try {
            $options = \Spyc::YAMLLoad($configFilePath);
        } catch (\Exception $e) {
            self::exitWithError(WebPConvertCloudService::ERROR_SERVER_SETUP, 'Error parsing wpc-config.yaml.');
        }
    }*/

    public static function exitWithError($errorCode, $msg)
    {
        $returnObject = [
            'success' => 0,
            'errorCode' => $errorCode,
            'errorMessage' => $msg,
        ];
        echo json_encode($returnObject);
        exit;
    }

    public static function handleRequest($options)
    {
        //$this->options = static::loadConfig();
        if (!isset($options)) {
            self::exitWithError(self::ERROR_SERVER_SETUP, 'No options was supplied');
        }

        $action = (isset($_POST['action']) ? $_POST['action'] : 'convert');

        // Handle actions that does not require access check

        switch ($action) {
            case 'api-version':
                echo '2';
                exit;
        }

        AccessCheck::runAccessChecks($options);

        // Handle actions that requires access check

        switch ($action) {
            case 'check-access':
                echo "You have access!\n";
                break;
            case 'convert':
                Serve::serve($options);
                break;
        }
    }
}
