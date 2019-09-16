<tr>
    <th scope="row">Scope<?php
        echo helpIcon(
            '<p>This setting determines which folders WebP Express is operational in. ' .
            'If for example "Uploads only" is selected, WebP Express will only convert the upload images and only put ' .
            'an <i>.htaccess</i> file in the <i>uploads</i> folder (if needed). Also, <i>Alter HTML</i> will limit itself to that area.' .
            '</p>' .
            '<p>The "All content" setting will work on <i>uploads</i>, <i>themes</i>, <i>plugins</i> - anything in the "wp-content" ' .
            '(or whatever it has been renamed to). It will work on uploads, even if the uploads folder has been ' .
            'configured to reside outside of wp-content - and on <i>plugins<i>, even if plugins has been moved.</p>'
        );
    ?></th>
    <td>
        <select name="scope" id="scope">
            <?php
            /*webpexpress_selectBoxOptions($config['destination-structure'], [
                'doc-root' => 'Document root',
                'image-roots' => 'Image roots',
            ]);
            */
            $imageTypes = $config['scope'];
            sort($imageTypes, SORT_STRING);
            $imageTypesString = implode(',', $imageTypes);

            $options = [
                ['uploads', 'Uploads only'],
                ['themes', 'Themes only'],
                ['themes,uploads', 'Uploads and themes'],
                ['plugins,themes,uploads,wp-content', 'All content'],
                ['index,plugins,themes,uploads,wp-content', 'Everything (including wp-admin)'],
            ];

            $otherValidOptions = ['index', 'wp-content'];

            if (in_array($imageTypesString, $otherValidOptions)) {
                $options[] = [$imageTypesString, $imageTypesString];
            }

            $hasSelected = false;
            foreach ($options as list($optionId, $optionName)) {
                $selected = ($imageTypesString == $optionId);
                if ($selected) {
                    $hasSelected = true;
                }
                echo '<option value="' . $optionId . '"' . ($selected ? ' selected' : '') . '>' . $optionName . '</option>';
            }

            if (!$hasSelected) {
                // Non-default option is "selected".
                // This can be done in migrate11.php or user could have edited config.php manually.
                // PS: We can rest assure that scope is sanitized - any invalid rootId is filtered out in Config::fix.
                echo '<option value="' . $imageTypesString . '" selected>' .
                    'Custom: ' . implode(', ', explode(',', $imageTypesString)) . 
                '</option>';
            }
            ?>
        </select>
    </td>
</tr>
