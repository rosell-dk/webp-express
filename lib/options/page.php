<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use \WebPExpress\Config;
use \WebPExpress\ConvertersHelper;
use \WebPExpress\FileHelper;
use \WebPExpress\HTAccess;
use \WebPExpress\Messenger;
use \WebPExpress\Multisite;
use \WebPExpress\Paths;
use \WebPExpress\PlatformInfo;
use \WebPExpress\State;
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

/*
Removed (#243)
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
*/

$canDetectQuality = TestRun::isLocalQualityDetectionWorking();
$testResult = TestRun::getConverterStatus();
$config = Config::getConfigForOptionsPage();

State::setState('workingConverterIds', ConvertersHelper::getWorkingConverterIds($config));
State::setState('workingAndActiveConverterIds', ConvertersHelper::getWorkingAndActiveConverterIds($config));


//State::setState('last-ewww-optimize-attempt', 0);
//State::setState('last-ewww-optimize', 0);
\WebPExpress\KeepEwwwSubscriptionAlive::keepAliveIfItIsTime($config);

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
$webpexpressSaveSettingsNonce = wp_create_nonce('webpexpress-save-settings-nonce');
?>

<?php
//echo get_theme_root_uri();

//include_once __DIR__ . '/../classes/AlterHtmlHelper.php';
//$actionUrl = Multisite::isNetworkActivated() ? network_admin_url( 'admin-post.php' ) : admin_url( 'admin-post.php' );
$actionUrl = admin_url('admin-post.php');

echo '<form id="webpexpress_settings" action="' . esc_url($actionUrl) . '" method="post" >';
?>
    <input type="hidden" name="action" value="webpexpress_settings_submit">
    <input type="hidden" name="_wpnonce" value="<?php echo $webpexpressSaveSettingsNonce ?>" />

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
                if (strlen($text) > 1000) {
                    $className = 'widest';
                } else {
                    $className = 'even-wider';
                }
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
        echo '<option value="' . esc_attr($optionValue) . '"' . ($optionValue == $selected ? ' selected' : '') . '>';
        echo esc_html($text);
        echo '</option>';
    }
}

function webpexpress_radioButton($optionName, $optionValue, $label, $selectedValue, $helpText = null) {
    $id = esc_attr(str_replace('-', '_', $optionName . '_' . $optionValue));
    echo '<input type="radio" id="' . $id . '"';
    if ($optionValue == $selectedValue) {
        echo ' checked="checked"';
    }
    echo ' name="' . esc_attr($optionName) . '" value="' . esc_attr($optionValue) . '" style="margin-right: 10px">';
    echo '<label for="' . $id . '">';
    echo $label;
    if (!is_null($helpText)) {
        echo helpIcon($helpText);
    }
    echo '</label>';
}

function webpexpress_radioButtons($optionName, $selected, $options, $helpTexts = [], $style='margin-left: 20px; margin-top: 5px') {
    echo '<ul style="' . $style . '">';
    foreach ($options as $optionValue => $label) {
        echo '<li>';
        webpexpress_radioButton($optionName, $optionValue, $label, $selected, isset($helpTexts[$optionValue]) ? $helpTexts[$optionValue] : null);
        echo '</li>';
    }
    echo '</ul>';
}

function webpexpress_checkbox($optionName, $checked, $label, $helpText = '') {
    $id = esc_attr(str_replace('-', '_', $optionName));
    echo '<div style="margin:10px 0 0 10px;">';
    echo '<input value="true" type="checkbox" style="margin-right: 10px" ';
    echo 'name="' . esc_attr($optionName) . '"';
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
include_once 'options/general/general.inc';


/*
idea:

$options = [
    'tweaked' => [
        'general' => [
            'image-types',
            'destination-folder',
            'destination-extension',
            'cache-control'
        ]
    ],
    ...
];
*/


if ($config['operation-mode'] != 'tweaked') {
//    echo '<fieldset class="block">';
//    echo '<table class="form-table"><tbody>';
}

if ($config['operation-mode'] == 'no-conversion') {

    // General
    /*
    echo '<tr><th colspan=2>';
    echo '<h2>General</h2>';
    echo '</th></tr>';
    include_once 'options/conversion-options/destination-extension.inc';
    include_once 'options/general/image-types.inc';
    */

    include_once 'options/redirection-rules/redirection-rules.inc';
    include_once 'options/alter-html/alter-html.inc';
} else {
    include_once 'options/redirection-rules/redirection-rules.inc';
    include_once 'options/conversion-options/conversion-options.inc';
    //include_once 'options/conversion-options/destination-extension.inc';
    include_once 'options/serve-options/serve-options.inc';

    include_once 'options/alter-html/alter-html.inc';

/*
    if ($config['operation-mode'] == 'cdn-friendly') {
        include_once 'options/redirection-rules/enable-redirection-to-webp-realizer.inc';

        // ps: we call it "auto convert", when in this mode
        include_once 'options/redirection-rules/enable-redirection-to-converter.inc';
    }

    if ($config['operation-mode'] == 'varied-image-responses') {
        include_once 'options/redirection-rules/enable-redirection-to-webp-realizer.inc';
    }
    */

    include_once 'options/web-service-options/web-service-options.inc';
}

if ($config['operation-mode'] != 'tweaked') {
//    echo '</tbody></table>';
//    echo '</fieldset>';
}

?>
</form>
</div>
