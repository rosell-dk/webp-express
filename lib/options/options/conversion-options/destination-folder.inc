
<tr>
    <th scope="row">Destination folder<?php
        echo helpIcon('<p>' .
            '<span style="text-decoration:underline">Mingled:</span><br>' .
            'When "Mingled" is selected, the webp images will be put in the same folder as the original <i>but only for images in the uploads folder</i>. ' .
            'Other images, such as theme images are stored separately.<br>' .
            'If you are using WebP Express together with <a target="blank" href="https://da.wordpress.org/plugins/cache-enabler/">Cache enabler</a> or ' .
            '<a target="_blank" href="https://wordpress.org/plugins/shortpixel-image-optimiser/">Shortpixel</a>, choose this option<br><br>' .

            '<span style="text-decoration:underline">In separate folder:</span><br>' .
            'Images are stored in a separate folder ' .
            '(wp-content/webp-express/webp-images/doc-root).' .
            '<p><i>Note: Changing this option will cause existing webp images to be moved</i></p>');
    ?></th>
    <td>
        <select name="destination-folder" id="destination_folder">
            <?php
            webpexpress_selectBoxOptions($config['destination-folder'], [
                'separate' => 'In separate folder',
                'mingled' => 'Mingled',
            ]);
            ?>
        </select>
    </td>
</tr>
