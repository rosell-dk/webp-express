<?php
// Metadata
// --------------------
$metadata = $config['metadata'];

echo '<tr><th scope="row">Metadata';
echo helpIcon(
    'Decide what to do with image metadata, such as Exif. Note that this setting is not supported by the "Gd" conversion method, ' .
    'as it is not possible to copy the metadata with the Gd extension.'
);
echo '</th><td>';

echo '<select id="metadata" name="metadata">';
echo '<option value="none"' . ($metadata == 'none' ? ' selected' : '') . '>No metadata in webp</option>';
echo '<option value="all"' . ($metadata == 'all' ? ' selected' : '') . '>Copy all metadata to webp</option>';
echo '</select>';
echo '</td></tr>';
//        echo '<tr><td colspan=2><p>Converted jpeg images will get same quality as original, but not more than this setting. Something between 70-85 is recommended for most websites.</p></td></tr>';
