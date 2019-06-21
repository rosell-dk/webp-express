<?php
/*

jpg
    Encoding:  Always lossy / Auto
    Quality for lossy:  Auto / specific
    Near-Lossless quality (0-100)  (default: 60)

png
    Encoding:  Always lossless / Auto
    Quality for lossy (0-100)
    Near-Lossless quality (0-100)  (default: 70)
    Alhpa quality (0-100)

*/
// Quality - jpeg
// --------------------

//$canDetectQuality = false;
echo '<tr><th scope="row">Quality (jpeg -> webp)';
if ($canDetectQuality) {
    echo helpIcon(
        'Quality of webp, when the image being converted is a jpeg. If "Same as the jpeg" is selected, the converted image ' .
        'will get same quality as source. Auto is recommended!'
    );
} else {
    echo helpIcon(
        '<p>Quality of webp, when converting jpegs (0-100)</p>' .
        '<p>Note: If your system had the capability to detect the quality ' .
        'of jpeg images, you would have had the option to choose "Same as jpeg". To get that option, you ' .
        'either need to get the imagick extension running (PECL >= 2.2.2) or imagick or gmagick installed plus ' .
        'exec() rights.</p>' .
        '<p>Note: If you use the <i>Remote WebP Express</i> converter, you can configure it to ask the remote ' .
        'to do the automatic quality detection for jpegs. This will override the value entered here.</p>'
    );
}
echo '</th><td>';

if ($canDetectQuality) {
    $qualityAuto = $config['quality-auto'];;
    echo '<select id="quality_auto_select" name="quality-auto" style="margin-right: 15px;">';
    echo '<option value="auto_on"' . ($qualityAuto ? ' selected' : '') . '>Same as the jpeg</option>';
    echo '<option value="auto_off"' . (!$qualityAuto ? ' selected' : '') . '>Fixed quality</option>';
    echo '</select>';

    // Max quality
    // --------------------
    $maxQuality = $config['max-quality'];

//    echo '<tr id="max_quality_div"><th scope="row">Max quality (0-100)';
    echo '<div id="max_quality_div">Max ';
    //echo '</th><td>';
    echo '<input type="text" size=3 id="max_quality" name="max-quality" value="' . esc_attr($maxQuality) . '">';
    echo helpIcon('Quality is expensive byte-wise. For most websites, more than 80 is a waste of bytes. ' .
        'This option allows you to limit the quality to whatever is lowest: ' .
        'the quality of the jpeg or the limit entered here. Recommended value: Somewhere between 50-85');
    //echo '</td></tr>';
    echo '</div>';
}

// Quality - specific
// --------------------
$qualitySpecific = $config['quality-specific'];

echo '<div id="quality_specific_div">';
/*
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
*/
//echo '</th><td>';

echo '<input type="text" size=3 id="quality_specific" name="quality-specific" value="' . esc_attr($qualitySpecific) . '">';
echo helpIcon('Enter number (0 - 100)');
echo '</div>';
echo '</td></tr>';


// Quality - PNG
// --------------------
echo '<tr id="quality_png"><th scope="row">Quality (png -> webp)';
echo helpIcon(
    'Quality of webp, when the image that is converted is a png.'
);
echo '</th><td>';
echo '<input type="text" size=3 id="quality_png" name="quality-png" value="' . esc_attr($config['quality-png']) . '">';
echo helpIcon('Enter number (0 - 100). Recommended value: Somewhere between 60-90');
echo '</td></tr>';
