<tr>
    <th scope="row">
        <?php if ($config['operation-mode'] == 'just-convert'): ?>
            Auto convert <?php echo helpIcon('<p><em>The auto-convertion works this way: When an image is requested, a rule in the .htaccess detects if that image has been converted. If not, the request is redirected to the converter, which creates the webp and returns <em>the original</em> image</em></p>'); ?>
        <?php else: ?>
            Enable redirection to converter?<?php echo helpIcon('This will add rules in the .htaccess that redirects to converter'); ?>
        <?php endif; ?>
    </th>
    <td>
        <input
            id="enable_redirection_to_converter"
            name="enable-redirection-to-converter"
            <?php echo ($config['enable-redirection-to-converter'] ? 'checked="checked"' : '') ?>
            value="true"
            type="checkbox"
        >
    </td>
</tr>
