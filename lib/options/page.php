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
echo '<table class="form-table"><tbody>';

// Image types
// ------------
echo '<tr><th scope="row">Image types to convert';
echo helpIcon('Beware that the Gd conversion method cannot handle transparency for PNGs. PNG conversions havent been tested much yet. Please report any problems with PNG images <a target="_blank" href="https://github.com/rosell-dk/webp-convert/issues/42">here</a>');
echo '</th><td>';

// bitmask
// 1: JPEGs
// 2: PNG's
// Converting only jpegs is thus "1"
// Converting both jpegs and pngs is (1+2) = 3
//$imageTypes = get_option('webp_express_image_types_to_convert');
$imageTypes = $config['image-types'];

echo '<select name="image-types">';
echo '<option value="0"' . ($imageTypes == 0 ? ' selected' : '') . '>Do not convert any images!</option>';
echo '<option value="1"' . ($imageTypes == 1 ? ' selected' : '') . '>Only convert jpegs</option>';
echo '<option value="3"' . ($imageTypes == 3 ? ' selected' : '') . '>Convert both jpegs and pngs</option>';
echo '</select>';

echo '</td></tr>';

// Quality
// --------------------

if ($canDetectQuality) {
    echo '<tr><th scope="row">Quality';
    echo helpIcon('If "Auto" is selected, the converted image will get same quality as source. Auto is recommended!');
    echo '</th><td>';
    $qualityAuto = $config['quality-auto'];;
    echo '<select id="quality_auto_select" name="quality-auto">';
    echo '<option value="auto_on"' . ($qualityAuto ? ' selected' : '') . '>Auto</option>';
    echo '<option value="auto_off"' . (!$qualityAuto ? ' selected' : '') . '>Specific value</option>';
    echo '</select>';

    echo '</td></tr>';


    // Max quality
    // --------------------
    $maxQuality = $config['max-quality'];

    echo '<tr id="max_quality_row"><th scope="row">Max quality (0-100)';
    echo helpIcon('Quality is expensive byte-wise. For most websites, more than 80 is a waste of bytes. ' .
        'This option allows you to limit the quality to whatever is lowest: ' .
        'the quality of the source or max quality. Recommended value: Somewhere between 50-85');
    echo '</th><td>';

    echo '<input type="text" size=3 id="max_quality" name="max-quality" value="' . $maxQuality . '">';
    echo '</td></tr>';
} else {

}

// Quality - specific
// --------------------
$qualitySpecific = $config['quality-specific'];

echo '<tr id="quality_specific_row"><th scope="row">Quality (0-100)';
if ($canDetectQuality) {
    echo helpIcon('All converted images will be encoded with this quality');
} else {
    echo helpIcon('All converted images will be encoded with this quality. ' .
        'For Remote WebP Express and Imagick, you however have the option to use override this, and use ' .
        '"auto". With some setup, you can get quality detection working and you will then be able to set ' .
        'quality to "auto" generally. For that you either need to get the imagick extension running ' .
        '(PECL >= 2.2.2) or exec() rights and either imagick or gmagick installed.'
    );
}
echo '</th><td>';

echo '<input type="text" size=3 id="quality_specific" name="quality-specific" value="' . $qualitySpecific . '">';
echo '</td></tr>';

// Converters
// --------------------

echo '<tr><th scope="row">Conversion method';
echo helpIcon('Drag to reorder. The conversion method on top will first be tried. ' .
    'Should it fail, the next will be used, etc. To learn more about the conversion methods, <a target="_blank" href="https://github.com/rosell-dk/webp-convert/blob/master/docs/converters.md">Go here</a>');

echo '</th><td>';

$converters = $config['converters'];
echo '<script>window.converters = ' . json_encode($converters) . '</script>';
echo '<script>window.defaultConverters = ' . json_encode($defaultConverters) . '</script>';

echo "<input type='text' name='converters' value='' style='visibility:hidden; height:0' />";

// https://premium.wpmudev.org/blog/handling-form-submissions/


?>
        <?php
/*
$localConverters = ['cwebp', 'imagick', 'gd'];
        $testResult = WebPExpressHelpers::testConverters($localConverters);
        //print_r($testResult);

        if ($testResult['numOperationalConverters'] == 0) {
            echo 'Unfortunately, your server is currently not able to convert to webp files by itself. You will need to set up a cloud converter.<br><br>';
            foreach ($testResult['results'] as $result) {
                echo $result['converter'] . ':' . $result['message'] . '<br>';
            }
        } else {
            //echo 'Your server is able to convert webp files by itself.';
        }
        if ($testResult['numOperationalConverters'] == 1) {
            //
        }
*/


