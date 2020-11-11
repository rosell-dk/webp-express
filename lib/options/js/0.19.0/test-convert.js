function openTestConvertPopup(converterId) {
    var html = '<div id="tc_conversion_options">options</div><div><div id="tc_conversion_result"><h2>Result</h2>wait...</div></div>'
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
    html += '<option value="test-pattern-tv.jpg">test-pattern-tv.jpg</option>';
    html += '<option value="dice.png">dice.png</option>';
    //html += '<option value="alphatest.png">alphatest.png</option>';
    html += '<option value="palette-based-colors.png">palette-based-colors.png</option>';
    //html += '<option value="test.png">test.png</option>';
    html += '<option value="architecture-q85-w600.jpg">architecture-q85-w600.jpg</option>';
    html += '</select></div>';
//    html += '<h3>Conversion options</h3>'
    html += '<div id="tc_png" class="toggler effect-visibility"><h3>Conversion options (PNG)</h3><div id="tc_png_options"></div></div>';
    html += '<div id="tc_jpeg" class="toggler effect-visibility"><h3>Conversion options (JPEG)</h3><div id="tc_jpeg_options"></div></div>';
//    html += '<div id="tc_jpeg_options" class="toggler effect-visibility"></div>';
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
    function el(elmId) {
        return document.getElementById(elmId);
    }

    var testImage = el('tc_test_image').value;
    var isPng = (testImage.toLowerCase().indexOf('.png') != -1);

    toggleVisibility('tc_png', isPng);
    toggleVisibility('tc_jpeg', !isPng);

    toggleVisibility('tc_png_quality_lossy_div', el('tc_png_encoding_select').value != 'lossless');
    toggleVisibility('tc_png_near_lossless_div', el('tc_png_enable_near_lossless').value == 'on');

    console.log('value:' +  el('tc_quality_auto_select').value);
    toggleVisibility('tc_max_quality_div', el('tc_quality_auto_select').value == 'auto_on');
    toggleVisibility('tc_quality_specific_div', el('tc_quality_auto_select').value != 'auto_on');


}

function runTestConversion() {
    var html = '';

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
        "quality-specific": (elTxt("quality-auto") == 'auto_on' ? elInt('quality-fallback') : elInt('quality-specific')),
        "png-encoding": elTxt("png-encoding"),
        "png-enable-near-lossless": true,
        "png-near-lossless": elInt("png-near-lossless"),
        "png-quality": elInt("png-quality"),
        "alpha-quality": elInt("alpha-quality"),
        "metadata": elTxt('metadata'),
        "log-call-arguments": true,
    };

    var data = {
        'action': 'convert_file',
        'nonce': window.webpExpress['ajax-nonces']['convert'],
        'filename': window.webpExpressPaths['filePaths']['webpExpressRoot'] + '/test/' + elTxt('image'),
        "converter": elTxt("converter"),
        'config-overrides': JSON.stringify(configOverrides)
    }

    //html = JSON.stringify(data);
    //html = 'Converting...';
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

function processLogMoveOptions(thelog) {
    var pos1 = thelog.indexOf('Options:<br>---');
    if (pos1 >= 0) {
        var pos2 = thelog.indexOf('<br>', pos1 + 12) + 4;
        //pos2+=8;
        /*if (thelog.indexOf('<br>', pos2) < 2) {
            pos2 = thelog.indexOf('<br>', pos2) + 4;
        }*/
        var pos3 = thelog.indexOf('----<br>', pos2) + 8;

        // Remove empty line after "Conversion log:"
        var pos4 = thelog.indexOf('<br>', pos3);
        if (pos4-pos3 < 2) {
            pos3 = pos4 + 4;
        }
        //pos3+=4;

        return thelog.substr(0, pos1) +
            thelog.substr(pos3) +
            //'-------------------------------------------<br>' +
            '<h3>Options:</h3>' +
            thelog.substr(pos2, pos3-pos2);
    }
    return thelog;


/*
    return thelog.substr(0, pos1) +
        'Click to view options' +
        '<div style="display:none">' + thelog.substr(pos1, pos2-pos1) + '</div>' +
        thelog.substr(pos2);
        */
}

