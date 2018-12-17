<?php

include_once __DIR__ . '/../classes/Config.php';
use \WebPExpress\Config;

include_once __DIR__ . '/../classes/ConvertersHelper.php';
use \WebPExpress\ConvertersHelper;

include_once __DIR__ . '/../classes/FileHelper.php';
use \WebPExpress\FileHelper;

include_once __DIR__ . '/../classes/HTAccess.php';
use \WebPExpress\HTAccess;

include_once __DIR__ . '/../classes/Messenger.php';
use \WebPExpress\Messenger;

include_once __DIR__ . '/../classes/Paths.php';
use \WebPExpress\Paths;

include_once __DIR__ . '/../classes/PlatformInfo.php';
use \WebPExpress\PlatformInfo;

include_once __DIR__ . '/../classes/State.php';
use \WebPExpress\State;

include_once __DIR__ . '/../classes/TestRun.php';
use \WebPExpress\TestRun;

if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}
?>
<div class="wrap">
    <h2>WebP Express Settings</h2>

<?php

function webpexpress_converterName($converterId) {
    if ($converterId == 'wpc') {
        return 'Remote WebP Express';
    }
    return $converterId;
}

$canDetectQuality = TestRun::isLocalQualityDetectionWorking();

function printAutoQualityOptionForConverter($converterId) {
?>
    <div>
        <label for="<?php echo $converterId; ?>_quality">
            Quality
            <?php echo helpIcon('If "Auto" is selected, the converted image will get same quality as source. Auto is recommended!'); ?>
        </label>
        <select id="<?php echo $converterId; ?>_quality" onchange="converterQualityChanged('<?php echo $converterId; ?>')">
            <option value="inherit">Use global settings</option>
            <option value="auto">Auto</option>
        </select>
    </div>
    <div id="<?php echo $converterId; ?>_max_quality_div">
        <label>
            Max quality
            <?php echo helpIcon('Enter number (0-100). Converted images will be encoded with same quality as the source image, but not more than this setting'); ?>
        </label>
        <input type="text" size=3 id="<?php echo $converterId; ?>_max_quality">
    </div>
<?php
}
//update_option('webp-express-migration-version', '1');

// Test converters
$testResult = TestRun::getConverterStatus();
$workingConverters = [];
if ($testResult) {
    $workingConverters = $testResult['workingConverters'];
    //print_r($testResult);
} else {
    Messenger::printMessage(
        'error',
        'WebP Express cannot save a test conversion, because it does not have write ' .
        'access to your upload folder, nor your wp-content folder. Please provide!'
    );
}


include __DIR__ . "/page-messages.php";

/*
foreach (Paths::getHTAccessDirs() as $dir) {
    echo $dir . ':' . (Paths::canWriteHTAccessRulesHere($dir) ? 'writable' : 'not writable') . '<br>';
    //Paths::canWriteHTAccessRulesHere($dir);
}*/


$defaultConfig = [
    'cache-control' => 'no-header',
    'cache-control-custom' => 'public, max-age:3600',
    'converters' => [],
    'fail' => 'original',
    'forward-query-string' => true,
    'image-types' => 1,
    'quality-auto' => $canDetectQuality,
    'max-quality' => 80,
    'quality-specific' => 70,
    'metadata' => 'none',
    'pass-source-in-query-string' => true,
    'redirect-to-existing-in-htaccess' => false,
    'web-service' => [
        'enabled' => false,
        'whitelist' => [
            /*[
            'uid' => '',       // for internal purposes
            'label' => '',     // ie website name. It is just for display
            'ip' => '',        // restrict to these ips. * pattern is allowed.
            'api-key' => '',   // Api key for the entry. Not neccessarily unique for the entry
            //'quota' => 60
            ]
            */
        ]

    ]
];

$defaultConverters = ConvertersHelper::$defaultConverters;


$config = Config::loadConfig();
//echo '<pre>' . print_r($config, true) . '</pre>';
if (!$config) {
    $config = [];
}
//$config = [];

$config = array_merge($defaultConfig, $config);
if ($config['converters'] == null) {
    $config['converters'] = [];
}
if (!isset($config['web-service'])) {
    $config['web-service'] = [];
}
if (!isset($config['web-service']['whitelist'])) {
    $config['web-service']['whitelist'] = [];
}

// Remove keys in whitelist (so they cannot easily be picked up by examining the html)
foreach ($config['web-service']['whitelist'] as &$whitelistEntry) {
    unset($whitelistEntry['api-key']);
}

// Remove keys from WPC converters
foreach ($config['converters'] as &$converter) {
    if (isset($converter['converter']) && ($converter['converter'] == 'wpc')) {
        if (isset($converter['options']['api-key'])) {
            if ($converter['options']['api-key'] != '') {
                $converter['options']['_api-key-non-empty'] = true;
            }
            unset($converter['options']['api-key']);
        }
    }
}


