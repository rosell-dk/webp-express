<tr>
    <th scope="row">
        <?php if ($config['operation-mode'] == 'just-convert'): ?>
            Auto convert <?php echo helpIcon('TODO: write'); ?>
        <?php else: ?>
            Enable redirection to converter?<?php echo helpIcon('This will add rules in the .htaccess that redirects to converter'); ?>
        <?php endif; ?>
    </th>
    <td>
        <input
            type="checkbox"
            id="enable_redirection_to_converter"
            name="enable-redirection-to-converter"
            value="true"
            <?php echo ($config['enable-redirection-to-converter'] ? 'checked="checked"' : '') ?>
        >
    </td>
</tr>
