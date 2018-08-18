<?php

//require 'webp-on-demand/vendor/autoload.php';


//require 'vendor/webp-convert-and-serve/autoload.php';
//require 'vendor/webp-convert/autoload.php';

error_reporting(E_ALL);
ini_set("display_errors", 1);

//require 'vendor/webp-convert/require-all.inc';
require 'vendor/require-webp-convert.php';

//use WebPConvertAndServe\WebPConvertAndServe;
use WebPConvert\WebPConvert;
use WebPConvert\Converters\ConverterHelper;

// TODO:
// Much of this file could be moved into the libraries.
// Ie:
// - Report (such as "trying gd", "successfully...", "file size (original)") could be part of WebPConvertAndServe
//     ($REPORT_AS_IMAGE and $REPORT actions could show complete report, and convertAndReport too)
//   - or even be part of WebPConvert - ie the log could be returned in a variable passed by reference.
//

$source = $_GET['source'];
$destination = $_GET['destination'];
$converter = $_GET['converter'];

if (isset($_GET['max-quality'])) {
  $options['max-quality'] = intval($_GET['max-quality']);
}

if (isset($_GET['method'])) {
  $options['method'] = intval($_GET['method']);
}

/*
switch ($converter) {
    case 'ewww':
        if (isset($_GET['key'])) {
            $options['key'] = $_GET['key'];
        }
        break;
    case 'cwebp':
        if (isset($_GET['use-nice'])) {
            $options['use-nice'] = boolval($_GET['use-nice'] == 'true');
        }
        break;
    case 'gd':
        if (isset($_GET['skip-pngs'])) {
            $options['skip-pngs'] = boolval($_GET['skip-pngs'] == 'true');
        }
        break;
}*/

$converterClassName = 'WebPConvert\\Converters\\' . ucfirst($converter);
$availOptions = array_column($converterClassName::$extraOptions, 'type', 'name');
//print_r($availOptions);

$hasFallback = false;
$options2 = [];
foreach ($availOptions as $optionName => $optionType) {
    if (isset($_GET[$optionName . '-2'])) {
        if ($_GET[$optionName . '-2'] != '') {
            $hasFallback = true;
            $options2 = $options;
//            echo 'value:' . $_GET[$optionName . '-2'];
            break;
        }
    }
}

foreach ($availOptions as $optionName => $optionType) {
    switch ($optionType) {
        case 'string':
            if (isset($_GET[$optionName])) {
                $options[$optionName] = $_GET[$optionName];
            }
            if (isset($_GET[$optionName. '-2'])) {
                $options2[$optionName] = $_GET[$optionName . '-2'];
            } else {
                if ($hasFallback) {
                    $options2[$optionName] = $options[$optionName];
                }
            }
            break;
        case 'boolean':
            if (isset($_GET[$optionName])) {
                $options[$optionName] = ($_GET[$optionName] == 'true');
            }
            break;
    }
}

//echo '<pre>' . print_r($options, true) . '</pre>';
//echo '<pre>' . print_r($options2, true) . '</pre>';

//echo '';
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
//echo '<p>source: ' . $source . '</p>';
//echo '<p>destination: ' . $destination . '</p>';
//echo '<p>converter: ' . $converter . '</p>';
//echo '</body></html>';

//WebPConvertAndServe::convertAndReport($source, $destination, $options);

function testRun($converter, $source, $destination, $options) {
    $beginTime = microtime(true);

    try {
        ConverterHelper::runConverter($converter, $source, $destination, $options);
    } catch (\WebPConvert\Exceptions\WebPConvertBaseException $e) {
        $failure = $e->description;
        $msg = $e->getMessage();
    } catch (\Exception $e) {
        $failure = 'Unancipated failure';
        $msg = $e->getMessage();
    }

    $endTime = microtime(true);
    $duration = $endTime - $beginTime;

    if (isset($msg)) {
        echo '<h3 class="error">Test conversion failed (in ' . round($duration * 1000) . ' ms)</h3>';
        echo '<label>Problem:</label>';
        echo '<p class="failure">' . $failure . '</p>';
        echo '<label>Details:</label>';
        echo '<p class="error-msg">' . $msg . '</p>';
    } else {
        echo '<p>Successfully converted test image in ' . round($duration * 1000) . ' ms</p>';

        if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false )) {
            echo '<img src="' . $_GET['destinationUrl'] . '" width=48%><br><br>';
        }
        if (filesize($source) < 10000) {
            echo 'file size (original): ' . round(filesize($source)) . ' bytes<br>';
            echo 'file size (converted): ' . round(filesize($destination)) . ' bytes<br>';
        }
        else {
            echo 'file size (original): ' . round(filesize($source)/1000) . ' kb<br>';
            echo 'file size (converted): ' . round(filesize($destination)/1000) . ' kb<br>';
        }
    }
}

testRun($converter, $source, $destination, $options);

if ($hasFallback) {
    echo '<h2>Testing fallback</h2>';
    testRun($converter, $source, $destination, $options2);
}




/*
$className = 'WebPConvert\\Converters\\' . ucfirst($converter);

if (!is_callable([$className, 'convert'])) {
    echo 'Converter does not appear to exist!';
    exit;
}

try {
    call_user_func(
        [$className, 'convert'],
        $source,
        $destination,
        $options
    );
} catch (\WebPConvert\Converters\Exceptions\ConverterNotOperationalException $e) {
    // The converter is not operational.
    $failure = 'The converter is not operational';

    // TODO: We should show link to install instructions for the specific converter (WIKI)

    $msg = $e->getMessage();
} catch (\WebPConvert\Converters\Exceptions\ConverterFailedException $e) {
    $failure = 'The converter failed converting, although requirements seemed to be met';
    $msg = $e->getMessage();
} catch (\WebPConvert\Converters\Exceptions\ConversionDeclinedException $e) {
    $failure = 'The converter declined converting';
    $msg = $e->getMessage();
} catch (\WebPConvert\Exceptions\InvalidFileExtensionException $e) {
    $failure = 'The converter does not accept the file extension';
    $msg = $e->getMessage();
} catch (\WebPConvert\Exceptions\TargetNotFoundException $e) {
    $failure = 'The converter could not locate source file';
    $msg = $e->getMessage();
} catch (\WebPConvert\Exceptions\CreateDestinationFolderException $e) {
    $failure = 'The converter could not create destination folder. Check file permisions!';
    $msg = $e->getMessage();
} catch (\WebPConvert\Exceptions\CreateDestinationFileException $e) {
    $failure = 'The converter could not create destination file. Check file permisions!';
    $msg = $e->getMessage();
} catch (\Exception $e) {
    $failure = 'Unexpected failure';
    $msg = $e->getMessage();
}
*/




/*
try {
    $options = [
        'converters' => [$converter]
    ];
    $success = WebPConvert::convert($source, $destination, $options);
} catch (\Exception $e) {
    $success = false;
    $msg = $e->getMessage();
}

if ($success) {
    $endTime = microtime(true);

    $duration = $endTime - $beginTime;
    echo '<p>Successfully converted test image in ' . round($duration * 1000) . ' ms</p>';


    if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false )) {
        echo '<img src="' . $_GET['destinationUrl'] . '" width=50%>';
    }
} else {
    echo 'Converter failed ';
    echo $msg;
}
*/
/*
$status = WebPOnDemand::serve(__DIR__);
if ($status < 0) {
    // Conversion failed.
    // you could message your application about the problem here...
}*/
