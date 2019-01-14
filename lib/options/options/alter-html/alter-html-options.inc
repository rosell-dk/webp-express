<tr>
    <th scope="row">Alter HTML? <span style="color:darkorange">(beta)</span><?php echo helpIcon(
        '<p>Alter HTML to either use picture tag syntax for pointing to webp versions or point directly to webps.</p>' .
        '<p>The feature is new in 0.11.0. It is based on code from Cache Enabler (image urls) and ShortPixel (picture tags), but I needed altering it, as this plugin does not limit its scope to the uploads folder. I have tested pretty thoroughly, but it has yet to stand the tests of the real world.</p>' .
        (($config['operation-mode'] == 'varied-responses') ? '<p>This is not needed in Standard operation mode, but enabling it has the benefit that when a user downloads an image, it will have the correct extension.</p>' : '')
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
                <label>What to replace:</label>
                <?php
                webpexpress_radioButtons('alter-html-replacement', $config['alter-html']['replacement'], [
                    'picture' => 'Replace &lt;img&gt; tags with &lt;picture&gt; tags, adding the webp to srcset.',
                    'url' => 'Replace image URLs',
                ], [
                    'picture' => '<p>"Picture tag" replaces images with &lt;picture&gt; tags and adds the webp to the src-set. ' .
                        'This effectively points webp-enabled browsers to the webp variant and other browsers to ' .
                        'the original image. It also adds a script that dynamically loads picturefill.js in browsers ' .
                        'that doesnt support the picture tag.</p>' .
                        '<p><em>Beware that this structural change may affect styling!</em></p>',
                    'url' => '<p>"Image URLs" replaces the image URLs in src, srcset and ref attributes to point to ' .
                        'the webp <i>rather than</i> the original.</p>' .
                        '<p>Note that you will have to do something for the browsers that does not support webp. ' .
                        'For example, you can enable the option below. ' .
                        'Or you can add the <a href="https://webpjs.appspot.com/">webpjs</a> javascript library. ' .
                        'In the next release, you will be able to add the webjs library with this plugin.'
                ]
                );
                ?>
                <div id="alter_html_url_options_div" style="margin-left:39px; margin-top: -5px;">
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
            </div>
            <div>
                <div style="margin-left: 11px">
                    <?php
                    webpexpress_checkbox(
                        'alter-html-for-webps-that-has-yet-to-exist',
                        (!$config['alter-html']['only-for-webps-that-exists']),
                        'Reference webps that hasn\'t been converted yet',
                        'If you enable this option, there will be references to webp files that doesnt exist yet. ' .
                            'And that will be ok! - Just make sure to enable the option to create the missing webp files ' .
                            'on demand (the "webp realizer" feature)'
                    );
                    ?>
                </div>
            </div>
            <div>
                <label>Where to replace: <?php echo helpIcon(
                    '<p></p>',
                    'no-margin-left set-margin-right');
                ?></label>

                <?php
                webpexpress_radioButtons('alter-html-hooks', $config['alter-html']['hooks'], [
                    'content-hooks' => 'Use content filtering hooks (the_content, the_excerpt, etc)',
                    'ob' => 'The complete page (using output buffering)</em>',
                ], [
                ]
                );
                ?>
            </div>

        </div>

    </td>
</tr>