if (count($config['converters']) == 0) {
    // This is first time visit!

    if (count($workingConverters) == 0) {
        // No converters are working
        // Send ewww converter to top
        $resultPart1 = [];
        $resultPart2 = [];
        foreach ($defaultConverters as $converter) {
            $converterId = $converter['converter'];
            if ($converterId == 'ewww') {
                $resultPart1[] = $converter;
            } else {
                $resultPart2[] = $converter;
            }
        }
        $config['converters'] = array_merge($resultPart1, $resultPart2);
    } else {
        // Send converters not working to the bottom
        // - and also deactivate them..
        $resultPart1 = [];
        $resultPart2 = [];
        foreach ($defaultConverters as $converter) {
            $converterId = $converter['converter'];
            if (in_array($converterId, $workingConverters)) {
                $resultPart1[] = $converter;
            } else {
                $converter['deactivated'] = true;
                $resultPart2[] = $converter;
            }
        }
        $config['converters'] = array_merge($resultPart1, $resultPart2);
    }

    // $workingConverters
    //echo '<pre>' . print_r($converters, true) . '</pre>';
} else {
    // not first time visit...
    // merge missing converters in
    $config['converters'] = ConvertersHelper::mergeConverters($config['converters'], ConvertersHelper::$defaultConverters);
}


// Set "working" and "error" properties
if ($testResult) {
    foreach ($config['converters'] as &$converter) {
        $converterId = $converter['converter'];
        $hasError = isset($testResult['errors'][$converterId]);
        $working = !$hasError;
        if (isset($converter['working']) && ($converter['working'] != $working)) {
            if ($working) {
                Messenger::printMessage(
                    'info',
                    'Hurray! - The <i>' . webpexpress_converterName($converterId) . '</i> conversion method is working now!'
                );
            } else {
                Messenger::printMessage(
                    'warning',
                    'Sad news. The <i>' . webpexpress_converterName($converterId) . '</i> conversion method is not working anymore. What happened?'
                );
            }
        }
        $converter['working'] = $working;
        if ($hasError) {
            $error = $testResult['errors'][$converterId];
            if ($converterId == 'wpc') {
                if (preg_match('/Missing URL/', $error)) {
                    $error = 'Not configured';
                }
                if ($error == 'No remote host has been set up') {
                    $error = 'Not configured';
                }

                if (preg_match('/cloud service is not enabled/', $error)) {
                    $error = 'The server is not enabled. Click the "Enable web service" on WebP Express settings on the site you are trying to connect to.';
                }
            }
            $converter['error'] = $error;
        } else {
            unset($converter['error']);
        }
    }
}
//echo '<pre>' . print_r($config['converters'], true) . '</pre>';

//echo 'Working converters:' . print_r($workingConverters, true) . '<br>';
// Generate a custom nonce value.
$webpexpress_settings_nonce = wp_create_nonce('webpexpress_settings_nonce');
?>
<p>
    <i>WebP Express takes care of serving autogenerated WebP images instead of jpeg/png to browsers that supports WebP.<br>
    The settings below does not affect your original images - only the converted webp images, and the redirection rules.</i>
</p>

<?php



echo '<form id="webpexpress_settings" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post" >';
?>
    <input type="hidden" name="action" value="webpexpress_settings_submit">
    <input type="hidden" name="webpexpress_settings_nonce" value="<?php echo $webpexpress_settings_nonce ?>" />

<?php

function helpIcon($text) {
    return '<div class="help">?<div class="popup">' . $text . '</div></div>';
}
?>
<fieldset class="block">
    <h3>Conversion options</h3>
    <table class="form-table">
        <tbody>
<?php
include_once 'options/quality.inc';
include_once 'options/metadata.inc';
include_once 'options/converters.inc';
include_once 'options/cache-control.inc';
include_once 'options/response-on-failure.inc';


//echo '</tbody></table>';
include_once 'options/web-service.inc';

?>
        </tbody>
    </table>
</fieldset>
<fieldset class="block">
    <h3>Redirect options</h3>
    <table class="form-table">
        <tbody>
<?php
include_once 'options/image-types.inc';
include_once 'options/redirect-to-existing.inc';
include_once 'options/pass-source-path-in-query-string.inc';
?>
        </tbody>
    </table>
</fieldset>
<table>
    <tr>
        <td style="padding-right:20px"><?php submit_button('Save settings', 'primary', 'mysubmit'); ?></td>
        <td><?php submit_button('Save settings and force new .htaccess rules', 'secondary', 'force'); ?></td>
    </tr>
</table>
</form>
</div>
