<?php

// Maybe go away from using Settings API ?
// https://wpshout.com/wordpress-options-page/

add_thickbox();

include_once 'helpers.php';

add_action('admin_enqueue_scripts', function () {
    // https://github.com/RubaXa/Sortable
    wp_register_script('sortable', plugins_url('../js/sortable.min.js', __FILE__), [], '1.9.0');
    wp_enqueue_script('sortable');

    wp_register_script(
        'webp-express-options-page',
        plugins_url('../js/webp-express-options-page.js', __FILE__),
        ['sortable'],
        '1.0.4'
    );
    wp_enqueue_script('webp-express-options-page');

    wp_add_inline_script('webp-express-options-page', 'window.webpExpressPaths = ' . json_encode(WebPExpressHelpers::calculateUrlsAndPaths()) . ';');
    //wp_add_inline_script('webp-express-options-page', 'window.converters = [{"converter":"imagick","id":"imagick"},{"converter":"cwebp","id":"cwebp"},{"converter":"gd","id":"gd"}];');

    //wp_add_inline_script('webp-express-options-page', 'window.converters = [{"converter":"imagick","id":"imagick"},{"converter":"cwebp","id":"cwebp"},{"converter":"gd","id":"gd"}];');
    // ,{"converter":"wpc","options":{"url":"http://","secret":"banana"},"id":"wpc"}



    wp_register_style(
        'webp-express-options-page-css',
        plugins_url('../css/webp-express-options-page.css', __FILE__),
        null,
        '1.0.5'
    );
    wp_enqueue_style('webp-express-options-page-css');
});



add_action('admin_init', 'webp_express_option_group_init');


function webp_express_option_group_init()
{
/*
    register_setting(
        'webp_express_option_group', // A settings group name. Must exist prior to the register_setting call. This must match the group name in settings_fields()
        'webp_express_options' //The name of an option to sanitize and save.
    );*/
    register_setting(
        'webp_express_option_group', // A settings group name. Must exist prior to the register_setting call. This must match the group name in settings_fields()
        'webp_express_quality', //The name of an option to sanitize and save.
        [
            'type' => 'integer',
            'default' => '70',
            'sanitize_callback' => 'sanitize_text_field',
        ]
    );
    register_setting(
        'webp_express_option_group',
        'webp_express_method',
        [
            'type' => 'integer',
            'default' => '6',
            'sanitize_callback' => 'sanitize_text_field',
        ]
    );
    register_setting(
        'webp_express_option_group',
        'webp_express_failure_response',
        [
            'type' => 'string',
            'default' => 'original',
            'sanitize_callback' => 'sanitize_text_field',
        ]
    );
    register_setting(
        'webp_express_option_group',
        'webp_express_converters',
        [
            'type' => 'string',
            // TODO: test on new installation
            'default' => '[{"converter":"cwebp","id":"cwebp"},{"converter":"wpc","id":"wpc"},{"converter":"gd","id":"gd"},{"converter":"imagick","id":"imagick"}]',
            //'sanitize_callback' => 'sanitize_text_field',
        ]
    );


    add_settings_section('webp_express_conversion_options_section', 'Conversion options', function () {
        //echo 'here you set conversion options';
    }, 'webp_express_settings_page');

    add_settings_field('webp_express_quality_id', 'Quality (0-100)', function () {
        $quality = get_option('webp_express_quality');
        echo "<input type='text' name='webp_express_quality' value='" . $quality . "' />";
    }, 'webp_express_settings_page', 'webp_express_conversion_options_section');

    add_settings_field('webp_express_method_id', 'Method (0-6)', function () {
        $method = get_option('webp_express_method');
        echo "<input type='text' name='webp_express_method' value='" . $method . "' />";
        echo '<p>When higher values are used, the encoder will spend more time inspecting additional encoding possibilities and decide on the quality gain. Supported by cwebp, wpc and imagick</p>';
    }, 'webp_express_settings_page', 'webp_express_conversion_options_section');

    add_settings_field('webp_express_failure_response', 'Response on failure', function () {
        $failureResponse = get_option('webp_express_failure_response');
        echo '<select name="webp_express_failure_response">';
        echo '<option value="original"' . ($failureResponse == 'original' ? ' selected' : '') . '>Original image</option>';
        echo '<option value="404"' . ($failureResponse == '404' ? ' selected' : '') . '>404</option>';
        echo '<option value="report"' . ($failureResponse == 'report' ? ' selected' : '') . '>Error report (in plain text)</option>';
        echo '<option value="report-as-image"' . ($failureResponse == 'report-as-image' ? ' selected' : '') . '>Error report as image</option>';
        echo '</select>';
        echo '<p>Determines what the converter should serve, in case the image conversion should fail. For production servers, recommended value is "Original image". For development servers, recommended value is anything but that</p>';
    }, 'webp_express_settings_page', 'webp_express_conversion_options_section');

/*
    public static $CONVERTED_IMAGE = 1;
    public static $ORIGINAL = -1;
    public static $HTTP_404 = -2;
    public static $REPORT_AS_IMAGE = -3;
    public static $REPORT = -4;*/

    add_settings_field('webp_express_converters', '', function () {
        $converters = get_option('webp_express_converters');
        echo '<script>window.converters = ' . get_option('webp_express_converters') . '</script>';

        echo "<input type='text' name='webp_express_converters' value='' />";
    }, 'webp_express_settings_page', 'webp_express_conversion_options_section');
}