function convertResponseCallback(response){

    if (typeof response.requestError == 'boolean') {
        document.getElementById('tc_conversion_result').innerHTML = '<h1 style="color:red">An error occured!</h1>';
        //console.log('response', response);
        return;
    }
    if ((response['success'] === false) && response['data']) {
        document.getElementById('tc_conversion_result').innerHTML = '<h1 style="color:red">An error occured</h1>' + response['data'];
        return;
    }

    if ((typeof response == 'string') && (response[0] != '{')) {
        document.getElementById('tc_conversion_result').innerHTML =
            '<h1 style="color:red">Response was not JSON</h1><p>The following was returned:</p>' + response;
        return;
    }

    var result = JSON.parse(response);
    //result['log'] = processLogMoveOptions(result['log']);


    //var html = document.getElementById('tc_conversion_result').innerHTML;
    var html = '';

    if (result['success'] === true) {

        html += '<h2>Result: <span style="color:green;margin-bottom:2px">Success</span></h2>';

        // sizes
        var orgSize = result['filesize-original'];
        var webpSize = result['filesize-webp'];
        html += '<b>Reduction: ' + Math.round((orgSize - webpSize)/orgSize * 100) + '% ';


        if (orgSize < 10000) {
            orgSizeStr = orgSize + ' bytes';
            webpSizeStr = webpSize + ' bytes';

        } else {
            orgSizeStr = Math.round(orgSize / 1024) + ' K';
            webpSizeStr = Math.round(webpSize / 1024) + ' K';
        }
        html += '(from ' + orgSizeStr.replace('K', 'kb') + ' to ' + webpSizeStr.replace('K', 'kb') + ')';
        html += '</b><br><br>'

        if (window.canDisplayWebp) {
            var filename = document.querySelector('#tc_conversion_options [name=image]').value;
            var srcUrl = '/' + window.webpExpressPaths['urls']['webpExpressRoot']  + '/test/' + filename;
            //html += '<img src="/' + srcUrl + '" style="width:100%">';



            // TODO: THIS DOES NOT WORK. NEEDS ATTENTION!
            /*
            var webpUrl = '/' + window.webpExpressPaths['urls']['content'] +
                              '/webp-express/webp-images/doc-root/' +
                              window.webpExpressPaths['filePaths']['pluginRelToDocRoot'] + '/' +
                              'webp-express/' +
                              'test/' +
                              filename + '.webp';
                          */
            //html += '<img src="' + webpUrl + '" style="width:100%">';

            var webpUrl = result['destination-url'];

            html += '<div class="cd-image-container">';
            html += '  <div class="cd-image-label webp">WebP: ' + webpSizeStr + '</div>';
            html += '  <div class="cd-image-label original">' + (filename.toLowerCase().indexOf('png') > 0 ? 'PNG' : 'JPEG') + ': ' + orgSizeStr + '</div>';
            html += '  <img src="' + webpUrl + '" alt="Converted Image" style="max-width:100%">';
            html += '  <div class="cd-resize-img"> <!-- the resizable image on top -->';
            html += '    <img src="' + srcUrl + '" alt="Original Image">';
            html += '  </div>';
            html += '  <span class="cd-handle"></span> <!-- slider handle -->';
            html += '</div> <!-- cd-image-container -->';
            html += '<i>Drag the slider above to compare original vs webp</i><br><br>'
        }

        html += '<h3>Conversion log:</h3>';

        // the "log" result is a simple form of markdown, using just italic, bold and newlines.
        // It ought not to return anything evil, but safety first
        html += '<pre style="white-space:pre-wrap">' + webpexpress_escapeHTML(result['log']) + '</pre>';

        document.getElementById('tc_conversion_result').innerHTML = html;
        initComparisonSlider(jQuery);

    } else {
        html += '<h2>Result: <span style="color:red;margin-bottom:2px">Failure</span></h2>';

        if (result['msg'] != '') {
            html += ' <h3>Message: <span style="color:red; font-weight: bold">' + webpexpress_escapeHTML(result['msg']) + '</span></h3>';
        }
        if (result['log'] != '') {
            html += '<h3>Conversion log:</h3>';
            html += '<pre style="white-space:pre-wrap">' + webpexpress_escapeHTML(result['log']) + '</pre>';
        }

        document.getElementById('tc_conversion_result').innerHTML = html;
    }

    //html = result['log'];

}
