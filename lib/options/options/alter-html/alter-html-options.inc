<tr>
    <th scope="row">Alter HTML?</span><?php echo helpIcon(
        '<p>Alter HTML to either use picture tag syntax for pointing to webp versions or point directly to webps.</p>' .
        '<p>Pro picture tag syntax: The browser selects the webp if it supports it.</p>' .
        '<p>Pro image urls: Also works on inline styles</p>' .
        (($config['operation-mode'] == 'varied-image-responses') ?
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
            <div id="alter_html_comparison_chart">
                <p>
                    Two distinct methods for altering HTML are supported. Here is a comparison chart:
                </p>
                <table class="designed">
                    <tr>
                        <th></th>
                        <th>Method 1: Replacing &lt;img&gt; tags with &lt;picture&gt; tags</th>
                        <th>Method 2: Replacing image URLs</th>
                    </tr>
                    <tr>
                        <th>How it works</th>
                        <td>
                            <p>
                                It replaces &lt;img&gt; tags with  &lt;picture&gt; tags, adding two &lt;source&gt; tags - one for the original image(s), and one
                                for the webp image(s).
                                Browsers that supports webp picks the &lt;source&gt; tag with <i>type</i> attribute set to "image/webp".
                            </p>
                            <p>
                                We are using <a target="_blank" href="https://github.com/rosell-dk/dom-util-for-webp">this library</a>.
                                You can visit it for more information.
                            </p>
                        </td>
                        <td>
                            It replaces any image url it can find.
                            We are using <a target="_blank" href="https://github.com/rosell-dk/dom-util-for-webp">this library</a>.
                            You can visit it for more information.
                        </td>
                    </tr>
                    <tr>
                        <th>Page caching</th>
                        <td>Works great with page caching, because all browsers are served the same HTML</td>
                        <td>
                            <p>
                                As the HTML varies with the webp-capability of the browser, page caching is tricky.
                                However, it can be achieved with the wonderful <i>Cache Enabler</i> plugin, which maintains two cached versions of each page.<br><br>
                                <span style="font-size:10px">
                                    Note: Cache Enabler works without WebP Express, but the HTML altering have
                                    <a target="_blank" href="https://regexr.com/46isf">problems and limitations</a>, so the recommended setup
                                    is using WebP Express for the HTML altering of image URLs and <i>Cache Enabler</i> for the page caching.
                                    WebP Express does the replacing before Cache Enabler, so no special action is needed in order to do that.
                                </span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><nobr>Styling and javascript</nobr></th>
                        <td>May break because of changed HTML structure</td>
                        <td>No problems</td>
                    </tr>
                    <tr>
                        <th>Comprehensiveness</th>
                        <td>Only replaces &lt;img&gt; tags - other images are untouched</td>
                        <td>Very comprehensive. Replaces images in inline styles, image urls in lazy load attributes set in &lt;div&gt; or &lt;li&gt; tags, etc.</td>
                    </tr>
                </table>
                <p>
                    Note: The altering only happens for images that are converted and which exists in the same folder as the source with the extension selected in the GENERAL section.
                </p>
            </div>
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
                        'And that something is in most cases to enable the <i>Only do the replacements in webp enabled browsers</i> option, which ' .
                        'will show up when you enable this option. ' .
                        'Or you can experiment with javascript solutions. There is for example the <a href="https://webpjs.appspot.com/">webpjs</a> javascript library. ' .
                        'But it does not support srcset, which is a showstopper. There are other libraries out there. ' .
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
                            'the page. To overcome that, you can use the Cache Enabler plugin, '
                    );
                    ?>
                </div>
            </div>
            <?php
            if ($config['operation-mode'] != 'no-conversion') {
                echo '<div><div style="margin-left: 11px">';
                webpexpress_checkbox(
                    'alter-html-for-webps-that-has-yet-to-exist',
                    (!$config['alter-html']['only-for-webps-that-exists']),
                    'Reference webps that hasn\'t been converted yet',
                    'If you enable this option, there will be references to webp files that doesnt exist yet. ' .
                        'And that will be ok! - Just make sure to enable the option to convert missing webp files ' .
                        'upon request'
                );
                echo '</div></div>';
            }
            ?>
            <div style="margin-top: 20px">
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