/* Settings Page Content */
function webp_express_settings_page_content()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'yasr'));
    }
    ?>
    <div class="wrap">
        <h2>WebP Express Settings</h2>

        <form action="options.php" method="post">
            <?php
            settings_fields('webp_express_option_group');
            do_settings_sections('webp_express_settings_page');

            //error_reporting(E_ALL);
            //ini_set('display_errors', 1);


            //echo '<pre>' . print_r(WebPExpressHelpers::calculateUrlsAndPaths(), true) . '</pre>';

            $localConverters = ['cwebp', 'imagick', 'gd'];

/*
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
            $extraConverters = [
                [
                    'converter' => 'ewww',
                    'options' => array(
                        'key' => 'your api key here',
                    ),
                ]
            ];

            $converters = [
                [
                    'converter' => 'cwebp',
                ],
                [
                    'converter' => 'imagick',
                ],
                [
                    'converter' => 'gd',
                ],
                [
                    'converter' => 'ewww',
                    'options' => [
                        'key' => 'your api key here',
                    ],
                ]
            ];

            echo '<h2>Converters</h2>';
            $dragIcon = '<svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="17px" height="17px" viewBox="0 0 100.000000 100.000000" preserveAspectRatio="xMidYMid meet"><g transform="translate(0.000000,100.000000) scale(0.100000,-0.100000)" fill="#444444" stroke="none"><path d="M415 920 l-80 -80 165 0 165 0 -80 80 c-44 44 -82 80 -85 80 -3 0 -41 -36 -85 -80z"/><path d="M0 695 l0 -45 500 0 500 0 0 45 0 45 -500 0 -500 0 0 -45z"/><path d="M0 500 l0 -40 500 0 500 0 0 40 0 40 -500 0 -500 0 0 -40z"/><path d="M0 305 l0 -45 500 0 500 0 0 45 0 45 -500 0 -500 0 0 -45z"/><path d="M418 78 l82 -83 82 83 83 82 -165 0 -165 0 83 -82z"/></g></svg>';

            echo '<p><i>Drag to reorder. The converter on top will be used. Should it fail, the next will be used, etc</i></p>';
            // https://github.com/RubaXa/Sortable

            // Empty list of converters. The list will be populated by the javascript
            echo '<ul id="converters"></ul>';
            ?>
            <div id="cwebp" style="display:none;">
                <div class="cwebp converter-options">
                  <h3>cwebp options</h3>
                  <div class="info">
                      This converter works by executing cwebp binary by google.
                      <br><br>Enabling "use nice" saves system resources at the cost of slightly slower conversion
                  </div>
                  <div>
                      <label for="cwebp_use_nice">Use nice</label>
                      <input type="checkbox" id="cwebp_use_nice">

                  </div>
                  <br>
                  <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update</button>
                  <!-- <a href="javascript: tb_remove();">close</a> -->
                </div>
            </div>
            <div id="gd" style="display:none;">
                <div class="ewww converter-options">
                  <h3>Gd options</h3>
                  <div class="info">
                      Gd neither supports copying metadata nor exposes any WebP options. Lacking the option to set lossless encoding results in poor encoding of PNGs - the filesize is generally much larger than the original. For this reason, the converter defaults to skip PNG's.
                  </div>
                  <div>
                      <label for="gd_skip_pngs">Skip PNGs</label>
                      <input type="checkbox" id="gd_skip_pngs">
                  </div>
                  <br>
                  <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update</button>
                  <!-- <a href="javascript: tb_remove();">close</a> -->
                </div>
            </div>
            <div id="imagick" style="display:none;">
                <div class="ewww converter-options">
                  <h3>Imagick options</h3>
                  <div class="info">
                      imagick has no special options.
                  </div>
                  <br>
                  <!--
                  <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update</button>
              -->
                  <!-- <a href="javascript: tb_remove();">close</a> -->
                </div>
            </div>
            <div id="ewww" style="display:none;">
                <div class="ewww converter-options">
                  <h3>Ewww options</h3>
                  <div>
                      <label for="ewww_key">Key</label>
                      <input type="text" id="ewww_key" placeholder="Your API key here">
                  </div>
                  <br>
                  <h4>Fallback (optional)</h4>
                  <div>
                      <label for="ewww_key_2">key</label>
                      <input type="text" id="ewww_key_2" placeholder="In case the first one expires...">
                  </div>
                  <br>
                  <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update</button>
                  <!-- <a href="javascript: tb_remove();">close</a> -->
                </div>
            </div>
            <div id="wpc" style="display:none;">
                <div class="ewww converter-options">
                  <h3>Options for WebPConvert Cloud Service</h3>
                  <div>
                      <label for="wpc_url">URL</label>
                      <input type="text" id="wpc_url" placeholder="Url to your WPC instance">
                  </div>
                  <div>
                      <label for="wpc_secret">Secret</label>
                      <input type="text" id="wpc_secret" placeholder="Secret (must match secret on server side)">
                  </div>
                  <br>
                  <h4>Fallback (optional)</h4>
                  <div>
                      <label for="wpc_url_2">URL</label>
                      <input type="text" id="wpc_url_2" placeholder="Url to your other WPC instance">
                  </div>
                  <div>
                      <label for="wpc_secret_2">Secret</label>
                      <input type="text" id="wpc_secret_2" placeholder="Secret (must match secret on server side)">
                  </div>
                  <br>
                  <button onclick="updateConverterOptions()" class="button button-primary" type="button">Update</button>
                </div>
            </div>
            <?php
            //print_r($urls);
            //echo $urls['urls']['webpExpressRoot'];
            if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false )) {
                echo 'Your browser supports webp... So you can test if everything, including the redirect magic works using these links:<br>';
                $webpExpressRoot = WebPExpressHelpers::calculateUrlsAndPaths()['urls']['webpExpressRoot'];
                echo '<a href="' . $webpExpressRoot . '/test/test.jpg" target="_blank">Convert test image</a><br>';
                echo '<a href="' . $webpExpressRoot . '/test/test.jpg?debug" target="_blank">Convert test image (show debug)</a><br>';
            }
             ?>

            <!--
            <div id="add-cloud-converter-id" style="display:none;">
                <p>
                  Select cloud converter to add:

                  <button onclick="addConverter('ewww')" class="button button-primary" type="button">Add ewww converter</button>
                </p>
            </div>
            <button class="button button-secondary" onclick="addConverterClick()" type="button">Add cloud converter</button>
            -->
            <?php


            submit_button('Save settings');
            ?>
        </form>
    </div>

<?php
}

add_action('updated_option', function($option_name, $old_value, $value) {
    switch ($option_name) {
        case 'webp_express_quality':
        case 'webp_express_method':
        case 'webp_express_converters':
        case 'webp_express_failure_response':
            //update_option('webp-express-htaccess-needs-updating', true, false);

            $rules = WebPExpressHelpers::generateHTAccessRules();
            WebPExpressHelpers::insertHTAccessRules($rules);

            break;
    }
}, 10, 3);



//End webp_express_settings_page_content

//include( plugin_dir_path( __FILE__ ) . 'lib/helpers.php');

//echo '<pre>rules:' . WebPExpressHelpers::generateHTAccessRules() . '</pre>';

/*
add_action('admin_menu', function () {
    add_options_page('WebP Express', 'WebP Express', 'manage_options', 'webp-express', function () {
        include(plugin_dir_path(__FILE__) . 'lib/options.php');
    });
});

function d1() {
    echo '<p>Main description of this section here.</p>';
}
function d2() {
    $options = get_option('options');
    echo "<input id='plugin_text_string' name='plugin_options[text_string]' size='40' type='text' value='{$options['text_string']}' />";
}
function plugin_options_validate($input) {
    $newinput['text_string'] = trim($input['text_string']);
    if (!preg_match('/^[a-z0-9]{32}$/i', $newinput['text_string'])) {
        $newinput['text_string'] = '';
    }
    return $newinput;
}

add_action('admin_init', function () {
    register_setting('general', 'quality', [
        'type' => 'string',
        'default' => '85'
    ]);
    add_settings_field('plugin_text_string', 'Plugin Text Input', function () {
        echo 'hello...';
    }, 'webp-express');

    //register_setting('webp_express_options', 'options', 'plugin_options_validate');
    //add_settings_section('webp_express_main', 'Main Settings', d1, 'webp_express');
    //add_settings_field('plugin_text_string', 'Plugin Text Input', d2, 'webp_express', 'webp_express_main');
});
*/
