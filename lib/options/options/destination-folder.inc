<?php
// Image types
// ------------
echo '<tr><th scope="row">Destination folder';
echo helpIcon('<p>If "In separate folder" is selected, the webp images will be saved to a common root (wp-content/webp-express/webp-images/doc-root), mirroring the folder structure.</p><p>If "Mingled" is selected, the converter will try to save the webp image in the same folder as the original. If that fails (ie for theme or plugin images), the image will be saved in the separate folder</p>');
echo '</th><td>';

$destinationFolder = $config['destination-folder'];

echo '<select name="destination-folder" id="destination_folder">';
echo '<option value="separate"' . ($destinationFolder == 'separate' ? ' selected' : '') . '>In separate folder</option>';
echo '<option value="mingled"' . ($destinationFolder == 'mingled' ? ' selected' : '') . '>Mingled</option>';
echo '</select>';

echo '</td></tr>';
