<div id="ffmpeg" style="display:none;">
    <div class="ffmpeg converter-options">
        <h3>ffmpeg options</h3>
        <p>This conversion method works by executing ffmpeg binary.</p>

        <div>
            <label for="ffmpeg_use_nice">
                Use nice
                <?php echo helpIcon(
                    'Enabling this option saves system resources at the cost of slightly slower conversion.'
                ); ?>
            </label>
            <input type="checkbox" id="ffmpeg_use_nice">
        </div>
        <div>
            <label for="ffmpeg_method">Method (0-6)</label>
            <input type="text" size="2" id="ffmpeg_method">
            <br>This parameter controls the trade off between encoding speed and the compressed file size and quality.
            Possible values range from 0 to 6. 0 is fastest. 6 results in best quality.
        </div>
        <br>
        <?php
        /*
        Removed (#243)
        if (!$canDetectQuality) {
            printAutoQualityOptionForConverter('imagemagick');
        }*/
        ?>
        <!--
        <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update</button>
    -->
        <!-- <a href="javascript: tb_remove();">close</a> -->
        <?php webp_express_printUpdateButtons() ?>
    </div>
</div>
