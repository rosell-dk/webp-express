<tr>
    <th scope="row">
        <?php if ($config['operation-mode'] == 'cdn-friendly'): ?>
            Redirect requests for jpg/png to converter, but only when there is no webp, and serve the original<?php
                echo helpIcon(
                    //'<p><em>The auto-conversion works this way: When an image is requested, a rule in the .htaccess detects if that image has been converted. If not, the request is redirected to the converter, which creates the webp and returns <em>the original</em> image</em></p>
                    '<p>This works the following way:' .
                    '<ol>' .
                    '<li>WebP adds rules in the <i>.htaccess</i> that redirects requests for jpg/png images to the converter, <i>when no corresponding webp image is found</i></li>' .
                    '<li>The converter creates the webp image, the jpg/png is served</li>' .
                    '<li>The jpg/png file is served</li>' .
                    '</ol>' .
                    '<p>This only happens once per image. The next time the jpg/png is requested, ' .
                    'the rule will not trigger because it now detects a corresponding webp</p>' .
                    '<p>Note: After the introduction of the <i>Convert non-existing webp-files upon request?</i> option, ' .
                    'you probably will not need this option. There are however rare cases, where it could be useful. ' .
                    '</p>'
                ); ?>
        <?php else: ?>
            Enable redirection to converter?<?php echo helpIcon('This will add rules in the .htaccess that redirects images (jpg/png) to the script'); ?>
        <?php endif; ?>
    </th>
    <td>
        <input
            id="enable_redirection_to_converter"
            name="enable-redirection-to-converter"
            <?php echo ($config['enable-redirection-to-converter'] ? 'checked="checked"' : '') ?>
            value="true"
            type="checkbox"
        >
    </td>
</tr>
