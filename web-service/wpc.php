<?php

//require '../vendor/rosell-dk/webp-convert/build/webp-on-demand-1.inc';
require __DIR__ . '/../vendor/autoload.php';
use \WebPConvertCloudService\WebPConvertCloudService;

include_once __DIR__ . '../../lib/classes/Config.php';
use \WebPExpress\Config;

include_once __DIR__ . '../../lib/classes/Paths.php';
use \WebPExpress\Paths;

include_once __DIR__ . '/../lib/classes/Config.php';


// Patch together $options object for wpc...
$options = [];

$config = Config::loadConfig();
if ($config === false) {
    if (Config::isConfigFileThere()) {
        WebPConvertCloudService::exitWithError(WebPConvertCloudService::ERROR_CONFIGURATION, 'config file could not be loaded.');
    } else {
        WebPConvertCloudService::exitWithError(WebPConvertCloudService::ERROR_CONFIGURATION, 'config file could not be loaded (its not there): ' . Paths::getConfigFileName());
    }
}

if (!isset($config['web-service'])) {
    WebPConvertCloudService::exitWithError(WebPConvertCloudService::ERROR_ACCESS_DENIED, 'Web Service is not configured');
}

$webServiceConfig = $config['web-service'];

if (!$webServiceConfig['enabled']) {
    WebPConvertCloudService::exitWithError(WebPConvertCloudService::ERROR_ACCESS_DENIED, 'Web Service is not enabled');
}

$options['destination-dir'] = Paths::getCacheDirAbs() . '/wpc';
$options['access'] = [
    //'allowed-ips' => ['127.0.0.1'],
    'whitelist' => $webServiceConfig['whitelist']
];

/*
$options['access'] = [
    'whitelist' => [
        [
            'label' => 'testing',
            'ip' => '127.0.0.1',
            'api-key' => 'my dog is white',
            'require-api-key-to-be-hashed-in-transfer' => false,
        ]
    ]
]; */

$options['webp-convert'] = Config::generateWodOptionsFromConfigObj($config);

WebPConvertCloudService::handleRequest($options);
