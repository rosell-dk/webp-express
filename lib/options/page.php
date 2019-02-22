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

include_once __DIR__ . '/../classes/Multisite.php';
use \WebPExpress\Multisite;

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
    <h2>WebP Express Settings<?php echo Multisite::isNetworkActivated() ? ' (network)' : ''; ?></h2>

<?php

function webpexpress_converterName($converterId) {
    if ($converterId == 'wpc') {
        return 'Remote WebP Express';
    }
    return $converterId;
}

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

$canDetectQuality = TestRun::isLocalQualityDetectionWorking();
$testResult = TestRun::getConverterStatus();
$config = Config::getConfigForOptionsPage();

if (!$testResult) {
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


//echo '<pre>' . print_r($config['converters'], true) . '</pre>';

//echo 'Working converters:' . print_r($workingConverters, true) . '<br>';
// Generate a custom nonce value.
$webpexpress_settings_nonce = wp_create_nonce('webpexpress_settings_nonce');
?>

<?php
//echo get_theme_root_uri();

//include_once __DIR__ . '/../classes/AlterHtmlHelper.php';
//$actionUrl = Multisite::isNetworkActivated() ? network_admin_url( 'admin-post.php' ) : admin_url( 'admin-post.php' );
$actionUrl = admin_url('admin-post.php');

echo '<form id="webpexpress_settings" action="' . esc_url($actionUrl) . '" method="post" >';
?>
    <input type="hidden" name="action" value="webpexpress_settings_submit">
    <input type="hidden" name="webpexpress_settings_nonce" value="<?php echo $webpexpress_settings_nonce ?>" />

    <fieldset class="block buttons">
        <table>
            <tr>
                <td style="padding-right:20px"><?php submit_button('Save settings', 'primary', 'mysubmit'); ?></td>
                <td><?php submit_button('Save settings and force new .htaccess rules', 'secondary', 'force'); ?></td>
            </tr>
        </table>
    </fieldset>
<?php
function helpIcon($text, $customClass = '') {
    $className = '';
    if (strlen($text) < 80) {
        $className = 'narrow';
    }
    if (strlen($text) > 150) {
        if (strlen($text) > 300) {
            if (strlen($text) > 500) {
                $className = 'widest';
            } else {
                $className = 'wider';
            }
        } else {
            $className = 'wide';
        }
    }
    return '<div class="help ' . $customClass . '">?<div class="popup ' . $className . '">' . $text . '</div></div>';
}

function webpexpress_selectBoxOptions($selected, $options) {
    foreach ($options as $optionValue => $text) {
        echo '<option value="' . $optionValue . '"' . ($optionValue == $selected ? ' selected' : '') . '>';
        echo $text;
        echo '</option>';
    }
}

function webpexpress_radioButtons($optionName, $selected, $options, $helpTexts = [], $style='margin-left: 20px; margin-top: 5px') {
    echo '<ul style="' . $style . '">';
    foreach ($options as $optionValue => $text) {
        $id = str_replace('-', '_', $optionName . '_' . $optionValue);
        echo '<li>';
        echo '<input type="radio" id="' . $id . '"';
        if ($optionValue == $selected) {
            echo ' checked="checked"';
        }
        echo ' name="' . $optionName . '" value="' . $optionValue . '" style="margin-right: 10px">';
        echo '<label for="' . $id . '">';
        echo $text;
        if (isset($helpTexts[$optionValue])) {
            echo helpIcon($helpTexts[$optionValue]);
        }
        echo '</label>';
        echo '</li>';
    }
    echo '</ul>';
}

function webpexpress_checkbox($optionName, $checked, $label, $helpText = '') {
    $id = str_replace('-', '_', $optionName);
    echo '<div style="margin:10px 0 0 10px;">';
    echo '<input value="true" type="checkbox" style="margin-right: 10px" ';
    echo 'name="' . $optionName . '"';
    echo 'id="' . $id . '"';
    if ($checked) {
        echo ' checked="checked"';
    }
    echo '>';
    echo '<label for="' . $id . '">';
    echo $label . '</label>';
    if ($helpText != '') {
        echo helpIcon($helpText);
    }
    echo '</div>';

}

include_once 'options/operation-mode.inc';

if ($config['operation-mode'] != 'tweaked') {
    echo '<fieldset class="block">';
    echo '<table class="form-table"><tbody>';
}

if ($config['operation-mode'] == 'no-conversion') {

    // General
    echo '<tr><th colspan=2>';
    echo '<h2>General</h2>';
    echo '</th></tr>';
    include_once 'options/conversion-options/destination-extension.inc';
    include_once 'options/redirection-rules/image-types.inc';

    // Redirection
    echo '<tr><th colspan=2 class="header-section">';
    echo '<h2>Redirecting jpeg/png to webp (varied image response)</h2>';
    echo '<p>Enabling this adds rules to the <i>.htaccess</i> that internally redirects jpg/pngs to webp ';
    echo 'and sets the <i>Vary:Accept</i> header. ';
    echo '<i>Beware that special attention is needed if you are using a CDN.</i></p>';
    echo '</th></tr>';
    include_once 'options/redirection-rules/redirection-rules.inc';
    include_once 'options/serve-options/cache-control.inc';

    // Html altering
    echo '<tr><th colspan=2 class="header-section">';
    echo '<h2>Html altering</h2>';
    echo '<p>Enabling this alters the HTML code such that webp images are served to browsers that supports webp. ';
    echo 'There are two distinct methods. Using picture tags works great with page caching but only replaces ';
    echo 'images found in &lt;img&gt; tags. Replacing image URLs works on more images ';
    echo '&ndash;including inline styles and lazy load attributes set ';
    echo 'on &lt;div&gt; or &lt;li&gt; tags &ndash; but page caching can only be achieved by also using the Cache Enabler ';
    echo 'plugin (Cache Enabler works without WebP Express, but the HTML altering have ';
    echo '<a target="_blank" href="https://regexr.com/46isf">problems and limitations</a>, so ';
    echo 'I recommend using WebP Express for the altering of image URLs and Cache Enabler for the page caching)';
    echo '</p>';
    echo '</th></tr>';

    include_once 'options/alter-html/alter-html.inc';
} else {
    include_once 'options/redirection-rules/redirection-rules.inc';
    include_once 'options/conversion-options/conversion-options.inc';
    include_once 'options/conversion-options/destination-extension.inc';
    include_once 'options/serve-options/serve-options.inc';
    include_once 'options/alter-html/alter-html.inc';

    if ($config['operation-mode'] == 'no-varied-responses') {
        include_once 'options/redirection-rules/enable-redirection-to-webp-realizer.inc';

        // ps: we call it "auto convert", when in this mode
        include_once 'options/redirection-rules/enable-redirection-to-converter.inc';
    }

    if ($config['operation-mode'] == 'varied-responses') {
        include_once 'options/redirection-rules/enable-redirection-to-webp-realizer.inc';
    }

    include_once 'options/web-service-options/web-service-options.inc';
}

if ($config['operation-mode'] != 'tweaked') {
    echo '</tbody></table>';
    echo '</fieldset>';
}

?>
</form>
</div>
