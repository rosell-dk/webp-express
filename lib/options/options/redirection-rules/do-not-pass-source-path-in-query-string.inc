<?php
echo '<tr><th scope="row"><span style="color:darkorange">Beta:</span> Do not pass source in Query String';
echo helpIcon('In v0.8 and below, the .htaccess always passed the filename of the image to the script through the query string. It however seems that the PHP can determine the original file name in a server variable, and therefore does not need it in the query string. Removing it has the benefit of reducing risk of firewalls blocking the request. This option will probably go away in the next release, when it is confirmed that the feature is stable. So you better test if it works now, thanks!');
echo '</th><td>';
echo '<input type="checkbox" id="do_not_pass_source_in_query_string" name="do-not-pass-source-in-query-string" value="true" ' . ($config['do-not-pass-source-in-query-string'] ? 'checked="checked"' : '') . '">';
echo '</td></tr>';
