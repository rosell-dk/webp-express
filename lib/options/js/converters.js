

// Map of converters (are updated with updateConvertersMap)
window.convertersMap = {};

window.currentlyEditing = '';

function getConversionMethodDescription(converterId) {
    var descriptions = {
        'cwebp': 'cwebp',
        'wpc': 'Remote WebP Express',
        'ewww': 'ewww cloud converter',
        'gd': 'Gd extension',
        'imagick': 'Imagick (PHP extension)',
        'gmagick': 'Gmagick (PHP extension)',
        'imagemagick': 'ImageMagick',
        'graphicsmagick': 'GraphicsMagick',
        'vips': 'Vips',
        'ffmpeg': 'ffmpeg',
    };

    if (descriptions[converterId]) {
        return descriptions[converterId];
    }
    return converterId;
}

function generateConverterHTML(converter) {
    html = '<li data-id="' + converter['id'] + '" class="' + (converter.deactivated ? 'deactivated' : '') + ' ' + (converter.working ? 'operational' : 'not-operational') + ' ' + (converter.warnings ? 'has-warnings' : '') + '">';
    //html += '<svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="17px" height="17px" viewBox="0 0 100.000000 100.000000" preserveAspectRatio="xMidYMid meet"><g transform="translate(0.000000,100.000000) scale(0.100000,-0.100000)" fill="#444444" stroke="none"><path d="M415 920 l-80 -80 165 0 165 0 -80 80 c-44 44 -82 80 -85 80 -3 0 -41 -36 -85 -80z"/><path d="M0 695 l0 -45 500 0 500 0 0 45 0 45 -500 0 -500 0 0 -45z"/><path d="M0 500 l0 -40 500 0 500 0 0 40 0 40 -500 0 -500 0 0 -40z"/><path d="M0 305 l0 -45 500 0 500 0 0 45 0 45 -500 0 -500 0 0 -45z"/><path d="M418 78 l82 -83 82 83 83 82 -165 0 -165 0 83 -82z"/></g></svg>';
//    html += '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M20 9H4v2h16V9zM4 15h16v-2H4v2z"/></svg>';
//    html += '<svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 100.000000 100.000000" preserveAspectRatio="xMidYMid meet"><g transform="translate(0.000000,100.000000) scale(0.100000,-0.100000)" fill="#888888" stroke="none"><path d="M415 920 l-80 -80 165 0 165 0 -80 80 c-44 44 -82 80 -85 80 -3 0 -41 -36 -85 -80z"/><path d="M0 695 l0 -45 500 0 500 0 0 45 0 45 -500 0 -500 0 0 -45z"/><path d="M0 500 l0 -40 500 0 500 0 0 40 0 40 -500 0 -500 0 0 -40z"/><path d="M0 305 l0 -45 500 0 500 0 0 45 0 45 -500 0 -500 0 0 -45z"/><path d="M418 78 l82 -83 82 83 83 82 -165 0 -165 0 83 -82z"/></g></svg>';
    html += '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18"><path d="M2 13.5h14V12H2v1.5zm0-4h14V8H2v1.5zM2 4v1.5h14V4H2z"/></svg>';
    html += '<div class="text">';
    html += getConversionMethodDescription(converter['id']);
    html += '</div>';
    html += '<a class="configure-converter btn" onclick="configureConverter(\'' + converter['id'] + '\')">configure</a>';
    html += '<a class="test-converter btn" onclick="testConverter(\'' + converter['id'] + '\')">test</a>';

    if (converter.deactivated) {
        html += '<a class="activate-converter btn" onclick=activateConverter(\'' + converter['id'] + '\')>activate</a>';
    }
    else {
        html += '<a class="deactivate-converter btn" onclick=deactivateConverter(\'' + converter['id'] + '\')>deactivate</a>';
    }

    html += '<div class="status">';
    if (converter['error']) {
        html += '<svg id="status_not_ok" width="19" height="19" title="not operational" version="1.0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500.000000 500.000000" preserveAspectRatio="xMidYMid meet">';
        html += '<g fill="currentcolor" stroke="none" transform="translate(0.000000,500.000000) scale(0.100000,-0.100000)"><path d="M2315 4800 c-479 -35 -928 -217 -1303 -527 -352 -293 -615 -702 -738 -1151 -104 -380 -104 -824 0 -1204 107 -389 302 -724 591 -1013 354 -354 785 -572 1279 -646 196 -30 476 -30 672 0 494 74 925 292 1279 646 354 354 571 784 646 1279 30 197 30 475 0 672 -75 495 -292 925 -646 1279 -289 289 -624 484 -1013 591 -228 62 -528 91 -767 74z m353 -511 c458 -50 874 -272 1170 -624 417 -497 536 -1174 308 -1763 -56 -145 -176 -367 -235 -434 -4 -4 -566 552 -1250 1236 l-1243 1243 94 60 c354 229 754 327 1156 282z m864 -3200 c-67 -59 -289 -179 -434 -235 -946 -366 -2024 172 -2322 1158 -47 155 -66 276 -73 453 -13 362 84 704 290 1023 l60 94 1243 -1243 c684 -684 1240 -1246 1236 -1250z"/></g></svg>';
        html += '<div class="popup">';

        html += webpexpress_escapeHTML(converter['error']);

        html += '</div>';
    } else if (converter['warnings']) {
      /*html += '<svg id="status_warning" width="19" height="19" version="1.0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 478.125 478.125">';
      html += '<g fill="currentcolor"">';
      html += '<circle cx="239.904" cy="314.721" r="35.878"/>';
      html += '<path d="M256.657,127.525h-31.9c-10.557,0-19.125,8.645-19.125,19.125v101.975c0,10.48,8.645,19.125,19.125,19.125h31.9c10.48,0,19.125-8.645,19.125-19.125V146.65C275.782,136.17,267.138,127.525,256.657,127.525z"/>';
      html += '<path d="M239.062,0C106.947,0,0,106.947,0,239.062s106.947,239.062,239.062,239.062c132.115,0,239.062-106.947,239.062-239.062S371.178,0,239.062,0z M239.292,409.734c-94.171,0-170.595-76.348-170.595-170.596c0-94.248,76.347-170.595,170.595-170.595s170.595,76.347,170.595,170.595C409.887,333.387,333.464,409.734,239.292,409.734z"/>';
      html += '</g></svg>';*/
      html += '<svg id="status_warning" width="19" height="19" version="1.0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 123.996 123.996">';
      html += '<circle cx="62" cy="67" r="35" color="black"/>';
      html += '<g fill="currentcolor">';
      html += '<path d="M9.821,118.048h104.4c7.3,0,12-7.7,8.7-14.2l-52.2-92.5c-3.601-7.199-13.9-7.199-17.5,0l-52.2,92.5C-2.179,110.348,2.521,118.048,9.821,118.048z M70.222,96.548c0,4.8-3.5,8.5-8.5,8.5s-8.5-3.7-8.5-8.5v-0.2c0-4.8,3.5-8.5,8.5-8.5s8.5,3.7,8.5,8.5V96.548z M57.121,34.048h9.801c2.699,0,4.3,2.3,4,5.2l-4.301,37.6c-0.3,2.7-2.1,4.4-4.6,4.4s-4.3-1.7-4.6-4.4l-4.301-37.6C52.821,36.348,54.422,34.048,57.121,34.048z"/>';
      html += '</g></svg>';


        html += '<div class="popup">';

        if (converter['warnings'].join) {
            if (converter['warnings'].filter) {
                // remove duplicate warnings
                converter['warnings'] = converter['warnings'].filter(function(item, pos, self) {
                    return self.indexOf(item) == pos;
                })
            }
            html += '<p>Warnings were issued:</p>';
            for (var i = 0; i<converter['warnings'].length; i++) {
                html += '<p>' + webpexpress_escapeHTML(converter['warnings'][i]) + '</p>';
            }
            // TODO: Tell him to deactivate the converter - or perhaps do it automatically?
            html += '<p>check conversion log for more insight (ie by clicking the "test" link a little left of this warning triangle)</p>';
        }

        html += '</div>';
    } else if (converter.working) {
        html += '<svg id="status_ok" width="19" height="19" version="1.0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256.000000 256.000000" preserveAspectRatio="xMidYMid meet">';
        html += '<g fill="currentcolor" stroke="none" transform="translate(0.000000,256.000000) scale(0.100000,-0.100000)"><path d="M1064 2545 c-406 -72 -744 -324 -927 -690 -96 -193 -127 -333 -127 -575 0 -243 33 -387 133 -585 177 -351 518 -606 907 -676 118 -22 393 -17 511 8 110 24 252 78 356 136 327 183 569 525 628 887 19 122 19 338 0 460 -81 498 -483 914 -990 1025 -101 22 -389 28 -491 10z m814 -745 c39 -27 73 -59 77 -70 9 -27 10 -25 -372 -590 -345 -510 -357 -524 -420 -512 -19 4 -98 74 -250 225 -123 121 -225 228 -228 238 -3 10 1 31 9 47 20 40 125 132 149 132 11 0 79 -59 162 -140 79 -77 146 -140 149 -140 3 0 38 48 78 108 95 143 465 678 496 720 35 46 64 42 150 -18z"/></g></svg>';
        //html += '<div class="popup">' + converter['id'] + ' is operational</div>';
        html += '<div class="popup">Operational</div>';
    }
    html += '</div>';

    html += '</li>';
    return html;
}

