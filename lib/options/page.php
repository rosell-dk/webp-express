<?php

include_once __DIR__ . '/../classes/Config.php';
use \WebPExpress\Config;

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


if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}
?>
<div class="wrap">
    <h2>WebP Express Settings</h2>

<?php
include __DIR__ . "/page-messages.php";

/*
foreach (Paths::getHTAccessDirs() as $dir) {
    echo $dir . ':' . (Paths::canWriteHTAccessRulesHere($dir) ? 'writable' : 'not writable') . '<br>';
    //Paths::canWriteHTAccessRulesHere($dir);
}*/


$defaultConfig = [
    'cache-control' => 'one-year',
    'cache-control-custom' => 'public, max-age:3600',
    'converters' => [],
    'fail' => 'original',
    'forward-query-string' => true,
    'image-types' => 1,
    'max-quality' => 80,
    'metadata' => 'none',
];

$config = Config::loadConfig();
if (!$config) {
    $config = [];
}

$config = array_merge($defaultConfig, $config);
if ($config['converters'] == null) {
    $config['converters'] = [];
}

// Generate a custom nonce value.
$webpexpress_settings_nonce = wp_create_nonce('webpexpress_settings_nonce');

echo '<form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post" id="webpexpress_settings" >';
?>
    <input type="hidden" name="action" value="webpexpress_settings_submit">
    <input type="hidden" name="webpexpress_settings_nonce" value="<?php echo $webpexpress_settings_nonce ?>" />

<?php

echo '<table class="form-table"><tbody>';

// Image types
// ------------
echo '<tr><th scope="row">Image types to convert</th><td>';

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

// Response on failure
// --------------------
echo '<tr><th scope="row">Response on failure</th><td>';

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

// Cache-Control
// --------------------
//$maxQuality = get_option('webp_express_max_quality');
$cacheControl = $config['cache-control'];
$cacheControlCustom = $config['cache-control-custom'];

echo '<tr><th scope="row">Caching</th><td>';
echo '<select id="cache_control_select" name="cache-control">';
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


// Max quality
// --------------------
//$maxQuality = get_option('webp_express_max_quality');
$maxQuality = $config['max-quality'];

echo '<tr><th scope="row">Max quality (0-100)</th><td>';
echo '<input type="text" size=3 name="max-quality" value="' . $maxQuality . '">';
echo '</td></tr>';
//        echo '<tr><td colspan=2><p>Converted jpeg images will get same quality as original, but not more than this setting. Something between 70-85 is recommended for most websites.</p></td></tr>';

// method
//echo '<p>When higher values are used, the encoder will spend more time inspecting additional encoding possibilities and decide on the quality gain. Supported by cwebp, wpc and imagick</p>';


// Metadata
// --------------------
//$maxQuality = get_option('webp_express_max_quality');
$metadata = $config['metadata'];

echo '<tr><th scope="row">Metadata</th><td>';
echo '<select name="metadata">';
echo '<option value="none"' . ($metadata == 'none' ? ' selected' : '') . '>No metadata in webp</option>';
echo '<option value="all"' . ($metadata == 'all' ? ' selected' : '') . '>Copy all metadata to webp</option>';
echo '</select>';
echo '</td></tr>';
//        echo '<tr><td colspan=2><p>Converted jpeg images will get same quality as original, but not more than this setting. Something between 70-85 is recommended for most websites.</p></td></tr>';

// method
//echo '<p>When higher values are used, the encoder will spend more time inspecting additional encoding possibilities and decide on the quality gain. Supported by cwebp, wpc and imagick</p>';



echo '</tbody></table>';

// Converters
// --------------------

$converters = $config['converters'];
echo '<script>window.converters = ' . json_encode($converters) . '</script>';
echo "<input type='text' name='converters' value='' style='visibility:hidden' />";

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

echo '<h2>Converters</h2>';
$dragIcon = '<svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="17px" height="17px" viewBox="0 0 100.000000 100.000000" preserveAspectRatio="xMidYMid meet"><g transform="translate(0.000000,100.000000) scale(0.100000,-0.100000)" fill="#444444" stroke="none"><path d="M415 920 l-80 -80 165 0 165 0 -80 80 c-44 44 -82 80 -85 80 -3 0 -41 -36 -85 -80z"/><path d="M0 695 l0 -45 500 0 500 0 0 45 0 45 -500 0 -500 0 0 -45z"/><path d="M0 500 l0 -40 500 0 500 0 0 40 0 40 -500 0 -500 0 0 -40z"/><path d="M0 305 l0 -45 500 0 500 0 0 45 0 45 -500 0 -500 0 0 -45z"/><path d="M418 78 l82 -83 82 83 83 82 -165 0 -165 0 83 -82z"/></g></svg>';

echo '<p><i>Drag to reorder. The converter on top will be used. Should it fail, the next will be used, etc</i></p>';
// https://github.com/RubaXa/Sortable

