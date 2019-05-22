<tr>
    <th scope="row" colspan=1>
        PNG options
        <?php echo helpIcon(
            'The "png" settings applies when the image being converted is a png.'
        );?>
    </th>
    <td>
        <label>
            WebP encoding:
        </label>
        <select id="png_encoding_select" name="png-encoding">
            <?php
            webpexpress_selectBoxOptions($config['png-encoding'], [
                'lossless' => 'Lossless',
                'auto' => 'Auto',
            ]);
            ?>
        </select>
        <?php echo helpIcon(
            '<p>The WebP format supports two types of encoding: lossy and lossless.</p>' .
            '<p>If you select "Auto", WebP Express will try converting to both encodings ' .
            'and select one that resulted in the smallest file.</p>' .
            '<p>Note that only some converters supports lossless encoding. It is supported by ' .
            'Cwebp, Vips, Imagick binary and Remote WebP Express (if the WebP Express you are ' .
            'connecting to are using one of these, and you are using version 0.14+). Converters ' .
            'that does not support lossless simply ignores the "Auto" option and converts to lossy.</p>' .
            '<p>You can read more about the option <a target="_blank" href="https://github.com/rosell-dk/webp-convert/blob/master/docs/v2.0/converting/introduction-for-converting.md#auto-selecting-between-losslesslossy-encoding">here</a></p>'
        );?>
    </td>
</tr>
<tr colspan=2 id="png_quality_lossy_row" class="toggler effect-visibility">
    <th></th>
    <td>
        <label>
            Quality for lossy:
        </label>

        <input type="text" size=3 name="png-quality" value="<?php echo $config['png-quality'] ?>" style="text-align:right; padding-left:0px; padding-right:4px; width:34px">
        <?php
            echo helpIcon('Enter number (0 - 100)');
        ?>
    </td>
</tr>
<tr colspan=2>
    <th></th>
    <td>
        <label>
            Quality for lossless:
        </label>
        <select id="png_enable_near_lossless" name="png-enable-near-lossless">
            <?php
            webpexpress_selectBoxOptions($config['png-enable-near-lossless'] ? 'on' : 'off', [
                'on' => 'Apply preprocessing',
                'off' => '100% lossless',
            ]);
            ?>
        </select>
        <?php
            echo helpIcon(
                '<p>What? Lossless is lossless, right?. Well, that depends on how you look at it. ' .
                'The webp conversion library has this nifty option called "near lossless preprocessing". The preproccesing manipulates ' .
                'the image before encoding in order to help compressibility.</p>' .
                '<p>Note: The near-lossless option is supported by the <i>Cwebp</i> and <i>Vips</i> conversion methods.</p>' .
                '<p>Read more about the feature <a target="_blank" href="https://github.com/rosell-dk/webp-convert/blob/master/docs/v2.0/converting/introduction-for-converting.md#near-lossless">here</a></p>'
            );
        ?>
        <div id="png_near_lossless_div" style="display:inline-block; margin-left:10px" class="toggler effect-visibility">
            <label>
                "Near lossless" quality:
            </label>
            <input type="text" size=3 name="png-near-lossless" value="<?php echo $config['png-near-lossless'] ?>" style="text-align:right; padding-left:0px; padding-right:4px; width:34px">
            <?php
                echo helpIcon(
                    'The level of near-lossless image preprocessing (when trying lossless). ' .
                    'You can think of it as "quality" for lossless. The range is 0 (maximum preprocessing) to 100 (no preprocessing). ' .
                    '<a href="https://groups.google.com/a/webmproject.org/forum/#!topic/webp-discuss/0GmxDmlexek">Read this</a> to get an informed opinion about appropriate setting.'
                );
            ?>
        </div>

        </td>
</tr>
