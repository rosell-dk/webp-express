<tr>
    <th scope="row">
        <?php

        if ($config['operation-mode'] == 'no-conversion') {
            echo 'Activate redirection';
            echo helpIcon('This will add rules in the .htaccess that redirects directly to existing converted files (note that it is an internal redirect, which is much faster than a 301 or 302 redirect).');
        } else {
            echo 'Redirect directly to existing converted images';
            echo helpIcon('This will add rules in the .htaccess that redirects directly to existing converted files. ' .
                'If you do not activate this setting, it will be the PHP script that handles the redirection to existing ' .
                'webp files. Best performance is achieved by redirecting in .htaccess');
        }
            ?>
    </th>
    <td>
        <input type="checkbox" id="redirect_to_existing_in_htaccess" name="redirect-to-existing-in-htaccess" value="true" <?php echo ($config['redirect-to-existing-in-htaccess'] ? 'checked="checked"' : '') ?> >

        <?php
        /*if ($config['operation-mode'] == 'no-conversion') {
            // Cache control header
            echo '<div id="cache_control_div"><table style="margin-top: 10px">';
            include_once __DIR__ . '/../serve-options/cache-control.inc';
            echo '</table></div>';
        }*/
        ?>
    </td>
</tr>
