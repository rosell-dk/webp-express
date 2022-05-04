<?php
namespace WebPExpress;
?>
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
            <p>
                Two distinct methods for altering HTML are supported. <a id="show_alterhtml_chart_btn" href="javascript:updateAlterHTMLChartVisibility(true);">View comparison chart</a>
                <input type="hidden" name="ui-show-alter-html-chart" id="ui_show_alter_html_chart" value="false">
            </p>
            <div id="alter_html_comparison_chart" name="alter-html-comparison-chart" class="toggler effect-slider">
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
                                A small javascript can be optionally added for dynamically loading picturefill.js on browsers that doesn't support the picture tag.
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
                                <b>Does not work with page caching</b> &ndash; <i>unless</i> you are using the <i>Cache Enabler</i> plugin,
                                which is able to maintain two cached versions of each page.<br><br>
                                <span style="font-size:10px">
                                    Note: <i>Cache Enabler</i> also has HTML altering, but their implementation has <a target="_blank" href="https://github.com/keycdn/cache-enabler/issues/51">limitations</a>.
                                    It for example doesn't replace background images in inline styles and it does not look for all common lazy load attributes. It also has some problems in edge cases.
                                    For this reason, I recommend activating HTML altering in WebP Express, even when <i>Cache Enabler</i> is used.
                                    By doing that, both plugins will have a go at it (WebP Express comes first).
                                    At least it will take care of the limitations in <i>Cache Enabler</i>.
                                    It does however not cure the edge cases where Cache Enabler replaces things it should not, or <a href="https://wordpress.org/support/topic/why-optimus-with-cache-enabler/">crashes</a>
                                </span>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><nobr>Styling and javascript</nobr></th>
                        <td>May break because of changed HTML structure.
                            A selector such as "div > img" will no longer match the image because the immidiate parent is now "picture".
                            However, a selector such as "div img" will still work.
                            Luckily, the picture element is not meant to be styled - one still has to target the contained img element.
                        </td>
                        <td>No problems</td>
                    </tr>
                    <tr>
                        <th>Comprehensiveness</th>
                        <td>Only replaces &lt;img&gt; tags - other images are untouched</td>
                        <td>Very comprehensive. Replaces images in inline styles, image urls in lazy load attributes set in &lt;div&gt; or &lt;li&gt; tags, etc.</td>
                    </tr>
                </table>
            </div>
            <a id="hide_alterhtml_chart_btn" href="javascript:updateAlterHTMLChartVisibility(false);">Hide comparison chart</a>
            <div>
                <label>What to replace:</label>
                <ul style="margin-left: 20px; margin-top: 5px"><li>
                <?php
                webpexpress_radioButton(
                  'alter-html-replacement',
                  'picture',
                  'Replace &lt;img&gt; tags with &lt;picture&gt; tags, adding the webp to srcset.',
                  $config['alter-html']['replacement'],     // PS: the function takes care of the escaping
                  '<p>"Picture tag" replaces &lt;img&gt tags with &lt;picture&gt; tags and adds the webp as an extra source. ' .
                        'This effectively points webp-enabled browsers to the webp variant and other browsers to ' .
                        'the original image.</p>' .
                        '<p><em>Beware that this structural change may break styling!</em><br>' .
                        '(a selector such as "div > img" will no longer match the image because the immidiate parent is now "picture". However, ' .
                        'a selector such as "div img" will still work)' .
                        '</p>' .
                        '<p>PS: This functionality is handled by ' .
                        '<a target="_blank" href="https://github.com/rosell-dk/dom-util-for-webp">this external library</a>' .
                        ' (I have pushed the code to an external library so it can be used by other projects besides this plugin)</p>'
                );
                ?>
                </li>
                <li><div id="alter_html_picture_options_div" style="margin-left:18px; margin-bottom: 13px;">
                    <?php
                    webpexpress_checkbox(
                        'alter-html-add-picturefill-js',
                        $config['alter-html']['alter-html-add-picturefill-js'],  // PS: the function takes care of the escaping
                        'Dynamically load picturefill.js on older browsers',
                        'If you enable this option, a small script will be added which detects if picture tags are supported and loads ' .
                        '<a href="https://github.com/scottjehl/picturefill" target="_blank">picturefill.js</a> if not. ' .
                        'It is recommended to activate this option, unless your theme or another plugin adds such a script. ' .
                        'Picture tag support is currently <a href="https://caniuse.com/#feat=picture" target="_blank">~94%</a>'
                    );
                    ?>
                </div></li>
                <li>
                <?php
                webpexpress_radioButton(
                  'alter-html-replacement',
                  'url',
                  'Replace image URLs',
                  $config['alter-html']['replacement'],  // PS: the function takes care of the escaping
                  '<p>"Image URLs" replaces the image URLs to point to ' .
                        'the webp <i>rather than</i> the original. Handles src, srcset, common lazy-load attributes and even ' .
                        'inline styles</p>' .
                        '<p><b>Does not work with page caching</b> &ndash; <i>unless</i> you are using the <i>Cache Enabler</i> plugin</p>' .
                        '<p>Note that you will have to do something for the browsers that does not support webp. ' .
                        'And that something is in most cases to enable the <i>Only do the replacements in webp enabled browsers</i> option, which ' .
                        'will show up when you enable this option. ' .
                        'Or you can experiment with javascript solutions. There is for example the <a href="https://webpjs.appspot.com/">webpjs</a> javascript library. ' .
                        'But it does not support srcset, which is a showstopper. There are other libraries out there. ' .
                        '<p>PS: This replace functionality is handled by ' .
                        '<a target="_blank" href="https://github.com/rosell-dk/dom-util-for-webp">this external library</a>' .
                        ', created for the purpose.</p>'
                );
                ?>
                </li>
                <li><div id="alter_html_url_options_div" style="margin-left:18px; margin-bottom: 13px;">
                    <?php
                    webpexpress_checkbox(
                        'alter-html-only-for-webp-enabled-browsers',
                        $config['alter-html']['only-for-webp-enabled-browsers'] == true,
                        'Only do the replacements in webp enabled browsers',
                        'If you enable this option, the replacements will only be made, when the request is from ' .
                            'a browser that supports webp. Note that this will not play well with plugins that caches ' .
                            'the page. To overcome that, you can use the Cache Enabler plugin, '
                    );
                    ?>
                </div></li>
                </ul>

            </div>
            <?php
            if ($config['operation-mode'] != 'no-conversion') {
                echo '<div><div style="margin-left: 11px">';
                webpexpress_checkbox(
                    'alter-html-for-webps-that-has-yet-to-exist',
                    (!$config['alter-html']['only-for-webps-that-exists']),
                    'Reference webps that haven\'t been converted yet',
                    'If you enable this option, there will be references to webp files that doesnt exist yet. ' .
                        'And that will be ok! - Just make sure to enable the option to convert missing webp files ' .
                        'upon request in the "Redirection rules" section.'
                );
                echo '</div></div>';
            }
            ?>
            <div style="margin-top: 20px">
                <label>How to replace: <?php echo helpIcon(
                    '<p>WebP Express can either trigger the HTML altering by hooking into ' .
                    'standard content filtering hooks, or it can do it on the whole page. ' .
                    'Usually, you should select the first option, as it is lighter to ' .
                    'do string manipulation on selected content than on the whole page. ' .
                    'Also, in order to protect from memory problems, the HTML altering is ' .
                    'bypassed when the HTML is larger than 600 kb. ' .
                    'However, not all themes and plugins uses the standard hooks, so in some ' .
                    'cases, you will have to resort to the second option. As of 0.17.5, ' .
                    'hooks has been added for some popular plugins, such as <i>ACF</i> and ' .
                    '<i>Woo Commerce Product Image</i>. ' .
                    '<p><i>Use content filtering hooks</i> currently supports the following hooks: ' .
                    '<i>the_content</i>, <i>the_excerpt</i>, <i>post_thumbnail_html</i>, <i>get_avatar</i>' .
                    ', <i>woocommerce_product_get_image</i>, <i>acf_the_content</i>, <i>dynamic_sidebar_before</i>, <i>dynamic_sidebar_after</i>' .
                    '. Let me know if you need another content filtering hook to be added.' .
                    '</p>',
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
            <div style="margin-top: 20px">
                <label>CDN hostname(s) / hostname alias(es) <?php echo helpIcon(
                    '<p>If images can be reached on another hostname than that of your site' .
                    (Multisite::isMultisite() ? '' : ' ("' . Paths::getHostNameOfUrl(Paths::getHomeUrl()) . '")') .
                    ', you should specify that hostname here. ' .
                    'This ensures that image URLs pointing to that hostname also gets replaced.</p>' .
                    '<p>PS: If you wonder why your images URLs to your CDN gets processed by WebP Express even though you ' .
                    'have not entered anything here, the answer is probably that you are using a plugin that changes ' .
                    'your URLs to point to the CDN and that WebP Express gets to do the replacement before your plugin. ' .
                    'Do not rely on this. A future update to your plugin could change that order.</p>'
                );
                ?></label>
            </div>
            <?php
            $aliases = $config['alter-html']['hostname-aliases'];
            $aliasInputs = array_merge($aliases, ['']);
            foreach ($aliasInputs as $i => $alias) {
                ?>
                http(s)://<input name="alter-html-hostname-alias-<?php echo $i ?>" value="<?php echo esc_attr($alias); ?>"/><br>
                <?php
            }
            ?>

        </div>

    </th>
</tr>
