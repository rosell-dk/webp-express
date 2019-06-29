<tr id="only_redirect_to_converter_for_webp_enabled_browsers_row">
    <th scope="row">
    </th>
    <td>
        Only redirect to converter for webp-enabled browsers?<?php echo helpIcon(
            'If checked, a condition is added to the .htaccess, that the <i>Accept</i> header contains "image/webp"'
        ); ?>
        <input
            name="only-redirect-to-converter-for-webp-enabled-browsers"
            id="only_redirect_to_converter_for_webp_enabled_browsers"
            <?php echo ($config['only-redirect-to-converter-for-webp-enabled-browsers'] ? 'checked="checked"' : '') ?>
            value="true"
            type="checkbox"
        >
    </td>
</tr>
