
<tr>
    <th scope="row">Method for passing filename <span style="color:green">(new in 0.12)</span><?php
        echo helpIcon('<p>' .
            'Passing the the filename of a requested image from the <i>.htaccess</i> to the converter scripts can be done in different ways. ' .
            'Some works on some systems, some on others.' .
            '<table class="designed"><tbody>' .
            '<tr><th>Method</th><th>Description</th></tr>' .
            '<tr><td><i>1. Do not pass...</i></td><td>Very clean (nothing is added to the .htaccess). It works on most standard setups. It does not work when Wordpress has been moved using "method 1" <a href="https://codex.wordpress.org/Giving_WordPress_Its_Own_Directory">described here</a></td></tr>' .
            '<tr><td><i>2. Passing through an environment variable</i></td><td>Only adds minimally to the .htaccess. Falls back to #1 if the pass fails, and thus works on more systems than #1</td></tr>' .
            '<tr><td><i>3. Passing through a request header</i></td><td>Adds a bit more to the .htaccess. Falls back on #2 if the pass fails, and thus works on more systems than #2</td></tr>' .
            '<tr><td><i>4. Passing full path through query string (full path)</i></td><td>If even #3 fails, try this one. Note however that it may be subject to firewall blocking.</td></tr>' .
            '<tr><td><i>5. Passing full path through query string (relative path)</i></td><td>If you are subject to firewall blocking, try this one. Relative paths does not look as suspecious as full paths. This option is however in beta</td></tr>' .
            '</tbody></table></p>' .
            '<p><b>Note:</b> The receiving script will not accept through query string unless it is choosen here. If you are on NGINX, <i>make sure to select the query string method you are using</i></p>' .
            '<p>To test if a method works: <ul class="with-bullets">' .
            '<li>be sure to disable direct redirection</li>' .
            '<li>Visit an image url, with "?cachebreak=something-unique"</li>' .
            '<li>Beware that even with a cachebreaker token, you might get a cached result (a CDN can be set to discard the query string)</li>' .
            '<li>To be absolutely sure, you can try uploading a new image</li>' .
            '<li>You can add "&debug" to the URL for further info</li>' .
            '</p>'

        );
    ?></th>
    <td>
        <select name="method-for-passing-source">
            <?php
            webpexpress_selectBoxOptions($config['method-for-passing-source'], [
                'request-uri' => 'Do not pass, but let the script use the REQUEST_URI variable.',
                'environment-variable' => 'Pass through environment variable',
                'request-header' => 'Pass through request header',
                'querystring-full-path' => 'Pass through query string (full path)',
                'querystring-relative-path' => 'Pass through query string (relative path from wp-content to image)',
            ]);
            ?>
        </select>
    </td>
</tr>
