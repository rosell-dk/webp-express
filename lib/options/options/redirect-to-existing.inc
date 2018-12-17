<?php
// Redirect to existing with .htaccess
// --------------------------------
echo '<tr><th scope="row"><span style="color:darkorange">Beta:</span> Redirect directly to converted image when available';
echo helpIcon('This will add rules in the .htaccess that redirects directly to existing converted files. If you do not activate this setting, it will be the PHP script that handles the redirection to existing webp files. Best performance is achieved by redirecting in .htaccess, however the feature is new and has not been tested widely. Please try it out, and report any problems in the forum, thanks :) Note that with this setting activated, modifying the source image will not lead to regeneration of the webp image.');
echo '</th><td>';
echo '<input type="checkbox" id="redirect_to_existing_in_htaccess" name="redirect-to-existing-in-htaccess" value="true" ' . ($config['redirect-to-existing-in-htaccess'] ? 'checked="checked"' : '') . '">';
echo '</td></tr>';
