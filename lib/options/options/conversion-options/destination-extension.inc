<?php
// Image types
// ------------
echo '<tr id="destination_extension_row"><th scope="row">Destination extension';
echo helpIcon('<p>Controls the filename of the converted file.</p><p>The "Append" option result in file names such as "image.png.webp". The "Set" option results in file names such as "image.webp".</p><p>Note that if you choose "Set", it will be a problem if you ie both have a logo.jpg and a logo.png in the same folder.</p><p>If you are using WebP Express together with <a target="blank" href="https://da.wordpress.org/plugins/cache-enabler/">Cache enabler</a>, you must select "Set"</p>');
echo '</th><td>';

$destinationExtension = $config['destination-extension'];

echo '<select name="destination-extension">';
echo '<option value="append"' . ($destinationExtension == 'append' ? ' selected' : '') . '>Append ".webp"</option>';
echo '<option value="set"' . ($destinationExtension == 'set' ? ' selected' : '') . '>Set to ".webp"</option>';
echo '</select>';

echo '</td></tr>';
