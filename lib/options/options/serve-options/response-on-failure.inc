<?php
// Response on failure
// --------------------
echo '<tr><th scope="row">Response on failure';
echo helpIcon('Determines what to serve in case the image conversion should fail.');
echo '</th><td>';

$fail = $config['fail'];
echo '<select name="fail">';
echo '<option value="original"' . ($fail == 'original' ? ' selected' : '') . '>Original image</option>';
echo '<option value="404"' . ($fail == '404' ? ' selected' : '') . '>404</option>';
echo '<option value="report"' . ($fail == 'report' ? ' selected' : '') . '>Error report (in plain text)</option>';
//echo '<option value="report-as-image"' . ($fail == 'report-as-image' ? ' selected' : '') . '>Error report as image</option>';
echo '</select>';
echo '</td></tr>';
//        echo '<tr><td colspan=2>Determines what the converter should serve, in case the image conversion should fail. For production servers, recommended value is "Original image". For development servers, choose anything you like, but that</td></tr>';
