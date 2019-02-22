<tr>
    <th scope="row">Alter HTML?</span><?php echo helpIcon(
        '<p>Alter HTML to either use picture tag syntax for pointing to webp versions or point directly to webps.</p>' .
        '<p>Pro picture tag syntax: The browser selects the webp if it supports it.</p>' .
        '<p>Pro image urls: Also works on inline styles</p>' .
        (($config['operation-mode'] == 'varied-responses') ?
            '<p>You do not need to enable this in <i>Varied image responses</i> operation mode, but enabling it has some benefits: ' .
            'Caching is improved if you are on a CDN as the webp images that are requested directly does not need to be keyed by the Accept header. ' .
            'Also, when a user downloads an image, it will have the correct extension.</p>' : '')
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
    </td>
  </tr>
  <tr>
      <th scope="row" colspan=2>
        <div id="alter_html_options_div">
            <div>
                <label>What to replace:</label>
                <?php
                webpexpress_radioButtons('alter-html-replacement', $config['alter-html']['replacement'], [
                    'picture' => 'Replace &lt;img&gt; tags with &lt;picture&gt; tags, adding the webp to srcset.',
                    'url' => 'Replace image URLs',
                ], [
                    'picture' => '<p>"Picture tag" replaces &lt;img&gt tags with &lt;picture&gt; tags and adds the webp as an extra source. ' .
                        'This effectively points webp-enabled browsers to the webp variant and other browsers to ' .
                        'the original image. It also adds a script that dynamically loads picturefill.js in browsers ' .
                        'that doesnt support the picture tag.</p>' .
                        '<p><em>Beware that this structural change may affect styling!</em></p>' .
                        '<p>PS: This functionality is handled by ' .
                        '<a target="_blank" href="https://github.com/rosell-dk/dom-util-for-webp">this external library</a>' .
                        ' (I have pushed the code to an external library so it can be used by other projects besides this plugin)</p>',
                    'url' => '<p>"Image URLs" replaces the image URLs to point to ' .
                        'the webp <i>rather than</i> the original. Handles src, srcset, common lazy-load attributes and even ' .
                        'inline styles</p>' .
                        '<p>Note that you will have to do something for the browsers that does not support webp. ' .
                        'For example, you can enable the <i>Only do the replacements in webp enabled browsers</i> option, which ' .
                        'will show up when you enable this option. ' .
                        'Or you can add the <a href="https://webpjs.appspot.com/">webpjs</a> javascript library. ' .
                        'In the next release, you will be able to add the webjs library with this plugin. ' .
                        '<p>PS: This replace functionality is handled by ' .
                        '<a target="_blank" href="https://github.com/rosell-dk/dom-util-for-webp">this external library</a>' .
                        ', created for the purpose.</p>',
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
            <?php
            if ($config['operation-mode'] != 'no-conversion') {
                echo '<div><div style="margin-left: 11px">';
                webpexpress_checkbox(
                    'alter-html-for-webps-that-has-yet-to-exist',
                    'Reference webps that hasn\'t been converted yet',
                    'If you enable this option, there will be references to webp files that doesnt exist yet. ' .
                        'And that will be ok! - Just make sure to enable the option to convert missing webp files ' .
                        'upon request'
                );
                echo '</div></div>';
            }
            ?>
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

    </th>
</tr>
