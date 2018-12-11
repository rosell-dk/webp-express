<?php

if (isset($_GET['stream-webp-image'])) {
    header('Content-type: image/webp');
    if (@readfile($_GET['stream-webp-image']) === false) {
        // ...
    }
    exit;
}

error_reporting(E_ALL);
ini_set("display_errors", 1);

//require "../wod/webp-convert.inc";
require "../vendor/autoload.php";

use WebPConvert\WebPConvert;
use WebPConvert\Loggers\EchoLogger;
//use WebPConvertAndServe\WebPConvertAndServe;
//use WebPConvert\Converters\ConverterHelper;
//use WebPConvertAndServe;

?>
<html>
<head>
    <style>
        body {
            padding:10px;
            font-size: 17px;
        }
        p {
            margin-top: 0;
        }
        label {
            font-style: italic;
        }
        p.error-msg {
            /*font-size: 20px;*/
        }
        h3 {color: red}
    </style>
</head>
<body style="">

<?php

/**
 *  Paths passed in query string were encoded, to avoid triggering LFI warning in Wordfence
 *  (encoding is done in converters.js)
 *  see https://github.com/rosell-dk/webp-express/issues/87
 */
function decodePathInQS($encodedPath) {
    return preg_replace('/\*\*/', '/', $encodedPath);
}

//WebPConvertAndServe::convertAndReport($source, $destination, $options);use WebPConvert\Loggers\EchoLogger;
$source = decodePathInQS($_GET['source']);
$destination = decodePathInQS($_GET['destination']);
$converter = $_GET['converter'];

if (isset($_GET['max-quality'])) {
  $options['max-quality'] = intval($_GET['max-quality']);
}
if (isset($_GET['quality'])) {
  $options['quality'] = intval($_GET['quality']);
}

/*
if (isset($_GET['method'])) {
  $options['method'] = intval($_GET['method']);
}*/

/**
 *  Sets the options from the query string
 *  We make sure only to set those options that are declared by the converter
 */
function getConverterOptionsFromQueryString($converter)
{

    // Get meta about the options that the converter supports
    $converterClassName = 'WebPConvert\\Converters\\' . ucfirst($converter);
    $availOptions = array_column($converterClassName::$extraOptions, 'type', 'name');
    //print_r($availOptions);

    // Set options
    $options = [];
    foreach ($availOptions as $optionName => $optionType) {
        //echo $optionName . ':' . $optionType . '<br>';
        switch ($optionType) {
            case 'string':
                if (isset($_GET[$optionName])) {
                    $options[$optionName] = $_GET[$optionName];
                }
                break;
            case 'number':
                if (isset($_GET[$optionName])) {
                    $options[$optionName] = floatval($_GET[$optionName]);
                }
                break;
            case 'boolean':
                if (isset($_GET[$optionName])) {
                    $options[$optionName] = ($_GET[$optionName] == 'true');
                }
                break;
        }
    }

    if ($converter == 'wpc') {

        // Handle api key.
        // If it has been modified on the options page, it is passed as 'new-api-key'.
        // If it has not been modified, it is not passed at all!
        // - in that case, we must load it from the config file.

        if (isset($_GET['new-api-key'])) {
            $options['api-key'] = $_GET['new-api-key'];
        } elseif (isset($_GET['configDirRel'])) {

            // Fetch api-key from configuration file.
            $configFilename = $_SERVER['DOCUMENT_ROOT'] . '/' . decodePathInQS($_GET['configDirRel']) . '/config.json';

            if (file_exists($configFilename)) {

                $handle = @fopen($configFilename, "r");
                $json = fread($handle, filesize($configFilename));
                fclose($handle);

                $config = json_decode($json, true);
                if ($config) {
                    foreach ($config['converters'] as $converter) {
                        if ($converter['converter'] == 'wpc') {
                            //print_r($converter);
                            if (isset($converter['options']['api-key'])) {
                                $options['api-key'] = $converter['options']['api-key'];
                                //echo 'api-key:' . $converter['options']['api-key'] . '<br>';
                                //print_r($options);
                            }
                        }
                    }
                }
            }
        }
        if (!isset($options['api-key'])) {
            echo '<p style="color:red">Warning: No Api key is set</p>';
        }
    }

    return $options;
}
$options['converters'] = [[
    'converter' => $converter,
    'options' => getConverterOptionsFromQueryString($converter)
]];





//echo '<pre>' . print_r($_GET, true) . '</pre>';
//echo '<pre>' . print_r($options, true) . '</pre>';

function testRun($converter, $source, $destination, $options) {

    $success = false;
    try {
        $success = WebPConvert::convert($source, $destination, $options, new EchoLogger());
    } catch (\Exception $e) {
        $msg = $e->getMessage();
    }

    if (!$success) {
        echo '<h3 class="error">Test conversion failed</h3>';

        if (isset($msg)) {
            echo '<label>Problem:</label>';
            //echo '<p class="failure">' . $failure . '</p>';
            //echo '<label>Details:</label>';
            echo '<p class="error-msg">' . $msg . '</p>';
        }
    } else {
        //echo '<p>Successfully converted test image</p>';

        if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false )) {
            //echo '<img src="' . $_GET['destinationUrl'] . '" width=48%><br><br>';
            echo '<img src="?stream-webp-image=' . $destination . '" width=48%><br><br>';

        }
    }
}

testRun($converter, $source, $destination, $options);
