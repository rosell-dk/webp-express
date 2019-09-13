<tr>
    <th scope="row">
        Create webp files upon request?
        <?php echo helpIcon(
            '<p>Enabling this option will add lines in the .htaccess which redirects requests for non-existing webp-files to ' .
            'the converter script (webp-realizer.php). ' .
            '<i>This way you can reference webps before they actually exists.</i></p>' .
            '<p>The feature works the following way:' .
            '<ol>' .
            '<li>WebP adds rules in the <i>.htaccess</i> that redirects requests for non-existing webp files to <i>webp-realizer.php</i></li>' .
            '<li><i>webp-realizer.php</i> looks for a corresponding jpg/png. ' .
            'If found, it is converted, saved and served. In case no corresponding jpg/png is found, a 404 is issued</li>' .
            '</ol>' .
            '</p>' .
            'This only happens once per image. The next time the webp is requested, the rule will not trigger because the webp now exists.'
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
        <button style="margin-left: 30px;" onclick="WebPExpress.SelfTest.openPopup('redirectToWebPRealizer')" class="button button-secondary" type="button">
            Live test
        </button>
    </td>
</tr>
