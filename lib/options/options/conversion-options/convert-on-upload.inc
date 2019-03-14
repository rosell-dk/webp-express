<tr>
    <th scope="row">
        Convert on upload
        <?php
            echo helpIcon(
                'Convert images at the moment they have been uploaded through the media library. ' .
                'Of course, the "Image types to work on" setting is respected. ' .
                'Technically, we are hooking into the <i>handle_upload</i> filter to trigger conversion of the image ' .
                'and the <i>image_make_intermediate_size</i> filter for the thumbnails.'
            );
        ?>
    </th>
    <td>
        <input type="checkbox" id="convert_on_upload" name="convert-on-upload" value="true" <?php echo ($config['convert-on-upload'] ? 'checked="checked"' : '') ?> >
    </td>
</tr>