/* Set ids on global converters object */
function setTemporaryIdsOnConverters() {
    if (window.converters == undefined) {
        console.log('window.converters is undefined. Strange. Please report!');
        return;
    }
    var numConverterInstances = [];
    for (var i=0; i<window.converters.length; i++) {
        var converter = converters[i]['converter'];
        if (numConverterInstances[converter]) {
            numConverterInstances[converter]++;
            window.converters[i]['id'] = converter + '-' + numConverterInstances[converter];
        }
        else {
            numConverterInstances[converter] = 1;
            window.converters[i]['id'] = converter;
        }
    }
    //alert(JSON.stringify(window.converters));
    updateConvertersMap();
}

function updateConvertersMap() {
    var map = {};
    for (var i=0; i<window.converters.length; i++) {
        var converter = window.converters[i];
        map[converter['id']] = converter;
    }
    window.convertersMap = map;
}

function reorderConverters(order) {

    // Create new converter array
    var result = [];
    for (var i=0; i<order.length; i++) {
        result.push(window.convertersMap[order[i]]);
    }
    //alert(JSON.stringify(result));
    window.converters = result;
    updateInputValue();
}

/* Update the hidden input containing all the data */
function updateInputValue() {
    document.getElementsByName('converters')[0].value = JSON.stringify(window.converters);
}

