<div id="wpc" style="display:none;">
    <div class="wpc converter-options">
      <h3>Remote WebP Express</h3>
      Use a WebP Express installed on another Wordpress site to convert. Remote WepP Express is based
      on <a href="https://github.com/rosell-dk/webp-convert-cloud-service" target="blank">WPC</a>,
      and you can use it to connect to WPC as well.

      <?php
      if ((!extension_loaded('curl')) || (!function_exists('curl_init'))) {
          echo '<p><b style="color:red">Your server does not have curl installed. Curl is required!</b></p>';
      }
      ?>

      <h3>Options</h3>
      <!--
        <div>
            <label for="wpc_web_services">Web Services</label>
            <div style="display:inline-block">
                <div id="wpc_web_services_div"></div>
                <button type="button" id="wpc_web_services_request" onclick="openWpcConnectPopup()" class="button button-secondary" >Add web service</button>
            </div>
        </div>
    -->

    <div id="wpc_api_version_div">
        <label for="wpc_api_version">
            Api version
            <?php echo helpIcon('Select 1, if connecting to a remote webp-express. Api 0 was never used with this plugin, and should only be used to connect to webp-convert-cloud-service v.0.1 instances'); ?>
        </label>
        <select id="wpc_api_version" onchange="wpcApiVersionChanged()">
            <option value="0">0</option>
            <option value="1">1</option>
        </select>
    </div>

      <div>
          <label for="wpc_api_url">
              URL
              <?php echo helpIcon('The endpoint of the web service. Copy it from the remote setup.'); ?>
          </label>
          <input type="text" id="wpc_api_url" placeholder="Url to your Remote WebP Express" autocomplete="off">
      </div>

      <div id="wpc_secret_div">
          <label for="wpc_secret">
              Secret
              <?php echo helpIcon('Must match the one set up in webp-convert-cloud-service v0.1'); ?>
          </label>
          <input type="text" id="wpc_secret" placeholder="" autocomplete="off">
      </div>

      <div id="wpc_api_key_div">
          <label id="wpc_api_key_label_1" for="wpc_api_key">
              Secret
              <?php echo helpIcon('The secret set up on the wpc server. Copy that.'); ?>
          </label>
          <label id="wpc_api_key_label_2" for="wpc_api_key">
              Api key
              <?php echo helpIcon('The API key is set up on the remote. Copy that.'); ?>
          </label>
          <input id="wpc_new_api_key" type="password" autocomplete="off">
          <a id="wpc_change_api_key" href="javascript:wpcChangeApiKey()">
              Click to change
          </a>
      </div>

      <div id="wpc_crypt_api_key_in_transfer_div">
          <label for="wpc_crypt_api_key_in_transfer">
              Crypt api key in transfer?
              <?php echo helpIcon('If checked, the api key will be crypted in requests. Crypting the api-key protects it from being stolen during transfer.'); ?>
          </label>
          <input id="wpc_crypt_api_key_in_transfer" type="checkbox">
      </div>

      <?php
      /*
      Removed (#243)
      if (!$canDetectQuality) {
          printAutoQualityOptionForConverter('wpc');
      }*/
      ?>

      <p>
        <b>Psst. The IP of your website is: <?php echo $_SERVER['SERVER_ADDR']; ?>.</b>
    </p>
    <?php webp_express_printUpdateButtons() ?>
    </div>
</div>
