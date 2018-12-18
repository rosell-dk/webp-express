<?php
// Redirect to existing with .htaccess
// --------------------------------
echo '<tr><th scope="row"><span style="color:darkorange">Beta:</span> Redirect directly to converted image when available';
echo helpIcon('This will add rules in the .htaccess that redirects directly to existing converted files. If you do not activate this setting, it will be the PHP script that handles the redirection to existing webp files. Best performance is achieved by redirecting in .htaccess, however the feature is new and has not been tested widely. Please try it out, and report any problems in the forum, thanks :)<br>Beware that there is currently no hooks for regenerating converted images when source image is edited.');
echo '</th><td>';
echo '<input type="checkbox" id="redirect_to_existing_in_htaccess" name="redirect-to-existing-in-htaccess" value="true" ' . ($config['redirect-to-existing-in-htaccess'] ? 'checked="checked"' : '') . '">';
echo '</td></tr>';
