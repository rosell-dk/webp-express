<tr>
    <th scope="row">
        <?php if ($config['operation-mode'] == 'just-convert'): ?>
            Auto convert <?php echo helpIcon('TODO: write'); ?>
        <?php else: ?>
            Only redirect to converter if no webp is found in cache <?php echo helpIcon('This will add a condition to the RewriteRule that redirects to the converter. Useful if you use WebP Express together with the Cache Enabler plugin.'); ?>
        <?php endif; ?>
    </th>
    <td>
        <input type="checkbox" name="only-redirect-to-converter-on-cache-miss" value="true" <?php echo ($config['only-redirect-to-converter-on-cache-miss'] ? 'checked="checked"' : '') ?> >
    </td>
</tr>
