<?php

//$maxQuality = get_option('webp_express_max_quality');
$cacheControl = $config['cache-control'];
$cacheControlCustom = $config['cache-control-custom'];

echo '<tr><th scope="row">Caching';
echo helpIcon(
    'Controls the cache-control header for the converted image. ' .
    'This header is only sent when a converted image can be successfully delivered (either existing, or new ' .
    'conversion). In case of failure, headers will be sent to prevent caching.');
echo '</th><td>';
echo '<select id="cache_control_select" name="cache-control">';
echo '<option value="no-header"' . ($cacheControl == 'no-header' ? ' selected' : '') . '>Do not set Cache-Control header</option>';
echo '<option value="one-second"' . ($cacheControl == 'one-second' ? ' selected' : '') . '>One second</option>';
echo '<option value="one-minute"' . ($cacheControl == 'one-minute' ? ' selected' : '') . '>One minute</option>';
echo '<option value="one-hour"' . ($cacheControl == 'one-hour' ? ' selected' : '') . '>One hour</option>';
echo '<option value="one-day"' . ($cacheControl == 'one-day' ? ' selected' : '') . '>One day</option>';
echo '<option value="one-week"' . ($cacheControl == 'one-week' ? ' selected' : '') . '>One week</option>';
echo '<option value="one-month"' . ($cacheControl == 'one-month' ? ' selected' : '') . '>One month</option>';
echo '<option value="one-year"' . ($cacheControl == 'one-year' ? ' selected' : '') . '>One year</option>';
echo '<option value="custom"' . ($cacheControl == 'custom' ? ' selected' : '') . '>Custom Cache-Control header</option>';
echo '</select><br>';
echo '<input type="text" id="cache_control_custom" name="cache-control-custom" value="' . $cacheControlCustom . '">';
echo '</td></tr>';
