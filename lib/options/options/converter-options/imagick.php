<div id="imagick" style="display:none;">
    <div class="imagick converter-options">
      <h3>Imagick options</h3>
      <?php
      if ($canDetectQuality) {
          echo '<div class="info">imagick has no special options.</div>';
      } else {
          echo '<br>';
          printAutoQualityOptionForConverter('imagick');
      }
      ?>
      <!--
      <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update</button>
  -->
      <!-- <a href="javascript: tb_remove();">close</a> -->
    </div>
</div>
