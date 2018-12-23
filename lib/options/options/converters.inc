<?php
// Converters
// --------------------

echo '<tr><th scope="row">Conversion method';
echo helpIcon('Drag to reorder. The conversion method on top will first be tried. ' .
    'Should it fail, the next will be used, etc. To learn more about the conversion methods, <a target="_blank" href="https://github.com/rosell-dk/webp-convert/blob/master/docs/converters.md">Go here</a>');

echo '</th><td>';

//$converters = $config['converters'];
//echo '<script>window.converters = ' . json_encode($converters) . '</script>';
//echo '<script>window.defaultConverters = ' . json_encode($defaultConverters) . '</script>';

echo "<input type='text' name='converters' value='' style='visibility:hidden; height:0' />";

// https://premium.wpmudev.org/blog/handling-form-submissions/


?>
        <?php
/*
$localConverters = ['cwebp', 'imagick', 'gd'];
        $testResult = WebPExpressHelpers::testConverters($localConverters);
        //print_r($testResult);

        if ($testResult['numOperationalConverters'] == 0) {
            echo 'Unfortunately, your server is currently not able to convert to webp files by itself. You will need to set up a cloud converter.<br><br>';
            foreach ($testResult['results'] as $result) {
                echo $result['converter'] . ':' . $result['message'] . '<br>';
            }
        } else {
            //echo 'Your server is able to convert webp files by itself.';
        }
        if ($testResult['numOperationalConverters'] == 1) {
            //
        }
*/


/*
http://php.net/manual/en/function.set-include-path.php

//exec('/usr/sbin/getsebool -a', $output6, $returnCode5); // ok
//echo 'All se bools: ' . print_r($output6, true) . '. Return code:' . $returnCode5;
*/

//echo '<h2>Conversion methods to try</h2>';
$dragIcon = '<svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="17px" height="17px" viewBox="0 0 100.000000 100.000000" preserveAspectRatio="xMidYMid meet"><g transform="translate(0.000000,100.000000) scale(0.100000,-0.100000)" fill="#444444" stroke="none"><path d="M415 920 l-80 -80 165 0 165 0 -80 80 c-44 44 -82 80 -85 80 -3 0 -41 -36 -85 -80z"/><path d="M0 695 l0 -45 500 0 500 0 0 45 0 45 -500 0 -500 0 0 -45z"/><path d="M0 500 l0 -40 500 0 500 0 0 40 0 40 -500 0 -500 0 0 -40z"/><path d="M0 305 l0 -45 500 0 500 0 0 45 0 45 -500 0 -500 0 0 -45z"/><path d="M418 78 l82 -83 82 83 83 82 -165 0 -165 0 83 -82z"/></g></svg>';

/*echo '<p><i>Drag to reorder. The conversion method on top will first be tried. ';
echo 'Should it fail, the next will be used, etc.<br>';
echo 'To learn more about the conversion methods, ';
echo '<a target="_blank" href="https://github.com/rosell-dk/webp-convert/blob/master/docs/converters.md">Go here</a></i></p>';
*/
// https://github.com/RubaXa/Sortable

// Empty list of converters. The list will be populated by the javascript

function webp_express_printUpdateButtons() {
?>
    <button onclick="updateConverterOptionsAndSave()" class="button button-primary" type="button">Update and save settings</button>
    <button onclick="updateConverterOptions()" class="button button-secondary" type="button">Update, but do not save yet</button>
    <?php
    //echo '<a href="javascript: tb_remove();">close</a>';
}
echo '<ul id="converters" style="margin-top: -13px"></ul>';

include 'converter-options/cwebp.php';
include 'converter-options/gd.php';
include 'converter-options/imagick.php';
include 'converter-options/ewww.php';
include 'converter-options/wpc.php';
include 'converter-options/imagickbinary.php';
?>
</td></tr>
