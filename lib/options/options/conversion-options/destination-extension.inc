<tr id="destination_extension_row"><th scope="row">File extension<?php
    echo helpIcon(
        '<p>Controls the filename of the converted file.</p>' .
        '<p>The "Append" option result in file names such as "image.png.webp". ' .
        'The "Set" option results in file names such as "image.webp". ' .
        'Note that if you choose "Set", it will be a problem if you ie both have a logo.jpg and a logo.png in the same folder. ' .
        'If you are using WebP Express together with <a target="blank" href="https://da.wordpress.org/plugins/cache-enabler/">Cache enabler</a> ' .
        'or <a target="_blank" href="https://wordpress.org/plugins/shortpixel-image-optimiser/">Shortpixel</a>, set this option to Set"</p>' .
        (($config['operation-mode'] == 'no-varied-responses') ? '<p>In this mode, the webp files will be stored in the same folder as the originals, except for images that are not inside the uploads folder (these are stored in wp-content/webp-express/webp-images/doc-root).</p>' : '') .
        '<p>Changing this option will cause existing webp images to be renamed (only those in the upload folder, and only those that has a ' .
        'corresponding source image)</p>'
    );?>
    </th>
    <td>
        <select name="destination-extension">
            <?php
            webpexpress_selectBoxOptions($config['destination-extension'], [
                'append' => 'Append ".webp"',
                'set' => 'Set to ".webp"',
            ]);
            ?>
        </select>
    </td>
</tr>
