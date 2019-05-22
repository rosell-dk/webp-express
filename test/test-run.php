<?php
use \WebPConvert\WebPConvert;
use \WebPConvert\Convert\ConverterFactory;
use \WebPConvert\Loggers\EchoLogger;
//use WebPConvertAndServe\WebPConvertAndServe;
//use WebPConvert\Converters\ConverterHelper;
//use WebPConvertAndServe;

error_reporting(E_ALL);
ini_set("display_errors", 1);

if (isset($_GET['stream-webp-image'])) {
    header('Content-type: image/webp');
    if (@readfile($_GET['stream-webp-image']) === false) {
        // ...
    }
    exit;
}


//require "../wod/webp-convert.inc";
require "../vendor/autoload.php";

/**
 *  Sets the options from the query string
 *  We make sure only to set those options that are declared by the converter
 */
function getConverterOptionsFromQueryString($converterId, $converterInstance)
{

    // Get meta about the options that the converter supports
    //$converterClassName = 'WebPConvert\\Convert\\Converters\\' . ucfirst($converterId);

    $optionDefinitions = $converterInstance->getOptionDefinitions();
    //echo '<pre>' . print_r($optionDefinitions, true) . '</pre>';


    //print_r($availOptions);

    // Set options
    $options = [];
    $sensitiveOptions = [];

    foreach ($optionDefinitions as $i => $def) {
        list($optionName, $optionType) = $def;
        if (isset($def[3]) && $def[3] === true) {
            $sensitiveOptions[$optionName] = true;
        }

        if (!isset($_GET[$optionName])) {
            //echo 'not set:' . $optionName . '<br>';
            continue;
        }

        if ($optionType == 'number|string') {
            if ($_GET[$optionName] == 'auto') {
                $options[$optionName] = 'auto';
                continue;
            }
            $optionType = 'number';
        }

        if ($optionType == 'boolean|string') {
            if ($_GET[$optionName] == 'auto') {
                $options[$optionName] = 'auto';
                continue;
            }
            $optionType = 'boolean';
        }

        //echo $optionName . ':' . $optionType . '<br>';
        switch ($optionType) {
            case 'string':
                $options[$optionName] = $_GET[$optionName];
                break;
            case 'number':
                if ($_GET[$optionName] == '') {
                    $options[$optionName] = null;
                } else {
                    $options[$optionName] = floatval($_GET[$optionName]);
                }
                break;
            case 'integer':
                if ($_GET[$optionName] == '') {
                    $options[$optionName] = null;
                } else {
                    $options[$optionName] = intval($_GET[$optionName]);
                }
                break;
            case 'boolean':
                $options[$optionName] = ($_GET[$optionName] == 'true');
                break;

        }
    }

    if ($converterId == 'wpc') {

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

    echo '<h4 style="margin-bottom:4px">Options</h4>';

    foreach ($options as $optionName => $optionValue) {
        if (isset($sensitiveOptions[$optionName])) {
            echo $optionName . ' : **** (sensitive) <br>';
        } else {

            echo $optionName . ' : <i>';

            switch (gettype($optionValue)) {
                case 'boolean':
                    echo $optionValue ? 'yes' : 'no';
                    break;
                case 'NULL':
                    echo 'not set';
                    break;
                default:
                    //echo $optionValue . ' (' . gettype($optionValue) . ')';
                    echo $optionValue;
            }
            echo '</i><br>';
        }
    }
    return $options;
}

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
$converterId = $_GET['converter'];

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

$converterInstance = ConverterFactory::makeConverter(
    $converterId,
    $source,
    $destination,
    [],
    new EchoLogger()
);

$options = getConverterOptionsFromQueryString($converterId, $converterInstance);
/*
echo '<h3>Options:</h3>';
foreach ($options as $optionName => $optionValue) {

}*/
//echo '<pre>' . print_r($options, true) . '</pre>';
$converterInstance->setProvidedOptions($options);

try {
    //echo '<br><br>';
    echo '<h4 style="margin-bottom:4px">Starting conversion</h4>';
    $converterInstance->doConvert();

    if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false )) {
        //echo '<img src="' . $_GET['destinationUrl'] . '" width=48%><br><br>';
        echo '<img src="?stream-webp-image=' . $destination . '" width=48%><br><br>';

    }
} catch (\Exception $e) {
    $msg = $e->getMessage();
    echo '<h3 class="error">Test conversion failed</h3>';

    if (isset($msg)) {
        echo '<label>Problem:</label>';
        //echo '<p class="failure">' . $failure . '</p>';
        //echo '<label>Details:</label>';
        echo '<p class="error-msg">' . $msg . '</p>';
    }
}

//$options = getConverterOptionsFromQueryString($converterId, $converterInstance);
//$converterInstance->setProvidedOptions($options);



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
