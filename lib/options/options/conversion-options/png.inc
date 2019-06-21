<tr id="png_row" class="toggler effect-opacity">
    <th scope="row" colspan=1>
        PNG options
        <?php echo helpIcon(
            'The "png" settings applies when the image being converted is a png.'
        );?>
    </th>
    <td id="png_td">
        <div>
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
                '<p>The WebP format supports two types of encoding: lossy and lossless. ' .
                'With WebP you can have both transparency AND lossy encoding, so "lossy" encoding is not out of the question, just ' .
                'because the converted file is a PNG.</p>' .
                '<p>There is no "lossy" option in the combobox, because converting all PNGs to lossy would probably be a bad idea. ' .
                'However, in many cases you get better compression with lossy. So this is what the "auto" option is for. ' .
                'With "Auto", WebP Express will try converting to both encodings ' .
                'and select one that resulted in the smallest file.</p>' .
                '<p>Note that Gd and Ewww neither supports "Lossless" or "Auto". ' .
                'Gd can only produce lossy, and will simply do that. ' .
                'Ewww can not be configured to use a certain encoding, but automatically chooses lossless encoding for PNGs and lossy for JPEGs.' .
                '</p>' .
                '<p>You can read more about the option <a target="_blank" href="https://github.com/rosell-dk/webp-convert/blob/master/docs/v2.0/converting/introduction-for-converting.md#auto-selecting-between-losslesslossy-encoding">here</a></p>'
            );?>
        </div>
        <div id="png_quality_lossy_div" class="toggler effect-visibility">
            <label>
                Quality for lossy:
            </label>

            <input type="text" size=3 name="png-quality" value="<?php echo esc_attr($config['png-quality']) ?>" style="text-align:right; padding-left:0px; padding-right:4px; width:34px">
            <?php
                echo helpIcon(
                    '<p>You probably want to set this value a bit higher than the quality for JPEGs. PNGs are often used for icons and graphics, ' .
                    'which perhaps demands a bit more quality than photos, which jpegs often are used for.</p>' .
                    '<p>Note that there is no "Same as PNG" option available here as PNGs are lossless</p>'
                );
            ?>
            <label style="margin-left:10px">
                Alpha quality:
            </label>
            <input type="text" size=3 name="alpha-quality" value="<?php echo esc_attr($config['alpha-quality']) ?>" style="text-align:right; padding-left:0px; padding-right:4px; width:34px">
            <?php
                echo helpIcon(
                    'The alpha quality is the quality of the alpha channel (the tranparency layer). ' .
                    'The option is only relevant for images with alpha channel, and only relevant in lossy encoding. ' .
                    '<p>Note that Gd and Ewww does not support the "Alpha quality" feature. ' .
                    'They simply ignore the option and converts the alpha channel losslessly. ' .
                    '</p>'
                );
            ?>
        </div>
        <div>
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
                    '<p>Note that the near-lossless option only is supported by the <i>Cwebp</i> and <i>Vips</i> conversion methods.</p>' .
                    '<p>Read more about the feature <a target="_blank" href="https://github.com/rosell-dk/webp-convert/blob/master/docs/v2.0/converting/introduction-for-converting.md#near-lossless">here</a></p>'
                );
            ?>
            <div id="png_near_lossless_div" style="display:inline-block; margin-left:10px" class="toggler effect-visibility">
                <label>
                    "Near lossless" quality:
                </label>
                <input type="text" size=3 name="png-near-lossless" value="<?php echo esc_attr($config['png-near-lossless']) ?>" style="text-align:right; padding-left:0px; padding-right:4px; width:34px">
                <?php
                    echo helpIcon(
                        'The level of near-lossless image preprocessing (when trying lossless). ' .
                        'You can think of it as "quality" for lossless. The range is 0 (maximum preprocessing) to 100 (no preprocessing). ' .
                        '<a href="https://groups.google.com/a/webmproject.org/forum/#!topic/webp-discuss/0GmxDmlexek">Read this</a> to get an informed opinion about appropriate setting.'
                    );
                ?>
            </div>
        </div>
    </td>
</tr>
