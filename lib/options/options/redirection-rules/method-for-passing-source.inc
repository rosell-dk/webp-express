
<tr>
    <th scope="row">Method for passing filename <?php
        echo helpIcon('<p>' .
            'Passing the the filename of a requested image from the <i>.htaccess</i> to the converter scripts can be done in different ways. ' .
            'Some works on some systems but not others. Some opens up for conversion of to be started by other parties, others dont. ' .
            'Some generates a lot of stuff in the .htaccess, making it harder to read, and the site a tiny bit slower than otherwise. Others generates no extra stuff.' .
            'So choose wisely. <table><tbody>' .
            '<tr><th>Method</th><th>Description</th></tr>' .
            '<tr><td><i>1. Do not pass...</i></td><td>Very clean and works on most standard setups.</td></tr>' .
            '<tr><td><i>2. Passing through an environment variable</i></td><td>Only adds minimally to the .htaccess. Falls back to #1 if the pass fails, and thus works on more systems than #1</td></tr>' .
            '<tr><td><i>3. Passing through a request header</i></td><td>Adds a bit more to the .htaccess. Falls back on #2 if the pass fails, and thus works on more systems than #2</td></tr>' .
            '<tr><td><i>4. Passing full path through query string (full path)</i></td><td>If even #3 fails, try this one. Note however that it may be subject to firewall blocking.</td></tr>' .
            //'<tr><td><i>5. Passing full path through query string (relative path)</i></td><td>If you are subject to firewall blocking, try this one. Relative paths does not look as suspecious as full paths. This option is however in beta</td></tr>' .
            '</tbody></table></p>'
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
                //'querystring-relative-path' => 'Pass through query string (relative path from wp-content to image)',
            ]);
            ?>
        </select>
    </td>
</tr>
