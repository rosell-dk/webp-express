<tr>
    <th scope="row">
        Redirect requests for non-existing webp-files to converter</span>
        <?php echo helpIcon(
            '<p>Have ie. "image.jpg.webp" automatically generated from "image.jpg" the first time the webp is requested. ' .
            'This way you can reference webps before they actually exists.</p>' .
            '<p>The feature works the following way:' .
            '<ol>' .
            '<li>WebP adds rules in the <i>.htaccess</i> that redirects requests for non-existing webp files to <i>webp-realizer.php</i></li>' .
            '<li><i>webp-realizer.php</i> looks for a corresponding jpg/png. If found, it is converted and saved, so the webp will be directly available the next time it is requested &ndash; and it tells the browser to fetch the same URL again (302 redirect to same location). In case no corresponding jpg/png is found, a 404 is returned</li>' .
            '</ol>' .
            '</p>' .
            'This only happens once per image. The next time the webp is requested, the rule will not trigger because the webp now exists'
//            '<p>This feature allows you to reference webp images before they actually exists. You can ie write:' .
//            "<pre>&lt;picture&gt;\n  &lt;source srcset=\"image.jpg.webp\" type=\"image/webp\" /&gt;\n  &lt;img src=\"image.jpg\" /&gt;&lt;/picture&gt;"

        ); ?>
    </th>
    <td>
        <input
            name="enable-redirection-to-webp-realizer"
            <?php echo ($config['enable-redirection-to-webp-realizer'] ? 'checked="checked"' : '') ?>
            value="true"
            type="checkbox"
        >
    </td>
</tr>