/*
http://php.net/manual/en/function.set-include-path.php

//exec('/usr/sbin/getsebool -a', $output6, $returnCode5); // ok
//echo 'All se bools: ' . print_r($output6, true) . '. Return code:' . $returnCode5;
*/

//echo '<h2>Conversion methods to try</h2>';
$dragIcon = '<svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="17px" height="17px" viewBox="0 0 100.000000 100.000000" preserveAspectRatio="xMidYMid meet"><g transform="translate(0.000000,100.000000) scale(0.100000,-0.100000)" fill="#444444" stroke="none"><path d="M415 920 l-80 -80 165 0 165 0 -80 80 c-44 44 -82 80 -85 80 -3 0 -41 -36 -85 -80z"/><path d="M0 695 l0 -45 500 0 500 0 0 45 0 45 -500 0 -500 0 0 -45z"/><path d="M0 500 l0 -40 500 0 500 0 0 40 0 40 -500 0 -500 0 0 -40z"/><path d="M0 305 l0 -45 500 0 500 0 0 45 0 45 -500 0 -500 0 0 -45z"/><path d="M418 78 l82 -83 82 83 83 82 -165 0 -165 0 83 -82z"/></g></svg>';

/*echo '<p><i>Drag to reorder. The conversion method on top will first be tried. ';
echo 'Should it fail, the next will be used, etc.<br>';
echo 'To learn more about the conversion methods, ';
echo '<a target="_blank" href="https://github.com/rosell-dk/webp-convert/blob/master/docs/converters.md">Go here</a></i></p>';
*/
// https://github.com/RubaXa/Sortable

// Empty list of converters. The list will be populated by the javascript

function webp_express_printUpdateButtons() {
?>
    <button onclick="updateConverterOptionsAndSave()" class="button button-primary" type="button">Update and save settings</button>
    <button onclick="updateConverterOptions()" class="button button-secondary" type="button">Update, but do not save yet</button>
    <?php
    //echo '<a href="javascript: tb_remove();">close</a>';
}
echo '<ul id="converters" style="margin-top: -13px"></ul>';

include 'converter-options/cwebp.php';
include 'converter-options/gd.php';
include 'converter-options/imagick.php';
include 'converter-options/ewww.php';
include 'converter-options/wpc.php';
include 'converter-options/imagickbinary.php';
?>
</td></tr>
<?php

//        echo '<tr><td colspan=2><p>Converted jpeg images will get same quality as original, but not more than this setting. Something between 70-85 is recommended for most websites.</p></td></tr>';

// method
//echo '<p>When higher values are used, the encoder will spend more time inspecting additional encoding possibilities and decide on the quality gain. Supported by cwebp, wpc and imagick</p>';

// Cache-Control
// --------------------
//$maxQuality = get_option('webp_express_max_quality');
$cacheControl = $config['cache-control'];
$cacheControlCustom = $config['cache-control-custom'];

echo '<tr><th scope="row">Caching';
echo helpIcon(
    'Controls the cache-control header for the converted image. ' .
    'This header is only sent when a converted image is successfully delivered (either existing, or new ' .
    'conversion). In case of failure, headers will be sent to prevent caching.');
echo '</th><td>';
echo '<select id="cache_control_select" name="cache-control">';
echo '<option value="no-header"' . ($cacheControl == 'no-header' ? ' selected' : '') . '>Do not set Cache-Control header</option>';
echo '<option value="one-second"' . ($cacheControl == 'one-second' ? ' selected' : '') . '>One second</option>';
echo '<option value="one-minute"' . ($cacheControl == 'one-minute' ? ' selected' : '') . '>One minute</option>';
echo '<option value="one-hour"' . ($cacheControl == 'one-hour' ? ' selected' : '') . '>One hour</option>';
echo '<option value="one-day"' . ($cacheControl == 'one-day' ? ' selected' : '') . '>One day</option>';
echo '<option value="one-week"' . ($cacheControl == 'one-week' ? ' selected' : '') . '>One week</option>';
echo '<option value="one-month"' . ($cacheControl == 'one-month' ? ' selected' : '') . '>One month</option>';
echo '<option value="one-year"' . ($cacheControl == 'one-year' ? ' selected' : '') . '>One year</option>';
echo '<option value="custom"' . ($cacheControl == 'custom' ? ' selected' : '') . '>Custom Cache-Control header</option>';
echo '</select><br>';
echo '<input type="text" id="cache_control_custom" name="cache-control-custom" value="' . $cacheControlCustom . '">';
echo '</td></tr>';


