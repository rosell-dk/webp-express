<?php
echo '<tr><th scope="row">Response on success';
echo helpIcon(
    '<p>Determines what to serve when conversion is a success. If you are using the Cache Enabler plugin, set to "Original image", ' .
    'otherwise you would normally set it to "Converted image".</p>' .
    '<p>If set to "Converted image", a Vary:Accept header will be sent to indicate that the response depends on the Accept header ' .
    '(which indicates if a browser supports webp images or not)</p><p>If set to "Original image", make sure to disable the "Redirect ' .
    'directly to converted image when available" option in the Redirect rules</p>');
echo '</th><td>';

$successResponse = $config['success-response'];
echo '<select name="success-response">';
echo '<option value="original"' . ($successResponse == 'original' ? ' selected' : '') . '>Original image</option>';
echo '<option value="converted"' . ($successResponse == 'converted' ? ' selected' : '') . '>Converted image</option>';
echo '</select>';
echo '</td></tr>';
