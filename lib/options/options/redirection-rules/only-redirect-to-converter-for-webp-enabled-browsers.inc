<tr>
    <th scope="row">
        Only redirect to converter for webp-enabled browsers?<?php echo helpIcon('If checked, a condition is added to the .htaccess, that the <i>Accept</i> header contains "image/webp"'); ?>
    </th>
    <td>
        <input
            name="only-redirect-to-converter-for-webp-enabled-browsers"
            <?php echo ($config['only-redirect-to-converter-for-webp-enabled-browsers'] ? 'checked="checked"' : '') ?>
            value="true"
            type="checkbox"
        >
    </td>
</tr>
