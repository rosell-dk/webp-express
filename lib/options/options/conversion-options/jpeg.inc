<?php
/*

jpg
    Encoding:  Lossy / Auto
    Quality for lossy:  Auto / specific
    Near-Lossless quality (0-100)  (default: 60)

png
    Encoding:  Always lossless / Auto
    Quality for lossy (0-100)
    Near-Lossless quality (0-100)  (default: 70)
    Alhpa quality (0-100)

*/
// Quality - jpeg
// --------------------

//$canDetectQuality = false;
?>

<tr>
    <th scope="row" colspan=1>
        Jpeg
        <?php echo helpIcon(
            'The "jpeg" settings applies when the image being converted is a jpeg.'
        );?>
    </th>
    <td>
        <label>
            Encoding:
        </label>
        <select name="jpeg-encoding">
            <?php
            webpexpress_selectBoxOptions($config['jpeg-encoding'], [
                'lossy' => 'Lossy',
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
<tr colspan=2>
    <th></th>
    <td>
        <label>
            Quality for lossy:
        </label>
        <select id="quality_auto_select" name="quality-auto">
            <option value="auto_on" <?php echo ($config['quality-auto'] ? 'selected' : ''); ?>>Same as the jpeg</option>
            <option value="auto_off" <?php echo (!$config['quality-auto'] ? 'selected' : ''); ?>>Fixed quality</option>
        </select>
        <?php echo helpIcon(
                '<p>If you select the "Same as the jpeg" option, WebP Express will try to determine the quality of ' .
                'the jpeg and use that for the webp - unless the quality is higher than the limit entered, in which ' .
                'case the limit is used.</p>' .
                '<p>Ouality detection requires imagick or gmagick (not neccessarily compiled with webp). ' .
                'In case quality detection is not available, the fallback quality is used.</p>' .
                (
                    $canDetectQuality ?
                    '<p><i>Quality detection is working, btw :)</i></p>' :
                    '<p><b>Quality detection is not currently available on your system.</b> ' .
                    ((extension_loaded('imagick') && class_exists('\\Imagick')) ?
                        'You have imagick, but you need a newer version (You need PECL >= 2.2.2). ' : ''
                    ) .
                    'However, you can still have it by using the <i>Remote WebP Express</i> conversion method (the request for ' .
                    'using same quality as the jpeg, as well as the <i>limit</i> and <i>fallback</i> settings will be forwarded to the remote)' .
                    '</p>'
                )

            );
            /*
            if ($canDetectQuality) {
                echo helpIcon('All converted images will be encoded with this quality');
            } else {
                echo helpIcon('All converted images will be encoded with this quality. ' .
                    'For Remote WebP Express and Imagick, you however have the option to use override this, and use ' .
                    '"auto". With some setup, you can get quality detection working and you will then be able to set ' .
                    'quality to "auto" generally. For that you either need to get the imagick extension running ' .
                    '(PECL >= 2.2.2) or exec() rights and either imagick or gmagick installed.'
                );
            }
            */
        ?>
        <div id="max_quality_div" style="margin-left:10px">
            <label>
                Limit:
            </label>
            <input type="text" size=3 id="max_quality" name="max-quality" value="<?php echo $config['max-quality']; ?>" style="text-align:right; padding-left:0px; padding-right:4px; width:34px">
            <?php echo helpIcon(
                'Quality is expensive byte-wise. For most websites, more than 80 is a waste of bytes. ' .
                'This option allows you to limit the quality to whatever is lowest: ' .
                'the quality of the jpeg or the limit entered here. Recommended value: Somewhere between 50-85'
            );?>
            <label style="display:inline-block; margin-left:10px">
                Fallback:
            </label>
            <input type="text" size=3 name="quality-fallback" value="<?php echo $config['quality-specific'] ?>" style="text-align:right; padding-left:0px; padding-right:4px; width:34px">
            <?php
                echo helpIcon(
                    'Fallback quality in case quality detection is not available or should fail for some reason (which has yet to be seen). ' .
                    (
                        !$canDetectQuality ?
                        'You have quality detection working, btw, so this setting will probably never be used' :
                        '<i>You do not have quality detection working, btw</i> - which means that all conversions will have the fixed quality entered here'
                    )
                );
            ?>
        </div>

        <div id="quality_specific_div">
            <input type="text" size=3 name="quality-specific" value="<?php echo $config['quality-specific'] ?>" style="text-align:right; padding-left:0px; padding-right:4px; width:34px">
            <?php
                echo helpIcon('Enter number (0 - 100)');
            ?>
        </div>

    </td>
</tr>
<tr colspan=2>
    <th></th>
    <td>
        <label>
            Quality for lossless:
        </label>
        <select id="jpeg_enable_near_lossless" name="jpeg-enable-near-lossless">
            <option value="on" <?php echo ($config['jpeg-enable-near-lossless'] ? 'selected' : ''); ?>>Use "near-lossless" preprocessing</option>
            <option value="off" <?php echo (!$config['jpeg-enable-near-lossless'] ? 'selected' : ''); ?>>100% lossless</option>
        </select>
        <?php
            echo helpIcon(
                '<p>What? Lossless is lossless, right?. Well, that depends on how you look at it. ' .
                'The webp conversion library has this nifty option called "near lossless preprocessing". The preproccesing manipulates ' .
                'the image before encoding it using lossless encoding in order to help compressibility.</p>' .
                '<p>Note: The near-lossless option is supported by the <i>Cwebp</i> and <i>Vips</i> conversion methods.</p>' .
                '<p>Read more about the feature <a target="_blank" href="https://github.com/rosell-dk/webp-convert/blob/master/docs/v2.0/converting/introduction-for-converting.md#near-lossless">here</a></p>'
            );
        ?>
        <div id="jpeg_near_lossless_div" style="display:inline-block; margin-left:10px">
            <label>
                Amount:
            </label>
            <input type="text" size=3 name="jpeg-near-lossless" value="<?php echo $config['jpeg-near-lossless'] ?>" style="text-align:right; padding-left:0px; padding-right:4px; width:34px">
            <?php
                echo helpIcon(
                    'The level of near-lossless image preprocessing (when trying lossless). Enter a number (0-100). ' .
                    'You can think of it as "quality" for lossless. 100 is truly lossless. 60 is a fine comprimize between compression and quality.'
                );
            ?>
        </div>

        </td>
</tr>
