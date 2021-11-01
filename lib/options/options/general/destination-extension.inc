<tr id="destination_extension_row"><?php

    if ($config['operation-mode'] == 'no-conversion') {
        echo '<th scope="row">Filename of the webp files';
        echo helpIcon(
            '<p>Select under which naming convention the webp files are stored. ' .
            'It is assumed that webp files are located in the same folder as the originals.</p>' .
            '<style>' .
            '#plugin-conventions th {font-style:italic;}' .
            '#plugin-conventions td, #plugin-conventions th {padding:0;}' .
            '#plugin-conventions td:last-child, #plugin-conventions th:last-child {padding-left:10px;}' .
            '</style>' .
            '<table id="plugin-conventions"><tbody>' .
            '<tr><th>Plugin</td><th>Convention</th></tr>' .
            //'<tr><td><a target="blank" href="https://wordpress.org/plugins/cache-enabler/">Cache enabler</a></td><td>Replaces extension</td></tr>' .
            '<tr><td><a target="_blank" href="https://wordpress.org/plugins/shortpixel-image-optimiser/">Shortpixel</a></td><td>Replaces extension</td></tr>' .
            '<tr><td><a target="_blank" href="https://wordpress.org/plugins/ewww-image-optimizer/">Ewww</a></td><td>Appends extension</td></tr>' .
            '<tr><td><a target="_blank" href="https://optimus.io/en/">Optimus HQ</a></td><td>Replaces extension</td></tr>' .
            // todo:
            '</tbody></table>'
        );
    } else {
        echo '<th scope="row">File extension';
        echo helpIcon(
            '<p>Controls the filename of the converted file.</p>' .
            '<p>The "Append" option result in file names such as "image.png.webp". ' .
            'The "Set" option results in file names such as "image.webp". ' .
            'Note that if you choose "Set", it will be a problem if you ie both have a logo.jpg and a logo.png in the same folder. ' .
            'If you are using WebP Express together with <a target="blank" href="https://wordpress.org/plugins/cache-enabler/">Cache enabler</a> ' .
            'or <a target="_blank" href="https://wordpress.org/plugins/shortpixel-image-optimiser/">Shortpixel</a>, set this option to Set"</p>' .
            (($config['operation-mode'] == 'cdn-friendly') ? '<p>In this mode, the webp files will be stored in the same folder as the originals, except for images that are not inside the uploads folder (these are stored in wp-content/webp-express/webp-images/doc-root).</p>' : '') .
            '<p>Changing this option will cause existing webp images to be renamed (only those in the upload folder, and only those that has a ' .
            'corresponding source image)</p>' .
            '<p>Note that this option only applies to the webp images stored in the uploads folder (mingled).</p>'
        );
    }
    ?>
    </th>
    <td>
        <?php
        if ($config['operation-mode'] == 'no-conversion') {
            webpexpress_radioButtons('destination-extension', $config['destination-extension'], [
                'append' => 'Appended ".webp" (ie "image.jpg.webp")',
                'set' => 'Replaced extension (ie "image.webp")',
            ], [
                'append' => 'Original extension is kept and ".webp" is appended. ',
                'set' => 'Original extension is replaced with ".webp".'
            ], 'margin-left: 0px; margin-top: 5px');

        } else {
            echo '<select name="destination-extension" id="destination_extension">';
            webpexpress_selectBoxOptions($config['destination-extension'], [
                'append' => 'Append ".webp"',
                'set' => 'Set to ".webp"',
            ]);
            echo '</select>';
        }
        ?>

    </td>
</tr>
