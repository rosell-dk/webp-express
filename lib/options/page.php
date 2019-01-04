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

echo '<form id="webpexpress_settings" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post" >';
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
            $className = 'wider';
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

include_once 'options/operation-mode.inc';

if ($config['operation-mode'] != 'tweaked') {
    echo '<fieldset class="block">';
    echo '<table class="form-table"><tbody>';
}

include_once 'options/redirection-rules/redirection-rules.inc';
if ($config['operation-mode'] != 'just-redirect') {
    include_once 'options/conversion-options/conversion-options.inc';
}
if ($config['operation-mode'] == 'just-redirect') {
    include_once 'options/conversion-options/destination-extension.inc';
}
include_once 'options/serve-options/serve-options.inc';

if ($config['operation-mode'] == 'just-convert') {
    // ps: we call it "auto convert", when in this mode
    include_once 'options/redirection-rules/enable-redirection-to-converter.inc';
}

if ($config['operation-mode'] != 'just-redirect') {
    include_once 'options/web-service-options/web-service-options.inc';
}

if ($config['operation-mode'] != 'tweaked') {
    echo '</tbody></table>';
    echo '</fieldset>';
}

?>
</form>
</div>
