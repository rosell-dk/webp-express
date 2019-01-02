<?php
// Image types
// ------------
echo '<tr><th scope="row">Destination folder';
echo helpIcon('<p>If "In separate folder" is selected, the webp images will be saved to a common root (wp-content/webp-express/webp-images/doc-root), mirroring the folder structure. If on the other hand, "Mingled" is selected, the converter will try to save the webp image in the same folder as the original. If that fails (ie for theme or plugin images), the image will be saved in the separate folder. If you are using WebP Express together with <a target="blank" href="https://da.wordpress.org/plugins/cache-enabler/">Cache enabler</a> or <a target="_blank" href="https://wordpress.org/plugins/shortpixel-image-optimiser/">Shortpixel</a>, set this option to "mingled"</p><p>Changing this option will cause existing webp images to be moved (only those in the upload folder, and only those that has a corresponding source image)</p>');
echo '</th><td>';

$destinationFolder = $config['destination-folder'];

echo '<select name="destination-folder" id="destination_folder">';
echo '<option value="separate"' . ($destinationFolder == 'separate' ? ' selected' : '') . '>In separate folder</option>';
echo '<option value="mingled"' . ($destinationFolder == 'mingled' ? ' selected' : '') . '>Mingled</option>';
echo '</select>';

echo '</td></tr>';