// Metadata
// --------------------
//$maxQuality = get_option('webp_express_max_quality');
$metadata = $config['metadata'];

echo '<tr><th scope="row">Metadata';
echo helpIcon('Decide what to do with image metadata, such as Exif. Note that this setting is not supported by the "Gd" conversion method, as it is not possible to copy the metadata with the Gd extension');
echo '</th><td>';

echo '<select name="metadata">';
echo '<option value="none"' . ($metadata == 'none' ? ' selected' : '') . '>No metadata in webp</option>';
echo '<option value="all"' . ($metadata == 'all' ? ' selected' : '') . '>Copy all metadata to webp</option>';
echo '</select>';
echo '</td></tr>';
//        echo '<tr><td colspan=2><p>Converted jpeg images will get same quality as original, but not more than this setting. Something between 70-85 is recommended for most websites.</p></td></tr>';

// method
//echo '<p>When higher values are used, the encoder will spend more time inspecting additional encoding possibilities and decide on the quality gain. Supported by cwebp, wpc and imagick</p>';

// Response on failure
// --------------------
echo '<tr><th scope="row">Response on failure';
echo helpIcon('Determines what to serve in case the image conversion should fail.');
echo '</th><td>';

//$fail = get_option('webp_express_failure_response');
$fail = $config['fail'];
echo '<select name="fail">';
echo '<option value="original"' . ($fail == 'original' ? ' selected' : '') . '>Original image</option>';
echo '<option value="404"' . ($fail == '404' ? ' selected' : '') . '>404</option>';
echo '<option value="report"' . ($fail == 'report' ? ' selected' : '') . '>Error report (in plain text)</option>';
echo '<option value="report-as-image"' . ($fail == 'report-as-image' ? ' selected' : '') . '>Error report as image</option>';
echo '</select>';
echo '</td></tr>';
//        echo '<tr><td colspan=2>Determines what the converter should serve, in case the image conversion should fail. For production servers, recommended value is "Original image". For development servers, choose anything you like, but that</td></tr>';

//echo '</tbody></table>';


// Web Service
// --------------------

$whitelist = $config['web-service']['whitelist'];
echo '<script>window.whitelist = ' . json_encode($whitelist) . '</script>';
echo '<tr id="share"><th scope="row">Enable web service?';
echo helpIcon('Enabling the web service will allow selected sites to convert webp-images through this site (more options will appear, if you enable)');
echo '</th><td>';

echo '<input type="checkbox" id="web_service_enabled" name="web-service-enabled" value="true" ' . ($config['web-service']['enabled'] ? 'checked="checked"' : '') . '">';
echo "<input type='text' name='whitelist' id='whitelist' value='' style='visibility:hidden; height:0' />";

?>
<div id="whitelist_div"></div>
<!--
<div id="whitelist_listen_popup" class="das-popup">
    <h3>Listening for a request<span class="animated-dots">...</span></h3>
    <div style="font-size:90%">
        Send the instructions below to the one that controls the website that you want to grant access.
        If you control that website, simply open up a new tab and perform the following:
        <ol>
            <li>Log in to the website you want to use the web service</li>
            <li>In WebP Express settings, find the <i>Remote WebP Express</i> conversion method and click <i>configure</i></li>
            <li>Click "Make request"</li>
            <li>Enter this url: <b><?php echo Paths::getWebServiceUrl(); ?></b></li>
        </ol>
        This popup will close once the above is completed<br><br>
    </div>
    <div style="display: inline-block;vertical-align:middle; line-height:27px;">
        <button onclick="whitelistCancelListening()" class="button button-secondary" type="button">
            Give up
        </button>
        or
        <button onclick="whitelistAddManually()" class="button button-secondary" type="button">
            Add manually
        </button>
    </div>
