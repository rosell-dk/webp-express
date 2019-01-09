<tr>
    <th scope="row">Alter HTML? <?php echo helpIcon(
        '<p>Alter HTML to either use picture tag syntax for pointing to webp versions or point directly to webps.</p>' .
        (($config['operation-mode'] == 'standard') ? '<p>This is not needed in Standard operation mode, but enabling it has the benefit that when a user downloads an image, it will have the correct extension.</p>' : '')
        );
    ?>
    </th>
    <td>
        <input
            id="alter_html_enabled"
            name="alter-html-enabled"
            <?php echo ($config['alter-html']['enabled'] ? 'checked="checked"' : '') ?>
            value="true"
            type="checkbox"
        >
        <div id="alter_html_options_div">
            <div>
                <?php
                webpexpress_checkbox(
                    'alter-html-only-for-webps-that-exists',
                    $config['alter-html']['only-for-webps-that-exists'],
                    'Only for webps that exists',
                    'Usually you should keep this disabled and enable the option for automatically creating ' .
                        'missing webp files when requested'
                );
                ?>
            </div>
            <div>
                <?php
                webpexpress_checkbox(
                    'alter-html-only-for-webp-enabled-browsers',
                    $config['alter-html']['only-for-webp-enabled-browsers'],
                    'Only do the replacements in webp enabled browsers',
                    'If you enable this option, the replacements will only be made, when the request is from ' .
                        'a browser that supports webp. Note that this will not play well with plugins that caches ' .
                        'the page. Instead of enabling this option, you should consider using the Cache Enabler plugin, ' .
                        'which does the same, but with page caching.'
                );
                ?>
            </div>
            <div>
                <label>Replacement:</label>
                <?php
                webpexpress_radioButtons('alter-html-replacement', $config['alter-html']['replacement'], [
                    'picture' => 'Picture tag',
                    'extension' => 'Image src',
                ], [
                    'picture' => '<p>"Picture tag" replaces images with &lt;picture&gt; tags and adds the webp to the src-set. ' .
                        'This effectively points webp-enabled browsers to the webp variant and other browsers to ' .
                        'the original image. It also adds a script that dynamically loads picturefill.js in browsers ' .
                        'that doesnt support the picture tag.</p>' .
                        '<p><em>Beware that this structural change may affect styling!</em></p>',
                    'extension' => '"Image src" replaces the src attributes to point to the webp <i>rather than</i> the ' .
                        'original. If you do that, either make sure to add javascript that makes older browsers ' .
                        'capable of displaying webp or set the "" option below to ""'
                ]
                );
                ?>

                <!--<select name="alter-html-replacement">
                <label>Replacement: <?php /*echo helpIcon(
                    '<p>"Picture tag" replaces images with &lt;picture$gt; tags and adds the webp to the src-set. ' .
                    'This effectively points webp-enabled browsers to the webp variant and other browsers to ' .
                    'the original image. It also adds a script that dynamically loads picturefill.js in browsers ' .
                    'that doesnt support the picture tag. Beware that this structural change may affect styling.</p>' .
                    '<p>"Image src" replaces the src attributes to point to the webp <i>rather than</i> the ' .
                    'original. If you do that, either make sure to add javascript that makes older browsers ' .
                    'capable of displaying webp, or set the "" option below to ""</p>',
                    'no-margin-left set-margin-right');*/
                ?></label>
                    <?php
                    /*webpexpress_selectBoxOptions($config['alter-html']['replacement'], [
                        'picture' => 'Picture tag',
                        'extension' => 'Image src',
                    ]);*/
                    ?>
                </select>-->
            </div>
            <div>
                <label>Hooks: <?php echo helpIcon(
                    '<p>Select which hook(s) should trigger the replacement</p>',
                    'no-margin-left set-margin-right');
                ?></label>

                <?php
                webpexpress_radioButtons('alter-html-hooks', $config['alter-html']['hooks'], [
                    'content-hooks' => 'Content hooks (the_content, the_excerpt, etc)',
                    'init' => 'Init hook, <em>using output buffering</em>',
                ], [
                ]
                );
                ?>
            </div>

        </div>

    </td>
</tr>
