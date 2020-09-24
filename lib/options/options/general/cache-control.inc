<?php

$cacheControl = $config['cache-control'];
$cacheControlCustom = $config['cache-control-custom'];
$cacheControlMaxAge = $config['cache-control-max-age'];
$cacheControlPublic = $config['cache-control-public'];
?>

<tr id="cache_control_div">
    <th scope="row">Cache-Control header <?php
    switch ($config['operation-mode']) {
        case 'no-conversion':
            echo helpIcon('<p>Optionally set cache-control header for the internally redirected images ' .
                '(recommended!)</p>');
            break;
        case 'cdn-friendly':
            echo helpIcon('<p>Optionally set cache-control header for webp images');
            break;
        default:
            echo helpIcon('<p>Controls the cache-control header on successful conversion and direct redirection to converted ' .
                'image in .htaccess. In case of convert failure, headers will be sent to prevent caching.</p>' .
                '<p>PS: In order to set <i>stale-while-revalidate</i> and <i>stale-if-error directives<i>, you must ' .
                'currently choose "Custom". <a target="_blank" href="https://www.fastly.com/blog/stale-while-revalidate-stale-if-error-available-today">It is a good idea to set these</a>.' .
                '</p>');
        break;
    }
    ?>
    </th>
    <td>
        <select id="cache_control_select" name="cache-control">
            <option value="no-header" <?php if ($cacheControl == 'no-header') echo ' selected' ?>>Do not set</option>
            <option value="set" <?php if ($cacheControl == 'set') echo ' selected' ?>>Set</option>
            <option value="custom" <?php if ($cacheControl == 'custom') echo ' selected' ?>>Custom</option>
        </select>
        <div id="cache_control_custom_div" style="display:inline-block;">
            <input type="text" id="cache_control_custom" name="cache-control-custom" value="<?php echo esc_attr($cacheControlCustom) ?>">
            <?php echo helpIcon(
                'You can read about possible options ' .
                '<a target="_blank" href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control">here</a>',
                'no-margin-left'
            );?>
        </div>

        <div id="cache_control_set_div" style="display:inline-block;">
            <select id="cache_control_public" name="cache-control-public">
                <?php
                webpexpress_selectBoxOptions(($cacheControlPublic ? 'public' : 'private'), [
                    'public' => 'Public',
                    'private' => 'Private',
                ]);
                ?>
            </select>
            <?php echo helpIcon(
                '<p>Set either the "public" or "private" directive. Setting this to public means that you are allow caching in shared caches. ' .
                'Only do this, if you are sure your CDN or reverse proxy can handle that the ' .
                'response varies depending on the Accept header.</p>' .
                '<p>Note: I am not completely sure that all forward  proxies handles varied responses. ' .
                'This is discussed <a target="_blank" href="https://github.com/rosell-dk/webp-express/issues/144">here</a>.</p>'
                ,
                'no-margin-left set-margin-right'
            );
            ?>
            </select>
            <select id="cache_control_max_age" name="cache-control-max-age">
                <?php
                webpexpress_selectBoxOptions($cacheControlMaxAge, [
                    'one-second' => 'One second',
                    'one-minute' => 'One minute',
                    'one-hour' => 'One hour',
                    'one-day' => 'One day',
                    'one-week' => 'One week',
                    'one-month' => 'One month',
                    'one-year' => 'One year',
                ]);
                ?>
            </select>
            <?php echo helpIcon(
                'This sets the max-age value. If want to set s-maxage, or generally need more control, ' .
                'choose "custom" in the first combobox (You can read about possible options ' .
                '<a target="_blank" href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control">here</a>)',
                'no-margin-left'
            );?>
        </div>
        <br>
        <!--
        <table>
            <tbody>
                <tr>
                    <td style="font-weight: bold; margin:0; padding:0">max-age</td>
                    <td>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        Allow caching in shared caches (ie CDNs or reverse proxies)
                        <?php echo helpIcon(''); ?>
                    </th>
                    <td>
                        <input
                            name="cache-control-public"
                            <?php echo ($config['cache-control-public'] ? 'checked="checked"' : '') ?>
                            value="true"
                            type="checkbox"
                        >
                    </td>
                </tr>
            </tbody>
        </table>
    -->

    </td>
</tr>
