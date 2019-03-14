<tr>
    <th scope="row">
        Bulk convert
        <?php
            echo helpIcon(
                'Click the button to open dialog for bulk converting. '
            );
        ?>
    </th>
    <td>
        <div>
            <button onclick="openBulkConvertPopup()" class="button button-secondary" type="button">Bulk Convert</button>
            <div id="bulkconvertpopup" style="display:none;">
                <div id="bulkconvertcontent"></div>
            </div>
        </div>
    </td>
</tr>
