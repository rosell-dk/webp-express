<div id="gd" style="display:none;">
    <div class="gd converter-options">
      <h3>Gd options</h3>
      <div>
          <label for="gd_skip_pngs">Skip PNGs</label>
          <input type="checkbox" id="gd_skip_pngs">
          <br>
          You can choose to skip PNG's for Gd (which means the next working and active converter in the stack will handle it, if there is any).
          In our first implementation, Gd had problems with transparency. This is however solved now.          
      </div>
      <br>
      <?php webp_express_printUpdateButtons() ?>
    </div>
</div>
