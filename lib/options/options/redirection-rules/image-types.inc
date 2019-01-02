<?php
// Image types
// ------------
echo '<tr><th scope="row">';
switch ($config['operation-mode']) {
    case 'standard':
        echo 'Image types to work on';
        break;
    case 'just-convert':
        echo 'Image types to convert';
        break;
    case 'just-redirect':
        echo 'Image types to redirect';
        break;
    case 'tweaked':
        echo 'Image types to send to the converter';
        break;
}
echo helpIcon('<p>Beware that the Gd conversion method cannot handle transparency for PNGs. PNG conversions havent been tested much yet. Please report any problems with PNG images <a target="_blank" href="https://github.com/rosell-dk/webp-convert/issues/42">here</a></p><p>The term "send" means an internal redirect on the server (not to be confused with an external redirect, such as a 301 or 302 response)</p>');
echo '</th><td>';

// bitmask
// 1: JPEGs
// 2: PNG's
// Converting only jpegs is thus "1"
// Converting both jpegs and pngs is (1+2) = 3
//$imageTypes = get_option('webp_express_image_types_to_convert');
$imageTypes = $config['image-types'];

echo '<select name="image-types">';
echo '<option value="0"' . ($imageTypes == 0 ? ' selected' : '') . '>None! (disable)</option>';
echo '<option value="1"' . ($imageTypes == 1 ? ' selected' : '') . '>Only jpegs</option>';
echo '<option value="3"' . ($imageTypes == 3 ? ' selected' : '') . '>Both jpegs and pngs</option>';
echo '</select>';

echo '</td></tr>';
