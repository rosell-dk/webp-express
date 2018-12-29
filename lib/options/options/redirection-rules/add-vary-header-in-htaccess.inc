<!--
We do not need this setting currently.
We will rather let this be decided by the success-response option
<tr>
    <th scope="row">
        Add Vary:Accept Header for the images <?php echo helpIcon('The Vary:Accept header tells browsers (and CDNs) that the response depends on the Accept header (which is the header that browsers use to indicate that they accept webp images). Usually you should leave this on. But if you are using the Cache Enabler plugin, check it off. '); ?>
    </th>
    <td>
        <input type="checkbox" id="add_vary_header_in_htaccess" name="add-vary-header-in-htaccess" value="true" <?php echo ($config['add-vary-header-in-htaccess'] ? 'checked="checked"' : '') ?> >
    </td>
</tr>
-->
