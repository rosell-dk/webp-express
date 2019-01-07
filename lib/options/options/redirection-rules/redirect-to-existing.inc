<tr>
    <th scope="row">
        Redirect directly to converted image when available<?php
        echo helpIcon('This will add rules in the .htaccess that redirects directly to existing converted files. ' .
            'If you do not activate this setting, it will be the PHP script that handles the redirection to existing ' .
            'webp files. Best performance is achieved by redirecting in .htaccess'); ?>
    </th>
    <td>
        <input type="checkbox" id="redirect_to_existing_in_htaccess" name="redirect-to-existing-in-htaccess" value="true" <?php echo ($config['redirect-to-existing-in-htaccess'] ? 'checked="checked"' : '') ?> >
    </td>
</tr>
