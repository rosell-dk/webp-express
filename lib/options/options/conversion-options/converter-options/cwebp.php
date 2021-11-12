<div id="cwebp" style="display:none;">
    <div class="cwebp converter-options">
      <h3>cweb options</h3>
      <div>
          <label for="cwebp_use_nice">Use nice</label>
          <input type="checkbox" id="cwebp_use_nice">
          <br>Enabling this option saves system resources at the cost of slightly slower conversion
      </div>
      <div>
          <label for="cwebp_try_common_system_paths">Try to execute cweb binary at common locations</label>
          <input type="checkbox" id="cwebp_try_common_system_paths">
          <br>If checked, we will look for binaries in common locations, such as <i>/usr/bin/cwebp</i>
      </div>
      <div>
          <label for="cwebp_try_supplied_binary">Try precompiled cwebp</label>
          <input type="checkbox" id="cwebp_try_supplied_binary">
          <br>This plugin ships with precompiled cweb binaries for different platforms. If checked, and we have a precompiled binary for your OS, we will try to exectute it
      </div>
      <div>
         <label for="cwebp_skip_these_precompiled_binaries">Skip these precompiled cwebp</label>
         <input type="text" size="40" id="cwebp_skip_these_precompiled_binaries" style="width:100%">
         <br>To skip precompiled binaries that are known not to work on current system (check the conversion log). 
         This will cut down on conversion time. Separate values with comma.
      </div>
      <div>
          <label for="cwebp_method">Method (0-6)</label>
          <input type="text" size="2" id="cwebp_method">
          <br>This parameter controls the trade off between encoding speed and the compressed file size and quality.
          Possible values range from 0 to 6. 0 is fastest. 6 results in best quality.
      </div>
      <div>
          <label for="cwebp_set_size">Set size option (and ignore quality option)</label>
          <input type="checkbox" id="cwebp_set_size">
          <br>This option activates the size option below.
          <?php
          if ($canDetectQuality) {
              echo 'As you have quality detection working on your server, it is probably best to use that, rather ';
              echo 'than the "size" option. Using the size option takes more ressources (it takes about 2.5 times ';
              echo 'longer for cwebp to do a a conversion with the size option than the quality option). Long ';
              echo 'story short, you should probably <i>not</i> activate the size option.';
          } else {
              echo 'As you do not have quality detection working on your server, it is probably a good ';
              echo 'idea to use the size option to avoid making conversions with a higher quality setting ';
              echo 'than the source image. ';
              echo 'Beware, though, that cwebp takes about 2.5 times longer to do a a conversion with the size option set.';
          }
          ?>
      </div>
      <div>
          <label for="cwebp_size_in_percentage">Size (in percentage of source)</label>
          <input type="text" size="2" id="cwebp_size_in_percentage">
          <br>Set the cwebp should aim for, in percentage of the original.
          Usually cwebp can reduce to ~45% of original without loosing quality.
      </div>
      <div>
          <label for="cwebp_command_line_options">Extra command line options</label><br>
          <input type="text" size="40" id="cwebp_command_line_options" style="width:100%">
          <br>This allows you to set any parameter available for cwebp in the same way as
          you would do when executing <i>cwebp</i>. As a syntax example, you could ie. set it to
          "-low_memory -af -f 50 -sharpness 0 -mt -crop 10 10 40 40" (do not include the quotes).
          Read more about all the available parameters in
          <a target="_blank" href="https://developers.google.com/speed/webp/docs/cwebp">the docs</a>
      </div>
      <br>
      <?php webp_express_printUpdateButtons() ?>
    </div>
</div>