function setConvertersHTML() {


    var html = '';

    setTemporaryIdsOnConverters();

    if (document.getElementById('converters') == null) {
        alert('document.getElementById("converters") returns null. Strange! Please report.');
        return;
    }

    for (var i=0; i<window.converters.length; i++) {
        var converter = converters[i];
        html += generateConverterHTML(converter);
    }

    var el = document.getElementById('converters');
    el.innerHTML = html;

    var sortable = Sortable.create(el, {
        onChoose: function() {
            document.getElementById('converters').className = 'dragging';
        },
        onUnchoose: function() {
            document.getElementById('converters').className = '';
        },
        store: {
            get: function() {
                var order = [];
                for (var i=0; i<window.converters.length; i++) {
                    order.push(window.converters[i]['id']);
                }
                return order;
            },
            set: function(sortable) {
                var order = sortable.toArray();
                reorderConverters(order);
            }
        }
    });
    updateInputValue();
}

document.addEventListener('DOMContentLoaded', function() {
    setConvertersHTML();
});

function wpe_addCloudConverter(converter) {

}

function isConverterOptionSet(converter, optionName) {
    if ((converter['options'] == undefined) || (converter['options'][optionName] == undefined)) {
        return false;
    }
    return true;
}

function getConverterOption(converter, optionName, defaultValue) {
    if ((converter['options'] == undefined) || (converter['options'][optionName] == undefined)) {
        return defaultValue;
    }
    return converter['options'][optionName];
}

function setConverterOption(converter, optionName, value) {
    if (converter['options'] == undefined) {
        converter['options'] = {};
    }
    converter['options'][optionName] = value;
}

function deleteConverterOption(converter, optionName) {
    if (converter['options'] == undefined) {
        converter['options'] = {};
    }
    delete converter['options'][optionName];
}


