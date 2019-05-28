
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
    html += '<div style="display:inline-block; margin-right: 20px;"><label>Converter:</label><select id="converter">';
    for (var i=0; i<window.converters.length; i++) {
        var c = window.converters[i];
        var cid = c['converter'];
        html += '<option value="' + cid + '"' + (cid == converterId ? ' selected' : '') + '>' + cid + '</option>';
    }
    html += '</select></div>'
    html += '<div style="display:inline-block;"><label>Test image:</label><select id="test_image">';
    html += '<option value="test.png">test.png</option>';
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

    var data = {
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
    };

    html = JSON.stringify(data);
    document.getElementById('tc_conversion_result').innerHTML = html;
}
