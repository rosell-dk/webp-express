
<tr id="destination_structure_row">
    <th scope="row">Destination structure<?php
        echo helpIcon(
            '<p>This setting determines how the converted files are structured within the folder that WebP Express ' .
            'uses for storing webp images</p>' .
            '<p><span style="text-decoration:underline">"document root"</span><br>' .
            'When "document root" is selected, the webp images will be stored in:<br>' .
            '<span style="white-space:pre-wrap;font-family:monospace">[cache root]/doc-root/[relative path of source image, from document root].</span><br>' .
            '</p>' .
            '<p><span style="text-decoration:underline">"image roots"</span><br>' .
            'A Wordpress site has images stored in different locations. Uploaded files are for example stored in the <i>uploads</i> folder, which is ' .
            'usually - but not always - located in the "wp-content" folder. I call the uploads folder an "image root". Other roots are: ' .
            '"themes", "plugins", "wp-content" and "index". When the "image roots" setting is selected, the webp files ' .
            'are stored in a structure that mirrors the relative path of the source image within its image root. ' .
            'For "uploads", that location is:<br>' .
            '<span style="white-space:pre-wrap;font-family:monospace">[cache root]/uploads/[relative path of source image, from uploads root].</span><br>' .
            'More generally we have:<br>' .
            '<span style="white-space:pre-wrap;font-family:monospace"">[cache root]/[image root]/[relative path of source image, from its image root].</span><br>' .
            '</p>' .
            '<p><span style="text-decoration:underline">Which option is best?</span><br>' .
            'Well, in most cases it does not matter. However, as the "image roots" option is new, I would recommend staying with the ' .
            '"document root" setting. On Nginx, I recommend "document root", as it requires fewer rewrite rules.</p>' .
            '<p>The "image roots" option was added to make WebP Express work on systems where the document root is set up incorrectly or ' .
            'is outside opendir restriction or an image root is located outside document root. WebP Express automatically detects this and will not allow you to select "document root" in those cases.</p>'
        );
    ?></th>
    <td>
        <select name="destination-structure" id="destination_structure">
            <?php
            webpexpress_selectBoxOptions($config['destination-structure'], [
                'doc-root' => 'Document root',
                'image-roots' => 'Image roots',
            ]);
            ?>
        </select>
    </td>
</tr>
