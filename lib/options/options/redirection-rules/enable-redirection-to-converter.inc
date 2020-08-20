<tr>
    <th scope="row">
        Enable redirection to converter?
        <?php echo helpIcon(
            '<p>This will add rules in the <i>.htaccess</i> that redirects images (jpeg/png) to the conversion script ("webp-on-demand.php") ' .
            'for browsers that supports webp.</p>' .
            '<p>If the script detects that the webp already exists, and it is smaller and newer than the original, the ' .
            'webp is served directly. Otherwise the original image is converted and served.</p>' .
            '<p>The redirect rule is placed below the rule that redirects directly to existing ' .
            'webp files, which means that conversion will only be triggered once.</p>'
        ); ?>
    </th>
    <td>
        <input
            id="enable_redirection_to_converter"
            name="enable-redirection-to-converter"
            <?php echo ($config['enable-redirection-to-converter'] ? 'checked="checked"' : '') ?>
            value="true"
            type="checkbox"
        >
        <button style="margin-left: 30px;" onclick="WebPExpress.SelfTest.openPopup('redirectToConverter')" class="button button-secondary" type="button">
            Live test
        </button>
    </td>
</tr>
