<tr>
    <th scope="row">
        Bulk convert
        <?php
            echo helpIcon(
                'Click the button to open dialog for bulk converting. PS: The bulk conversion is using the last saved settings.</p>'
            );
        ?>
    </th>
    <td>
        <div id="conversionlog" class="das-popup">
            <div id="conversionlog_content">
            </div>
            <button onclick="closeDasPopup()" class="button button-primary" type="button" style="position:absolute; bottom:20px">
                Close
            </button>
        </div>
        <div>
            <button onclick="openBulkConvertPopup()" class="button button-secondary" type="button">Bulk Convert</button>
            <div id="bulkconvertpopup" style="display:none;">
                <div id="bulkconvertcontent"></div>
                <div id="bulkconvertlog"></div>
            </div>
            <button onclick="openDeleteConvertedFilesPopup()" class="button button-secondary" type="button">Delete converted files</button>
            <div id="purgecachepopup" style="display:none;">
                <div id="purgecachecontent"></div>
            </div>
        </div>
    </td>
</tr>
