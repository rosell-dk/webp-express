<div id="imagickbinary" style="display:none;">
    <div class="imagickbinary converter-options">

      <h3>Imagick binary options</h3>
      <p>This conversion method works by executing imagick binary (the 'convert' command).</p>

      <div>
          <label for="imagickbinary_use_nice">Use nice</label>
          <input type="checkbox" id="imagickbinary_use_nice">
          <br>Enabling this option saves system resources at the cost of slightly slower conversion
      </div>

      <?php
      if (!$canDetectQuality) {
          printAutoQualityOptionForConverter('imagickbinary');
      }
      ?>
      <!--
      <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update</button>
  -->
      <!-- <a href="javascript: tb_remove();">close</a> -->
      <?php webp_express_printUpdateButtons() ?>
    </div>
</div>
