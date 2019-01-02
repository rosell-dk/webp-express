<?php
// Image types
// ------------
echo '<tr id="destination_extension_row"><th scope="row">File extension';
echo helpIcon('<p>Controls the filename of the converted file.</p><p>The "Append" option result in file names such as "image.png.webp". The "Set" option results in file names such as "image.webp". Note that if you choose "Set", it will be a problem if you ie both have a logo.jpg and a logo.png in the same folder. If you are using WebP Express together with <a target="blank" href="https://da.wordpress.org/plugins/cache-enabler/">Cache enabler</a> or <a target="_blank" href="https://wordpress.org/plugins/shortpixel-image-optimiser/">Shortpixel</a>, set this option to Set"</p><p>Changing this option will cause existing webp images to be renamed (only those in the upload folder, and only those that has a corresponding source image)</p>');
echo '</th><td>';

$destinationExtension = $config['destination-extension'];

echo '<select name="destination-extension">';
echo '<option value="append"' . ($destinationExtension == 'append' ? ' selected' : '') . '>Append ".webp"</option>';
echo '<option value="set"' . ($destinationExtension == 'set' ? ' selected' : '') . '>Set to ".webp"</option>';
echo '</select>';

echo '</td></tr>';