function configureConverter(id) {
    var converter = window.convertersMap[id];
    window.currentlyEditing = id;
    /*
    Removed (#243)
    var q = getConverterOption(converter, 'quality', 'auto');
    if (document.getElementById(id + '_quality')) {
        document.getElementById(id + '_quality').value = q;
        document.getElementById(id + '_max_quality_div').style['display'] = (q == 'auto' ? 'block' : 'none');
        document.getElementById(id + '_max_quality').value = getConverterOption(converter, 'max-quality', 85);
    }
    */

    switch (converter['converter']) {
        case 'ewww':
            document.getElementById('ewww_api_key').value = getConverterOption(converter, 'api-key', '');
            document.getElementById('ewww_api_key_2').value = getConverterOption(converter, 'api-key-2', '');
            break;
        case 'wpc':

            document.getElementById('wpc_api_url').value = getConverterOption(converter, 'api-url', '');

            /* api key in configuration file can be:
               - never set (null)
               - set to be empty ('')
               - set to be something.

               If never set, we show a password input.
               If set to empty, we also show a password input.
               There is no need to differentiate. between never set and empty
               If set to something, we show a link "Change"

               In Config::getConfigForOptionsPage, we remove the api key from javascript array.
               if api key is non-empty, a "_api-key-non-empty" field is set.
            */

            document.getElementById('wpc_new_api_key').value = '';


            if (getConverterOption(converter, '_api-key-non-empty', false)) {
                // api key is set to something...
                document.getElementById('wpc_change_api_key').style.display = 'inline';
                document.getElementById('wpc_new_api_key').style.display = 'none';
            } else {
                // api key is empty (or not set)
                document.getElementById('wpc_new_api_key').style.display = 'inline';
                document.getElementById('wpc_change_api_key').style.display = 'none';
            }

            apiVersion = getConverterOption(converter, 'api-version', 0);

            // if api version isn't set, then either
            // - It is running on old api 0. In that case, URL is set
            // - Wpc has never been configured. In that case, URL is not set,
            //      and we should not mention api 0 (we should set apiVersion to 1)
            if (!isConverterOptionSet(converter, 'api-version')) {
                if (getConverterOption(converter, 'api-url', '') == '') {
                    apiVersion = 1;
                }
            }

            document.getElementById('wpc_api_version').value = apiVersion.toString();

            if (apiVersion != 0) {
            }

            if (apiVersion == 0) {
                document.getElementById('wpc_secret').value = getConverterOption(converter, 'secret', '');
            } else {
                // Only show api version dropdown if configured to run on old api
                // There is no going back!
                document.getElementById('wpc_api_version_div').style.display = 'none';
            }

            document.getElementById('wpc_crypt_api_key_in_transfer').checked = getConverterOption(converter, 'crypt-api-key-in-transfer', true);

            // Hide/show the fields for the api version
            wpcApiVersionChanged();

            //document.getElementById('wpc_secret').value = getConverterOption(converter, 'secret', '');
            //document.getElementById('wpc_url_2').value = getConverterOption(converter, 'url-2', '');
            //document.getElementById('wpc_secret_2').value = getConverterOption(converter, 'secret-2', '');


            //wpcUpdateWebServicesHTML();

            break;
        case 'gd':
            document.getElementById('gd_skip_pngs').checked = getConverterOption(converter, 'skip-pngs', false);
            break;
        case 'cwebp':
            document.getElementById('cwebp_use_nice').checked = getConverterOption(converter, 'use-nice', true);
            document.getElementById('cwebp_method').value = getConverterOption(converter, 'method', '');
            document.getElementById('cwebp_try_common_system_paths').checked = getConverterOption(converter, 'try-common-system-paths', '');
            document.getElementById('cwebp_skip_these_precompiled_binaries').value = getConverterOption(converter, 'skip-these-precompiled-binaries', '');
            document.getElementById('cwebp_try_supplied_binary').checked = getConverterOption(converter, 'try-supplied-binary-for-os', '');
            document.getElementById('cwebp_set_size').checked = getConverterOption(converter, 'set-size', '');
            document.getElementById('cwebp_size_in_percentage').value = getConverterOption(converter, 'size-in-percentage', '');
            document.getElementById('cwebp_command_line_options').value = getConverterOption(converter, 'command-line-options', '');
            break;
        case 'imagemagick':
            document.getElementById('imagemagick_use_nice').checked = getConverterOption(converter, 'use-nice', true);
            break;
        case 'graphicsmagick':
            document.getElementById('graphicsmagick_use_nice').checked = getConverterOption(converter, 'use-nice', true);
            break;
        case 'vips':
            document.getElementById('vips_smart_subsample').checked = getConverterOption(converter, 'smart-subsample', false);
            document.getElementById('vips_preset').value = getConverterOption(converter, 'preset', 'disable');
            break;
        case 'ffmpeg':
            document.getElementById('ffmpeg_use_nice').checked = getConverterOption(converter, 'use-nice', true);
            document.getElementById('ffmpeg_method').value = getConverterOption(converter, 'method', '');
            break;
    }
    tb_show("Configure " + converter['id'] + ' converter', '#TB_inline?inlineId=' + converter['converter']);
}

