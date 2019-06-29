# WebPConvert Cloud Service

This library allows you to set up your own WebP conversion cloud service. This way you can have a cloud converter for free. You won't have to worry about licenses expiring or being stolen and abused. And you will be completely in control of it (and downtime)

After setting up the cloud service, you will be able to use it to convert jpeg and png images into webp images. You can do that, using [webp-convert](https://github.com/rosell-dk/webp-convert/), or one of its implementations, such as the Wordpress plugin, [WebP Express](https://github.com/rosell-dk/webp-express/).

Alternatively to installing this library, you could install Wordpress and the the *WebP Express* plugin mentioned above. *WebP Express* can be configured to act as a conversion service. The plugin actually uses this library to achieve that functionality.

## Installation

### 1. Require the library with composer
```text
composer require rosell-dk/webp-convert-cloud-service
```

### 2. Create a script, which calls the library with configuration options

Here is an example to get started with:

```php
<?php
require 'vendor/autoload.php';

use \WebPConvertCloudService\WebPConvertCloudService;

$options = [
    // Set dir for storing converted images temporarily
    // (make sure to create that dir, with permissions for web server to write)
    'destination-dir' => '../conversions',

    // Set acccess restrictions
    'access' => [
        'whitelist' => [
            [
                'ip' => '*',
                'api-key' => 'my dog is white',
                'require-api-key-to-be-crypted-in-transfer' => false
            ]
        ]
    ],

    // Optionally set webp-convert options
    'webp-convert' => [
        'converters' => ['cwebp', 'gd', 'imagick'],
        'converter-options' => [
            'cwebp' => [
                'try-common-system-paths' => true,
                'try-supplied-binary-for-os' => true,
                'use-nice' => true
            ]
        ]
    ]
];

$wpc = new WebPConvertCloudService();
$wpc->handleRequest($options);
?>

```

### 3. Test if it works

You can call the API with curl.
First thing you might do is to test if service is available.
You can do that by asking for the api-version, as this does not require any authorization:

```
curl --form action="api-version" http://wpc.example.com/wpc.php
```

Next, you can test access.
If you have set *require-api-key-to-be-crypted-in-transfer* to `false`, you can test access like this:

```
curl --form action="check-access" --form api-key="my dog is white" http://wpc.example.com/wpc.php
```

Finally, you can make a test conversion like this.
First, place a file *test.jpg* in your current dir, then run:
```
curl --form action="convert" --form api-key="my dog is white" --form file=@test.jpg http://wpc.example.com/wpc.php > test.webp
```
If you get a corrupt file, then it is probably because the output contains an error message. To see it, run the above command again, but remove the piping of the output to a file.

You will probably not need to know more of the API. But in case you do, check out [docs/api.md](https://github.com/rosell-dk/webp-convert-cloud-service/blob/master/docs/api.md)

## Mad Scientist-ware
If you enjoy this software, feel free to conduct some secret experiments and go mad. If you like.
