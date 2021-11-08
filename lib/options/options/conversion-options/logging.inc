<tr>
    <th scope="row">
        Enable logging
        <?php
            echo helpIcon(
                '<p>Store conversion results in log files. ' .
                'This can be useful in order to find out what went wrong in case a conversion failed or ' .
                'the result is poor quality. ' .
                'The log files reside in <i>wp-content/webp-express/log/conversions/</i>. ' .
                'In not too far a future, the conversion logs will be accessible in the File Manager too.' .
                '</p>'
            );
        ?>
    </th>
    <td>
        <input type="checkbox" id="enable_logging" name="enable-logging" value="true" <?php echo ($config['enable-logging'] ? 'checked="checked"' : '') ?> >
        &nbsp;
        <button onclick="openDeleteLogFilesPopup()" class="button button-secondary" type="button">Delete log files</button>
        <div id="purgelogpopup" style="display:none;">
            <div id="purgelogcontent"></div>
        </div>

    </td>
</tr>