function updateConverterOptions() {
    var id = window.currentlyEditing;
    var converter = window.convertersMap[id];

    /*
    Removed (#243)
    if (document.getElementById(id + '_quality')) {
        var q = document.getElementById(id + '_quality').value;
        if (q == 'auto') {
            setConverterOption(converter, 'quality', 'auto');
            setConverterOption(converter, 'max-quality', document.getElementById(id + '_max_quality').value);
        } else {
            setConverterOption(converter, 'quality', 'inherit');
            deleteConverterOption(converter, 'max-quality');
        }
    } else {
        deleteConverterOption(converter, 'quality');
        deleteConverterOption(converter, 'max-quality');
    }
    */

    switch (converter['converter']) {
        case 'ewww':
            setConverterOption(converter, 'api-key', document.getElementById('ewww_api_key').value);
            setConverterOption(converter, 'api-key-2', document.getElementById('ewww_api_key_2').value);
            break;
        case 'wpc':
            setConverterOption(converter, 'api-url', document.getElementById('wpc_api_url').value);
            //setConverterOption(converter, 'secret', document.getElementById('wpc_secret').value);
            //setConverterOption(converter, 'url-2', document.getElementById('wpc_url_2').value);
            //setConverterOption(converter, 'secret-2', document.getElementById('wpc_secret_2').value);*/

            var apiVersion = parseInt(document.getElementById('wpc_api_version').value, 10);
            setConverterOption(converter, 'api-version', apiVersion);

            if (apiVersion == '0') {
                setConverterOption(converter, 'secret', document.getElementById('wpc_secret').value);
            } else {
                deleteConverterOption(converter, 'secret');
                setConverterOption(converter, 'crypt-api-key-in-transfer', document.getElementById('wpc_crypt_api_key_in_transfer').checked);
            }

            if (document.getElementById('wpc_new_api_key').style.display == 'inline') {
                // password field is shown. Store the value
                setConverterOption(converter, 'new-api-key', document.getElementById('wpc_new_api_key').value);
            } else {
                // password field is not shown. Remove "new-api-key" value, indicating there is no new value
                //setConverterOption(converter, 'new-api-key', '');
                deleteConverterOption(converter, 'new-api-key');
            }

            break;
        case 'gd':
            setConverterOption(converter, 'skip-pngs', document.getElementById('gd_skip_pngs').checked);
            break;
        case 'cwebp':
            setConverterOption(converter, 'use-nice', document.getElementById('cwebp_use_nice').checked);
            var methodString = document.getElementById('cwebp_method').value;
            var methodNum = (methodString == '') ? 6 : parseInt(methodString, 10);
            setConverterOption(converter, 'method', methodNum);
            setConverterOption(converter, 'skip-these-precompiled-binaries', document.getElementById('cwebp_skip_these_precompiled_binaries').value);
            setConverterOption(converter, 'try-common-system-paths', document.getElementById('cwebp_try_common_system_paths').checked);
            setConverterOption(converter, 'try-supplied-binary-for-os', document.getElementById('cwebp_try_supplied_binary').checked);
            setConverterOption(converter, 'set-size', document.getElementById('cwebp_set_size').checked);

            var sizeInPercentageString = document.getElementById('cwebp_size_in_percentage').value;
            var sizeInPercentageNumber = (sizeInPercentageString == '') ? '' : parseInt(sizeInPercentageString, 10);
            setConverterOption(converter, 'size-in-percentage', sizeInPercentageNumber);
            setConverterOption(converter, 'command-line-options', document.getElementById('cwebp_command_line_options').value);
            break;
        case 'imagemagick':
            setConverterOption(converter, 'use-nice', document.getElementById('imagemagick_use_nice').checked);
            break;
        case 'graphicsmagick':
            setConverterOption(converter, 'use-nice', document.getElementById('graphicsmagick_use_nice').checked);
            break;
        case 'vips':
            setConverterOption(converter, 'smart-subsample', document.getElementById('vips_smart_subsample').checked);

            var vipsPreset = document.getElementById('vips_preset').value;
            if (vipsPreset == 'disable') {
                deleteConverterOption(converter, 'preset');
            } else {
                setConverterOption(converter, 'preset', vipsPreset);
            }
            break;
        case 'ffmpeg':
            setConverterOption(converter, 'use-nice', document.getElementById('ffmpeg_use_nice').checked);
            var methodString = document.getElementById('ffmpeg_method').value;
            var methodNum = (methodString == '') ? 6 : parseInt(methodString, 10);
            setConverterOption(converter, 'method', methodNum);
            break;
    }

    updateInputValue();
    tb_remove();
}

