<tr id="only_redirect_to_converter_on_cache_miss_row">
    <th scope="row">
    </th>
    <td>
        Only redirect to converter if no webp exists <?php
            echo helpIcon(
                '<p>This extra condition is not needed if you enabled the ' .
                '<i>Redirect directly to converted image when available</i> option.</p>' .
                '<p>The option was created in order to make it possible to achieve the functionality behind the ' .
                '<i>Redirect requests for non-existing webp-files to converter</i> option found in the ' .
                '"CDN friendly" operation mode.</p>'
            );
        ?>
        <input type="checkbox" name="only-redirect-to-converter-on-cache-miss" value="true" <?php
            echo ($config['only-redirect-to-converter-on-cache-miss'] ? 'checked="checked"' : '')
        ?> >
    </td>
</tr>
