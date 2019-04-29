<?php
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