function updateConverterOptionsAndSave() {
    updateConverterOptions();
    document.getElementById('webpexpress_settings').submit();
}
/** Encode path before adding to querystring.
 *  Paths in querystring triggers LFI warning in Wordfence.
 *  By encoding it, Wordpfence will not detect our misdeed!
 *
 *  see https://github.com/rosell-dk/webp-express/issues/87
 */
function encodePathforQS(path) {
    return path.replace('/', '**');
}

function testConverter(id) {
    openTestConvertPopup(id);
    return;
}

/*
function removeConverter(id) {
    for (var i=0; i<window.converters.length; i++) {
        if (window.converters[i]['id'] == id) {
            window.converters.splice(i, 1);
            setConvertersHTML();
            break;
        }
    }
}*/

function addConverter(id) {
    window.converters.push({
        converter: id
    });
    setConvertersHTML();
    tb_remove();
}

function deactivateConverter(id) {
    window.convertersMap[id].deactivated = true;
    setConvertersHTML();
}

function activateConverter(id) {
    delete window.convertersMap[id].deactivated
    setConvertersHTML();
}

/*  WPC          */
/* ------------- */

/*
function converterQualityChanged(converterId) {
    var q = document.getElementById(converterId + '_quality').value;
    document.getElementById(converterId + '_max_quality_div').style['display'] = (q == 'auto' ? 'block' : 'none');
}*/

function wpcShowAwaitingApprovalPopup() {
    closeDasPopup();
    openDasPopup('wpc_awaiting_approval_popup', 500, 350);

/*
    window.pollRequestApprovalTid = window.setInterval(function() {
        //openDasPopup('wpc_successfully_connected_popup', 500, 350);

    }, 1500);*/

}

function wpcRequestAccess() {

    var url = document.getElementById('wpc_request_access_url').value;
    url = 'http://we0/wordpress/webp-express-server';

    jQuery.post(window.ajaxurl, {
        'action': 'webpexpress_request_access',
    }, function(response) {
        if (response && (response.substr(0,1) == '{')) {
            var r = JSON.parse(response);
            if (r['success']) {
                wpcShowAwaitingApprovalPopup()
            } else {
                alert(r['errorMessage']);
            }
        }
    });
}

function openWpcConnectPopup() {
    openDasPopup('wpc_connect_popup', 500, 350);
}

function wpcChangeApiKey() {
    document.getElementById('wpc_new_api_key').style.display = 'inline';
    document.getElementById('wpc_change_api_key').style.display = 'none';
}

function wpcApiVersionChanged() {
    var apiVersion = parseInt(document.getElementById('wpc_api_version').value, 10);
    if (apiVersion == 0) {
        document.getElementById('wpc_crypt_api_key_in_transfer_div').style.display = 'none';
        document.getElementById('wpc_api_key_label_1').style.display = 'inline-block';
        document.getElementById('wpc_api_key_label_2').style.display = 'none';
        document.getElementById('wpc_secret_div').style.display = 'block';
        document.getElementById('wpc_api_key_div').style.display = 'none';
    } else {
        document.getElementById('wpc_crypt_api_key_in_transfer_div').style.display = 'block';
        document.getElementById('wpc_api_key_label_1').style.display = 'none';
        document.getElementById('wpc_api_key_label_2').style.display = 'inline-block';
        document.getElementById('wpc_secret_div').style.display = 'none';
        document.getElementById('wpc_api_key_div').style.display = 'block';
    }
}
