
function openTestConvertPopup(converterId) {
    var html = '<div id="tc_conversion_options">options</div><div><h2>Result</h2><div id="tc_conversion_result"></div></div>'
    document.getElementById('tc_content').innerHTML = html;

    var w = Math.min(1200, Math.max(200, document.documentElement.clientWidth - 100));
    var h = Math.max(250, document.documentElement.clientHeight - 80);
    tb_show('Testing conversion', '#TB_inline?inlineId=tc_popup&width=' + w + '&height=' + h);

    webpexpress_createTCOptions(converterId);
}

function webpexpress_createTCOptions(converterId) {

    var html = '';
    html += '<h2>Options</h2>'
    html += '<form>';
    html += '<div style="display:inline-block; margin-right: 20px;"><label>Converter:</label><select id="converter" name="converter">';
    for (var i=0; i<window.converters.length; i++) {
        var c = window.converters[i];
        var cid = c['converter'];
        html += '<option value="' + cid + '"' + (cid == converterId ? ' selected' : '') + '>' + cid + '</option>';
    }
    html += '</select></div>'
    html += '<div style="display:inline-block;"><label>Test image:</label><select id="test_image" name="image">';
    //html += '<option value="dice.png">dice.png</option>';
    html += '<option value="alphatest.png">alphatest.png</option>';
    html += '<option value="palette-based-colors.png">palette-based-colors.png</option>';

    html += '<option value="test.jpg">test.jpg</option>';
    html += '<option value="focus.jpg">focus.jpg</option>';
    html += '</select></div>';
    html += '<h3>Conversion options</h3>'
    html += '<div id="tc_png_options" class="toggler effect-visibility"></div>';
    html += '<div id="tc_jpeg_options" class="toggler effect-visibility"></div>';
    html += '<div><label>Metadata:</label><div id="tc_metadata" style="display: inline-block"></div></div>';
    html += '<button onclick="runTestConversion()" class="button button-primary" type="button" style="margin-top:25px">Convert</button>';
    html += '</form>';
    document.getElementById('tc_conversion_options').innerHTML = html;

    // Append PNG
    document.getElementById('tc_png_options').appendChild(
        document.getElementById('png_td').cloneNode(true)
    );

    // Append Jpeg
    document.getElementById('tc_jpeg_options').appendChild(
        document.getElementById('jpeg_td').cloneNode(true)
    );

    // Append Metadata
    document.getElementById('tc_metadata').appendChild(
        document.getElementById('metadata').cloneNode(true)
    );

    // change ids. All id's will get appended "tc_" - unless they already have
    document.querySelectorAll('#tc_conversion_options [id]').forEach(function(el) {
        el.value = document.getElementById(el.id).value;
        if (el.id.indexOf('tc_') != 0) {
            el.id = 'tc_' + el.id;
        }
    });

    // listen to all select box changes
    document.querySelectorAll('#tc_conversion_options select').forEach(function(el) {
        el.addEventListener('change', function() {
            webpexpress_updateVisibilities();
        });
    });

    webpexpress_updateVisibilities();

    runTestConversion();
}

function webpexpress_updateVisibilities() {
    // toggleVisibility('png_row', el('image_types').value != '1');
    //console.log('updating visibilities');
    function el(elmId) {
        return document.getElementById(elmId);
    }

    var testImage = el('tc_test_image').value;
    var isPng = (testImage.indexOf('.png') != -1);

    toggleVisibility('tc_png_options', isPng);
    toggleVisibility('tc_jpeg_options', !isPng);

    toggleVisibility('tc_png_quality_lossy_div', el('tc_png_encoding_select').value != 'lossless');
    toggleVisibility('tc_png_near_lossless_div', el('tc_png_enable_near_lossless').value == 'on');
}

function runTestConversion() {
    html = '';

    function elTxt(elmName) {
        //var el = document.getElementById('tc_' + elmId);
        var el = document.querySelector('#tc_conversion_options [name=' + elmName + ']');
        if (!el) {
            alert('Error: Could not find element with name: "' + elmName + '"');
        }
        return el.value;
    }
    function elInt(elmName) {
        return parseInt(elTxt(elmName), 10);
    }

    var configOverrides = {
        "jpeg-encoding": elTxt("jpeg-encoding"),
        "jpeg-enable-near-lossless": (elTxt("jpeg-enable-near-lossless") == 'on'),
        "jpeg-near-lossless": elInt('jpeg-near-lossless'),
        "quality-auto": (elTxt("quality-auto") == 'auto_on'),
        "max-quality": elInt('max-quality'),
        "quality-specific": elInt('quality-fallback'),
        "png-encoding": elTxt("png-encoding"),
        "png-enable-near-lossless": true,
        "png-near-lossless": elInt("png-near-lossless"),
        "png-quality": elInt("png-quality"),
        "alpha-quality": elInt("alpha-quality"),
        "metadata": elTxt('metadata'),
        "log-call-arguments": false,
    };

    var data = {
        'action': 'convert_file',
        'filename': window.webpExpressPaths['filePaths']['webpExpressRoot'] + '/test/' + elTxt('image'),
        "converter": elTxt("converter"),
        'config-overrides': JSON.stringify(configOverrides)
    }

    html = JSON.stringify(data);
    //console.log(data);
    document.getElementById('tc_conversion_result').innerHTML = html;

    jQuery.ajax({
        method: 'POST',
        url: ajaxurl,
        data: data,
        //dataType: 'json',
        success: (response) => {
            convertResponseCallback(response);
        },
        error: () => {
            convertResponseCallback({requestError: true});
        },
    });
}

function convertResponseCallback(response){



    var result = typeof response.requestError !== 'boolean' ? JSON.parse(response) : {
        success: false,
        msg: '',
        log: '',
    };

    var result = JSON.parse(response);

    //var html = document.getElementById('tc_conversion_result').innerHTML;
    var html = '';

    if (result['success'] === true) {

        html += '<h3 style="color:green;margin-bottom:2px">Success</span></h3>';
        var orgSize = result['filesize-original'];
        var webpSize = result['filesize-webp'];
        html += '<b>Reduction: ' + Math.round((orgSize - webpSize)/orgSize * 100) + '% ';
        if (orgSize < 10000) {
            html += '(from ' + orgSize + ' bytes to ' + webpSize + ' bytes)';
        } else {
            html += '(from ' + Math.round(orgSize / 1024) + ' kb to ' + Math.round(webpSize / 1024) + ' kb)';
        }
        html += '</b><br><br>'

        if (window.canDisplayWebp) {
            var filename = document.querySelector('#tc_conversion_options [name=image]').value;
            //html += '<img src="/' + window.webpExpressPaths['urls']['webpExpressRoot']  + '/test/' + filename + '" style="width:100%">';

            var webpUrl = '/' + window.webpExpressPaths['urls']['content'] +
                              '/webp-express/webp-images/doc-root/' +
                              window.webpExpressPaths['filePaths']['pluginRelToDocRoot'] + '/' +
                              'webp-express/' +
                              'test/' +
                              filename + '.webp';
            html += '<img src="' + webpUrl + '" style="width:100%">';
        }
        
        html += '<b>Conversion log:</b><br><br>'
        html += result['log'];

    } else {
        html += ' <span style="color:red">failed</span><br>';
        if (result['msg'] != '') {
            html += ' <span style="">' + result['msg'] + '</span>';
        }
        if (result['log'] != '') {
            html += result['log'];
        }
    }

    //html = result['log'];
    document.getElementById('tc_conversion_result').innerHTML = html;


}
