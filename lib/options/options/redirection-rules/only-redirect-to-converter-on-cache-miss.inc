<tr>
    <th scope="row">
        Only redirect to converter if no webp is found in cache <?php echo helpIcon('This will add a condition to the RewriteRule that redirects to the converter. Useful if you use WebP Express together with the Cache Enabler plugin.'); ?>
    </th>
    <td>
        <input type="checkbox" name="only-redirect-to-converter-on-cache-miss" value="true" <?php echo ($config['only-redirect-to-converter-on-cache-miss'] ? 'checked="checked"' : '') ?> >
    </td>
</tr>
