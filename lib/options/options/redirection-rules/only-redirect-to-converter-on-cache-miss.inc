<tr>
    <th scope="row">
        Only redirect to converter if no webp exists <?php
            echo helpIcon(
                '<p>This extra condition is not needed if you enabled the ' .
                '<i>Redirect directly to converted image when available</i> option.</p>' .
                '<p>The option was created in order to make it possible to achieve the functionality behind the ' .
                '<i>Convert non-existing webp-files upon request to original image?</i> option found in the ' .
                '"No varied images responses" operation mode.</p>'
            );
        ?>
    </th>
    <td>
        <input type="checkbox" name="only-redirect-to-converter-on-cache-miss" value="true" <?php echo ($config['only-redirect-to-converter-on-cache-miss'] ? 'checked="checked"' : '') ?> >
    </td>
</tr>
