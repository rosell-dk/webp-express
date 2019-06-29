<div id="imagemagick" style="display:none;">
    <div class="imagemagick converter-options">

      <h3>ImageMagick options</h3>
      <p>This conversion method works by executing imagemagick binary (the 'convert' command).</p>

      <div>
          <label for="imagemagick_use_nice">
              Use nice
              <?php echo helpIcon(
                  'Enabling this option saves system resources at the cost of slightly slower conversion.'
              ); ?>
          </label>
          <input type="checkbox" id="imagemagick_use_nice">
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
