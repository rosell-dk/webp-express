<fieldset class="block">
    <h3>Conversion</h3>
    <!--<p><i>The options here affects the conversion process</i></p>-->
    <table class="form-table">
        <tbody>
            <?php
            include_once 'quality.inc';
            include_once 'metadata.inc';
            include_once 'converters.inc';
            ?>
        </tbody>
    </table>
    <button onclick="openBulkConvertPopup()" class="button button-secondary" type="button">Bulk Convert</button>
    <div id="bulkconvertpopup" style="display:none;">
        <div id="bulkconvertcontent"></div>
    </div>

</fieldset>
