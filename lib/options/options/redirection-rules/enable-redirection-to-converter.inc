<tr>
    <th scope="row">
        <?php if ($config['operation-mode'] == 'no-varied-responses'): ?>
            Convert non-existing webp-files upon request to original image  <span style="color:darkorange">(obsolete?)<?php
                echo helpIcon(
                    //'<p><em>The auto-convertion works this way: When an image is requested, a rule in the .htaccess detects if that image has been converted. If not, the request is redirected to the converter, which creates the webp and returns <em>the original</em> image</em></p>
                    '<p>This works the following way:' .
                    '<ol>' .
                    '<li>WebP adds rules in the <i>.htaccess</i> that redirects requests for jpg/png images to the converter, <i>when no corresponding webp image is found</i></li>' .
                    '<li>The converter creates the webp image, the jpg/png is served</li>' .
                    '<li>The jpg/png file is served</li>' .
                    '</ol>' .
                    '<p>This only happens once per image. The next time the jpg/png is requested, ' .
                    'the rule will not trigger because it now detects a corresponding webp</p>' .
                    '<p>Note: This option is probably not needed anymore after the introduction of the <i>Convert '.
                    'non-existing webp-files upon request?</i> option. I am considering removing the feature. If ' .
                    'you see any reason not to remove it, please let me know! (ie in the <a target="_blank" href="https://wordpress.org/support/plugin/webp-express">support forum</a>).</p>'
                ); ?>
        <?php else: ?>
            Enable redirection to converter?<?php echo helpIcon('This will add rules in the .htaccess that redirects to converter'); ?>
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