// Empty list of converters. The list will be populated by the javascript
echo '<ul id="converters"></ul>';
?>
<div id="cwebp" style="display:none;">
    <div class="cwebp converter-options">
      <h3>cwebp</h3>
      <div class="info">
          cwebp works by executing the <i>cwebp</i> binary from Google, which is build upon the *libwebp* (also from Google).
          That library is actually the only library in town for generating webp images, which means that the other conversion
          methods ultimately uses that very same library.
          This again means that the results using the different methods are very similar.
          However, with cwebp, we have more parameters to tweak than with the rest.
          We for example have the <i>method</i> option, which controls the trade off between encoding speed and the
          compressed file size and quality.
          Setting this to max, we can squeeze the images a few percent extra - without loosing quality
          (the converter is still pretty fast, so in most cases it is probably worth it).
      </div>
      <h3>cweb options</h3>
      <div>
          <label for="cwebp_use_nice">Use nice</label>
          <input type="checkbox" id="cwebp_use_nice">
          <br>Enabling "use nice" saves system resources at the cost of slightly slower conversion
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
      <br>
      <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update</button>
      <!-- <a href="javascript: tb_remove();">close</a> -->
    </div>
</div>
<div id="gd" style="display:none;">
    <div class="gd converter-options">
      <h3>Gd</h3>
      <p>
        The gd converter uses the Gd extension to do the conversion. It is per default placed below the cloud converters for two reasons.
        Firstly, it does not seem to produce quite as good quality as cwebp.
        Secondly, it provides no conversion options, besides quality.
        The Gd extension is pretty common, so the main feature of this converter is that it <i>may</i> work out of the box.
        This is in contrast to the cloud converters, which requires that the user does some setup.
      </p>
      <h3>Gd options</h3>
      <div class="info">
          Gd neither supports copying metadata nor exposes any WebP options. Lacking the option to set lossless encoding results in poor encoding of PNGs - the filesize is generally much larger than the original. For this reason, the converter defaults to skip PNG's.
      </div>
      <div>
          <label for="gd_skip_pngs">Skip PNGs</label>
          <input type="checkbox" id="gd_skip_pngs">
      </div>
      <br>
      <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update</button>
      <!-- <a href="javascript: tb_remove();">close</a> -->
    </div>
</div>
<div id="imagick" style="display:none;">
    <div class="imagick converter-options">
      <h3>Imagick</h3>
      <p>
        imagick would be your last choice. For some reason it produces conversions that are only marginally better than the originals.
        See <a href="https://github.com/rosell-dk/webp-convert/issues/43" target="_blank">this issue</a>. But it is fast.
      </p>
      <h3>Imagick options</h3>
      <div class="info">
          imagick has no extra options.
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
        <a href="https://ewww.io/" target="_blank">ewww</a> is a cloud service.
        It is a decent alternative for those who don't have the technical know-how to install wpc.
        ewww is using cwebp to do the conversion, so quality is great.
        ewww however only provides one conversion option (quality), and it does not support "auto"
        quality (yet - I have requested the feature and the maintainer are considering it).
        Also, it is not free. But very cheap. Like in almost free.
      </p>
      <h3>Ewww options</h3>
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
      <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update</button>
      <!-- <a href="javascript: tb_remove();">close</a> -->
    </div>
</div>
<div id="wpc" style="display:none;">
    <div class="wpc converter-options">
      <h3>WebPConvert Cloud Service</h3>
      wpc is an open source cloud converter based on <a href="https://github.com/rosell-dk/webp-convert" target="_blank">WebPConvert</a>
      (this plugin also happens to be based on WebPConvert).
      Conversions will of course be slower than cwebp, as images need to go back and forth to the cloud converter.
      As images usually just needs to be converted once, the slower conversion
      speed is probably acceptable. The conversion quality and options of wpc matches cwebp.
      The only catch is that you will need to install the WPC library on a server (or have someone do it for you).
      <a href="https://github.com/rosell-dk/webp-convert-cloud-service" target="blank">Visit WPC on github</a>.
      If this is a problem, we suggest you turn to ewww.
      (PS: A Wordpress plugin is planned, making it easier to set up a WPC instance. Or perhaps the functionality will even be part of this plugin)

      <h3>Options for WebPConvert Cloud Service</h3>
      <div>
          <label for="wpc_url">URL</label>
          <input type="text" id="wpc_url" placeholder="Url to your WPC instance">
      </div>

      <div>
          <label for="wpc_secret">Secret</label>
          <input type="text" id="wpc_secret" placeholder="Secret (must match secret on server side)">
      </div>
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
      <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update</button>
    </div>
</div>

<table>
    <tr>
        <td style="padding-right:20px"><?php submit_button('Save settings'); ?></td>
        <td><?php submit_button('Save settings and force new .htaccess rules', 'secondary', 'force'); ?></td>
    </tr>
</table>
</form>
</div>
