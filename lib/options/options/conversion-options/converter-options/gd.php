<div id="gd" style="display:none;">
    <div class="gd converter-options">
      <h3>Gd options</h3>
      <div>
          <label for="gd_skip_pngs">Skip PNGs</label>
          <input type="checkbox" id="gd_skip_pngs">
          <br>
          <p>
          You can choose to skip PNG's for Gd (which means the next working and active converter in the stack will handle it,
          if there is any). There can be two reasons to do that:
              <ul class="with-bullets">
                  <li>In our first implementation, Gd had problems with transparency. This should however be solved now. </li>
                  <li>Gd does not compress PNGs very effeciently</li>
              </ul>
          </p>

      </div>
      <br>
      <?php webp_express_printUpdateButtons() ?>
    </div>
</div>
