<tr>
    <th scope="row">
        Convert on upload
        <?php
            echo helpIcon(
                '<p>Convert images at the moment they have been uploaded through the media library ' .
                '(of course, the "Image types to work on" setting is respected). ' .
                'Be aware that this may slow the down the experience of uploading in the media library, ' .
                'especially if your theme creates many thumbnails.</p>' .
                '<p>Technically, we are hooking into the <i>handle_upload</i> filter to trigger conversion of the image ' .
                'and the <i>image_make_intermediate_size</i> filter for the thumbnails.</p>'
            );
        ?>
    </th>
    <td>
        <input type="checkbox" id="convert_on_upload" name="convert-on-upload" value="true" <?php echo ($config['convert-on-upload'] ? 'checked="checked"' : '') ?> >
    </td>
</tr>
