<?php
// Image types
// ------------
echo '<tr><th scope="row">';
switch ($config['operation-mode']) {
    case 'varied-image-responses':
        echo 'Image types to work on';
        break;
    case 'cdn-friendly':
        echo 'Image types to convert';
        break;
    case 'no-conversion':
        echo 'Image types to work on';
        break;
    case 'tweaked':
        echo 'Image types to send to the converter';
        break;
}
if ($config['operation-mode'] == 'no-conversion') {
    echo helpIcon('<p>Select which types of images you would like to redirect and/or have altered in the HTML</p>');
} else {
    echo helpIcon('<p>Beware that some has reported problems with Gd and PNG, but it has not been pinned down when it happens. It is probably on older versions of Gd. Please report any problems with Gd!</p>');
}
echo '</th><td>';

// bitmask
// 1: JPEGs
// 2: PNG's
// Converting only jpegs is thus "1"
// Converting both jpegs and pngs is (1+2) = 3
$imageTypes = $config['image-types'];

echo '<select name="image-types" id="image_types">';
echo '<option value="0"' . ($imageTypes == 0 ? ' selected' : '') . '>None! (disable)</option>';
echo '<option value="1"' . ($imageTypes == 1 ? ' selected' : '') . '>Only jpegs</option>';
echo '<option value="2"' . ($imageTypes == 2 ? ' selected' : '') . '>Only pngs</option>';
echo '<option value="3"' . ($imageTypes == 3 ? ' selected' : '') . '>Both jpegs and pngs</option>';
echo '</select>';

echo '</td></tr>';
