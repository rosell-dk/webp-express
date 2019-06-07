<div id="vips" style="display:none;">
    <div class="vips converter-options">
      <h3>Vips options</h3>
      <div>
          <label for="vips_smart_subsample">
              Smart subsample
              <?php echo helpIcon(
                  'According to <a target="_blank" href="https://jcupitt.github.io/libvips/API/current/VipsForeignSave.html#vips-webpsave">the docs</a>, ' .
                  'this option "enables high quality chroma subsampling".'
              ); ?>
          </label>
          <input type="checkbox" id="vips_smart_subsample">
      </div>
      <div>
          <label for="vips_preset">
              Preset
              <?php echo helpIcon(
                  'Using a preset will set many of the other options to suit a particular type of source material. ' .
                  'It even overrides them. It does however not override the quality option.'
              ); ?>

          </label>
          <select id="vips_preset">
              <?php
              webpexpress_selectBoxOptions('default', [
                  'none' => 'Do not use a preset',
                  'default' => 'Default',
                  'photo' => 'Photo',
                  'picture' => 'Picture',
                  'drawing' => 'Drawing',
                  'icon' => 'Icon',
                  'text' => 'Text'
              ]);
              ?>
          </select>
          <br>
      </div>

      <br>
      <?php webp_express_printUpdateButtons() ?>
    </div>
</div>
