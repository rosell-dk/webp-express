<?php
include_once __DIR__ . '/../../../classes/Paths.php';
use \WebPExpress\Paths;

//$whitelist = $config['web-service']['whitelist'];
//echo '<script>window.whitelist = ' . json_encode($whitelist) . '</script>';
?>
<tr id="share">
    <th scope="row">Enable web service?<?php echo helpIcon(
        'Enabling the web service will allow selected sites to convert webp-images through this site (more options will appear, if you enable)'
    ); ?></th>
    <td>
        <input type="checkbox" id="web_service_enabled" name="web-service-enabled" value="true" <?php
            echo ($config['web-service']['enabled'] ? 'checked="checked"' : '')
        ?>>
        <input type='text' name='whitelist' id='whitelist' value='' style='visibility:hidden; height:0' />
        <div id="whitelist_div"></div>
        <div id="whitelist_properties_popup" class="das-popup">
            <h3 class="hide-in-edit">Authorize website</h3>
            <h3 class="hide-in-add">Edit authorized website</h3>
            <input type="hidden" id="whitelist_uid">
            <input type="hidden" id="whitelist_i">
            <div>
                <label for="whitelist_label">
                    Label
                    <?php echo helpIcon('The label is purely for your own reference'); ?>
                </label>
                <input id="whitelist_label" type="text">
            </div>
            <div>
                <label for="whitelist_ip">
                    IP
                    <?php echo helpIcon('IP to allow access to service. You can use *, ie "212.91.*", or even "*"'); ?>
                </label>
                <input id="whitelist_ip" type="text">
            </div>
            <div>
                <label for="whitelist_api_key">
                    Api key
                    <?php echo helpIcon('Who says api keys must be dull-looking meaningless sequences of random ' .
                    'characters? Here you get to shape your key to your liking. Enter any phrase you want'); ?>
                </label>
                <input id="whitelist_api_key" type="password" class="hide-in-edit">
                <a href="javascript:whitelistChangeApiKey()" class="hide-in-add" style="line-height:34px">Change api key</a>
            </div>
            <div>
                <label for="whitelist_require_api_key_to_be_crypted_in_transfer">
                    Require api-key to be crypted in transfer?
                    <?php echo helpIcon(
                        'If checked, the web service will only accept crypted api keys. Crypting the api-key protects it from being ' .
                        'stolen during transfer. On a few older server setups, clients do not have the capability to crypt');
                    ?>
                </label>
                <input id="whitelist_require_api_key_to_be_crypted_in_transfer" type="checkbox">
            </div>
            <p style="margin-top: 15px">Psst: The endpoint of the web service is: <b><?php
                echo esc_url(Paths::getWebServiceUrl())
            ?></b></p>
            <button id="whitelist_properties_add_button" onclick="whitelistAddWhitelistEntry()" class="hide-in-edit button button-primary" type="button" style="position:absolute; bottom:20px">
                Add
            </button>
            <button id="whitelist_properties_update_button" onclick="whitelistUpdateWhitelistEntry()" class="hide-in-add button button-primary" type="button" style="position:absolute; bottom:20px">
                Update
            </button>
        </div>
    </td>
</tr>
