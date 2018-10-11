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

$canDetectQuality = TestRun::isLocalQualityDetectionWorking();

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
    'wpc' => [
        'enabled' => true,
        'whitelist' => [
            [
                'site' => '*',
                'password' => 'my dog is white',
                'quota' => 60
            ]
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
                    'Hurray! - The <i>' . $converterId . '</i> conversion method is working now!'
                );
            } else {
                Messenger::printMessage(
                    'warning',
                    'Sad news. The <i>' . $converterId . '</i> conversion method is not working anymore. What happened?'
                );
            }
        }
        $converter['working'] = $working;
        if ($hasError) {
            $converter['error'] = $testResult['errors'][$converterId];
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
        'For the wpc converter, you will however have the option to use override this, and use ' .
        '"auto". If you install imagick or gmagick, quality can have "auto" for all convertion methods. '
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
echo '<ul id="converters" style="margin-top: -13px"></ul>';
?>
<div id="cwebp" style="display:none;">
    <div class="cwebp converter-options">
      <h3>cweb options</h3>
      <div>
          <label for="cwebp_use_nice">Use nice</label>
          <input type="checkbox" id="cwebp_use_nice">
          <br>Enabling this option saves system resources at the cost of slightly slower conversion
      </div>
      <div>
          <label for="cwebp_try_common_system_paths">Try to execute cweb binary at common locations</label>
          <input type="checkbox" id="cwebp_try_common_system_paths">
          <br>If checked, we will look for binaries in common locations, such as <i>/usr/bin/cwebp</i>
      </div>
      <div>
          <label for="cwebp_try_common_system_paths">Try precompiled cwebp</label>
          <input type="checkbox" id="cwebp_try_supplied_binary">
          <br>This plugin ships with precompiled cweb binaries for different platforms. If checked, and we have a precompiled binary for your OS, we will try to exectute it
      </div>
      <div>
          <label for="cwebp_method">Method (0-6)</label>
          <input type="text" size="2" id="cwebp_method">
          <br>This parameter controls the trade off between encoding speed and the compressed file size and quality.
          Possible values range from 0 to 6. 0 is fastest. 6 results in best quality.
      </div>
      <div>
          <label for="cwebp_set_size">Set size option (and ignore quality option)</label>
          <input type="checkbox" id="cwebp_set_size">
          <br>This option activates the size option below.
          <?php
          if ($canDetectQuality) {
              echo 'As you have quality detection working on your server, it is probably best to use that, rather ';
              echo 'than the "size" option. Using the size option takes more ressources (it takes about 2.5 times ';
              echo 'longer for cwebp to do a a conversion with the size option than the quality option). Long ';
              echo 'story short, you should probably <i>not</i> activate the size option.';
          } else {
              echo 'As you do not have quality detection working on your server, it is probably a good ';
              echo 'idea to use the size option to avoid making conversions with a higher quality setting ';
              echo 'than the source image. ';
              echo 'Beware, though, that cwebp takes about 2.5 times longer to do a a conversion with the size option set.';
          }
          ?>
      </div>
      <div>
          <label for="cwebp_size_in_percentage">Size (in percentage of source)</label>
          <input type="text" size="2" id="cwebp_size_in_percentage">
          <br>Set the cwebp should aim for, in percentage of the original.
          Usually cwebp can reduce to ~45% of original without loosing quality.
      </div>
      <div>
          <label for="cwebp_command_line_options">Extra command line options</label><br>
          <input type="text" size="40" id="cwebp_command_line_options" style="width:100%">
          <br>This allows you to set any parameter available for cwebp in the same way as
          you would do when executing <i>cwebp</i>. As a syntax example, you could ie. set it to
          "-low_memory -af -f 50 -sharpness 0 -mt -crop 10 10 40 40" (do not include the quotes).
          Read more about all the available parameters in
          <a target="_blank" href="https://developers.google.com/speed/webp/docs/cwebp">the docs</a>
      </div>
      <br>
      <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update and save settings</button>
      <!-- <a href="javascript: tb_remove();">close</a> -->
    </div>
</div>
<div id="gd" style="display:none;">
    <div class="gd converter-options">
      <h3>Gd options</h3>
      <div>
          <label for="gd_skip_pngs">Skip PNGs</label>
          <input type="checkbox" id="gd_skip_pngs">
          <br>Gd is not suited for converting PNGs into webp. &ndash;
          The filesize is generally much larger than the original.
          For this reason, the converter defaults to skip PNG's.
      </div>
      <br>
      <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update and save settings</button>
      <!-- <a href="javascript: tb_remove();">close</a> -->
    </div>
</div>
<div id="imagick" style="display:none;">
    <div class="imagick converter-options">
      <h3>Imagick options</h3>
      <div class="info">
          imagick has no special options.
      </div>
      <br>
      <!--
      <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update</button>
  -->
      <!-- <a href="javascript: tb_remove();">close</a> -->
    </div>
</div>
<div id="ewww" style="display:none;">
    <div class="ewww converter-options">
      <h3>Ewww</h3>
      <p>
        ewww is a cloud service for converting images.
        To use it, you need to purchase a key <a target="_blank" href="https://ewww.io/plans/">here</a>.
        They do not charge credits for webp conversions, so all you ever have to pay is the one dollar start-up fee :)
      </p>
      <h3>Options</h3>
      <div>
          <label for="ewww_key">Key</label>
          <input type="text" id="ewww_key" placeholder="Your API key here">
      </div>
      <br>
      <h4>Fallback (optional)</h4>
      <div>
          <label for="ewww_key_2">key</label>
          <input type="text" id="ewww_key_2" placeholder="In case the first one expires...">
      </div>
      <br>
      <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update and save settings</button>
      <!-- <a href="javascript: tb_remove();">close</a> -->
    </div>
