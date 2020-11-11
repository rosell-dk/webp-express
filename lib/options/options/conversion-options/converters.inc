
<tr>
    <th scope="row">
        Conversion method
        <?php
        echo helpIcon(
            'Drag to reorder. The conversion method on top will first be tried. ' .
            'Should it fail, the next will be used, etc. To learn more about the conversion methods, ' .
            '<a target="_blank" href="https://github.com/rosell-dk/webp-convert/blob/master/docs/v1.3/converting/converters.md">Go here</a>'
        ); ?>
    </th>
    <td>
        <input type='text' name='converters' value='' style='visibility:hidden; height:0' />
        <ul id="converters" style="margin-top: -13px"></ul>
        <div id="tc_popup" style="display:none;">
            <div id="tc_content"></div>
        </div>
        <?php
            function webp_express_printUpdateButtons() {
            ?>
                <button onclick="updateConverterOptionsAndSave()" class="button button-primary" type="button">Update and save settings</button>
                <button onclick="updateConverterOptions()" class="button button-secondary" type="button">Update, but do not save yet</button>
                <?php
                //echo '<a href="javascript: tb_remove();">close</a>';
            }

            include 'converter-options/cwebp.php';
            include 'converter-options/vips.php';
            include 'converter-options/gd.php';
            include 'converter-options/imagick.php';
            include 'converter-options/ewww.php';
            include 'converter-options/wpc.php';
            include 'converter-options/imagemagick.php';
            include 'converter-options/graphicsmagick.php';
            include 'converter-options/ffmpeg.php';
        ?>
    </td>
</tr>
