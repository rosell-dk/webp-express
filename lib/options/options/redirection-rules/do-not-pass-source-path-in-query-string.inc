<?php
echo '<tr><th scope="row">Do not pass source in Query String';
echo helpIcon(
    'You can try unchecking this, if you are experiencing that no images are converted. In v0.8 and below, the <i>.htaccess</i> ' .
    'always passed the filename of the image to the script through the query string. ' .
    'It however seems that passing through an environment variable instead works just fine. ' .
    'As passing it through the query string can cause some firewalls to block the request, we no longer do this per default.'
);
echo '</th><td>';
echo '<input type="checkbox" id="do_not_pass_source_in_query_string" name="do-not-pass-source-in-query-string" value="true" ' . ($config['do-not-pass-source-in-query-string'] ? 'checked="checked"' : '') . '">';
echo '</td></tr>';
