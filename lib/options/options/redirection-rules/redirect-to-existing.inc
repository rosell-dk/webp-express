<tr>
    <th scope="row">
        <span style="color:darkorange">Beta:</span> Redirect directly to converted image when available<?php echo helpIcon('This will add rules in the .htaccess that redirects directly to existing converted files. If you do not activate this setting, it will be the PHP script that handles the redirection to existing webp files. Best performance is achieved by redirecting in .htaccess, however the feature is new and has not been tested widely. Please try it out, and report any problems in the forum, thanks :)'); ?>
    </th>
    <td>
        <input type="checkbox" id="redirect_to_existing_in_htaccess" name="redirect-to-existing-in-htaccess" value="true" <?php echo ($config['redirect-to-existing-in-htaccess'] ? 'checked="checked"' : '') ?> >
    </td>
</tr>