</div>
<div id="wpc" style="display:none;">
    <div class="wpc converter-options">
      <h3>WebPConvert Cloud Service (WPC)</h3>
      wpc is an open source cloud converter based on <a href="https://github.com/rosell-dk/webp-convert" target="_blank">WebPConvert</a>.
      You will need to install the WPC library on a server (or have someone do it for you).
      <a href="https://github.com/rosell-dk/webp-convert-cloud-service" target="blank">Visit WPC on github</a>.
      (PS: It is planned to integrate wpc into <i>WebP Express</i>, making it very easy to share the capability to convert with your other sites)
      <h3>Options</h3>
      <div>
          <label for="wpc_url">URL</label>
          <input type="text" id="wpc_url" placeholder="Url to your WPC instance">
      </div>

      <div>
          <label for="wpc_secret">Secret</label>
          <input type="text" id="wpc_secret" placeholder="Secret (must match secret on server side)">
      </div>
      <?php
      if ($canDetectQuality) { ?>
          <div>
              <label for="wpc_quality">
                  Quality
                  <?php echo helpIcon('If "Auto" is selected, the converted image will get same quality as source. Auto is recommended!'); ?>
              </label>
              <!--
              Your server cannot detect quality of jpeg files. But you can have the cloud server do it for you
              (provided that <i>it</i> can) -->
              <select id="wpc_quality" onchange="wpcQualityChanged()">
                  <option value="not_set">Use global settings</option>
                  <option value="auto">Auto</option>
              </select>
          </div>
          <div id="wpc_max_quality_div">
              <label>
                  Max quality
                  <?php echo helpIcon('Enter number (0-100). Converted images will be encoded with same quality as the source image, but not more than this setting'); ?>
              </label>
              <input type="text" size=3 id="wpc_max_quality">
          </div>
    <?php } ?>
    <br>
      <h4>Fallback (optional)</h4>
      <p>In case the first is down, the fallback will be used.</p>
      <div>
          <label for="wpc_url_2">URL</label>
          <input type="text" id="wpc_url_2" placeholder="Url to your other WPC instance">
      </div>
      <div>
          <label for="wpc_secret_2">Secret</label>
          <input type="text" id="wpc_secret_2" placeholder="Secret (must match secret on server side)">
      </div>
      <br>
      <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update and save settings</button>
    </div>
</div>
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


// WPC - enabled
// --------------------

echo '<tr id="share"><th scope="row">Enable conversion service?';
echo helpIcon('Allow other sites to convert webp-images through this site?');
echo '</th><td>';

echo '<input type="checkbox" id="wpc_enabled" name="wpc-enabled" value="' . $config['wpc']['enabled'] . '">';
echo '</td></tr>';

// WPC - url
// --------------------

echo '<tr><th scope="row">Url';
echo helpIcon('The sites that wants to use your conversion service needs this URL. You cannot modify it.');
echo '</th><td>';

echo '<i>' . Paths::getWpcUrl() . '</i>';
echo '</td></tr>';

// WPC - secret
// --------------------
/*
echo '<tr><th scope="row">Password';
echo helpIcon('The password is not transmitted directly, but used to create a ' .
    'unique hash for the image being converted. So if someone intercepts, they will only get the hash, not the password. And that ' .
    'hash will only work for that specific image.');
echo '</th><td>';

echo '<input type="text" id="wpc_secret" name="wpc-secret" value="' . $config['wpc']['secret'] . '">';
echo '</td></tr>';
*/
// WPC - whitelist
// --------------------

echo '<tr><th scope="row">Whitelist';

$whitelist = $config['wpc']['whitelist'];
echo '<script>window.whitelist = ' . json_encode($whitelist) . '</script>';
echo helpIcon('Specify which sites that may use the conversion service.');
echo '</th><td>';
echo '<div id="whitelist_div"></div>';
echo "<input type='text' name='whitelist' value='' style='visibility:hidden; height:0' />"; //
//echo gethostbyaddr('212.97.134.33');
//echo gethostbyname('www.rosell.dk');
echo '<div id="password_helptext">' . helpIcon('You may have to leave blank, if the site in question doesnt have the md5() function available.<br><br>' .
    'md5 is needed because the password is not transmitted directly, but used to create a ' .
    'unique hash for the image being converted. So if someone intercepts, they will only get the hash, not the password. And that ' .
    'hash will only work for that specific image.') . '</div>';
echo '<div id="whitelist_site_helptext">' . helpIcon('Enter IP or domain (ie www.example.com). You may use * as a wildcard.') . '</div>';
echo '<div id="whitelist_quota_helptext">' . helpIcon('Maximum conversions per hour for this site') . '</div>';

echo '</td></tr>';


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