</div>
-->
<div id="whitelist_properties_popup" class="das-popup">
    <h3 class="hide-in-edit">Authorize website</h3>
    <h3 class="hide-in-add">Edit authorized website</h3>
    <input type="hidden" id="whitelist_uid">
    <input type="hidden" id="whitelist_i">
    <div>
        <label for="whitelist_label">
            Label
            <?php echo helpIcon('The label is purely for your own reference'); ?>
        </label>
        <input id="whitelist_label" type="text">
    </div>
    <div>
        <label for="whitelist_ip">
            IP
            <?php echo helpIcon('IP to allow access to service. You can use *, ie "212.91.*", or even "*"'); ?>
        </label>
        <input id="whitelist_ip" type="text">
    </div>
    <div>
        <label for="whitelist_api_key">
            Api key
            <?php echo helpIcon('Who says api keys must be dull-looking meaningless sequences of random ' .
            'characters? Here you get to shape your key to your liking. Enter any phrase you want'); ?>
        </label>
        <input id="whitelist_api_key" type="password" class="hide-in-edit">
        <a href="javascript:whitelistChangeApiKey()" class="hide-in-add" style="line-height:34px">Change api key</a>
    </div>
    <div>
        <label for="whitelist_require_api_key_to_be_crypted_in_transfer">
            Require api-key to be crypted in transfer?
            <?php echo helpIcon('If checked, the web service will only accept crypted api keys. Crypting the api-key protects it from being stolen during transfer. On a few older server setups, clients do not have the capability to crypt'); ?>
        </label>
        <input id="whitelist_require_api_key_to_be_crypted_in_transfer" type="checkbox">
    </div>
    <p style="margin-top: 15px">Psst: The endpoint of the web service is: <b><?php echo Paths::getWebServiceUrl() ?></b></p>

    <button id="whitelist_properties_add_button" onclick="whitelistAddWhitelistEntry()" class="hide-in-edit button button-primary" type="button" style="position:absolute; bottom:20px">
        Add
    </button>
    <button id="whitelist_properties_update_button" onclick="whitelistUpdateWhitelistEntry()" class="hide-in-add button button-primary" type="button" style="position:absolute; bottom:20px">
        Update
    </button>
</div>
<!--
<div id="whitelist_accept_request" class="das-popup">
    <h3>Incoming request!</h3>
    <div id="request_details"></div>
    <button onclick="whitelistAcceptRequest()" class="button button-primary" type="button" style="position:absolute; bottom:20px">Grant access</button>
    <button onclick="whitelistDenyRequest()" class="button button-secondary" type="button" style="position:absolute; bottom:20px;right:20px">Deny</button>
</div>-->

<?php
echo '</td></tr>';


// WPC - whitelist
// --------------------
/*
echo '<tr id="whitelist_row"><th scope="row">Whitelist';

$whitelist = $config['server']['whitelist'];

echo '<script>window.whitelist = ' . json_encode($whitelist) . '</script>';
echo helpIcon('Specify which sites that may use the conversion service.');
echo '</th><td>';
*/
?>
<!--
<div id="whitelist_enter_password_popup" style="display:none">
    <div class="whitelist-popup-content">
        <div>
            <label for="whitelist_password">New password</label>
            <input type="password" id="whitelist_enter_password">
        </div>
        <div>
            <label for="whitelist_hash_password">Scramble password?</label>
            <input type="checkbox" id="whitelist_hash_password">
        </div><br>
        <i>Note: If you choose "scramble password", the password will be scrambled.
            This protects others from discovering what you wrote as password.
            It however still allows people with read access to the file system of your website to get the scrambled
            password and use that to connect with.
        </i>
        <br><br>
        <button onclick="setPassword()" class="button button-primary" type="button">Set password</button>

    </div>
</div>
-->
<?php
/*
echo '<div id="whitelist_div"></div>';
echo "<input type='text' name='whitelist' value='' style='visibility:hidden; height:0' />"; //
//echo gethostbyaddr('212.97.134.33');
//echo gethostbyname('www.rosell.dk');
echo '<div id="password_helptext">' . helpIcon('You may have to leave blank, if the site in question doesnt have the md5() function available.<br><br>' .
    'md5 is needed because the password is not transmitted directly, but used to create a ' .
    'unique hash for the image being converted. So if someone intercepts, they will only get the hash, not the password. And that ' .
    'hash will only work for that specific image.') . '</div>';visibility:
echo '<div id="whitelist_site_helptext">' . helpIcon('Enter IP or domain (ie www.example.com). You may use * as a wildcard.') . '</div>';
//echo '<div id="whitelist_quota_helptext">' . helpIcon('Maximum conversions per hour for this site') . '</div>';

echo '</td></tr>';
*/

 ?>
</tbody></table>

<table>
    <tr>
        <td style="padding-right:20px"><?php submit_button('Save settings', 'primary', 'mysubmit'); ?></td>
        <td><?php submit_button('Save settings and force new .htaccess rules', 'secondary', 'force'); ?></td>
    </tr>
</table>
</form>
</div>
