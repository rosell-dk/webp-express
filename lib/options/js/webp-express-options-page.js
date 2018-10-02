

// Map of converters (are updated with updateConvertersMap)
window.convertersMap = {};

window.currentlyEditing = '';

function resetToDefaultConverters() {
    window.converters = window.defaultConverters;
}

function addMissingConvertersAndOptions() {
    // check if all available converters are in the array.
    // if not - add!
    // the double loop could be avoided with map. But arrays are so small, so not worth it
    for (var i=0; i<window.defaultConverters.length; i++) {
        var checkMe = window.defaultConverters[i];
        var found = false;
        for (var j=0; j<window.converters.length; j++) {
            var checkMe2 = window.converters[j]
            if (checkMe2['converter'] == checkMe['converter']) {
                found = true;

                if (checkMe['options']) {
                    for (var optionName in checkMe['options']) {
                        if (checkMe['options'].hasOwnProperty(optionName)) {
                            if (!checkMe2['options']) {
                                checkMe2['options'] = [];
                            }
                            if (!checkMe2['options'].hasOwnProperty(optionName)) {
                                checkMe2['options'][optionName] = checkMe['options'][optionName];
                            }
                        }
                    }
                }
            }
        }
        if (!found) {
            window.converters.push(window.defaultConverters[i]);
        }
    }
}

function addMissingOptions() {

}

function getConversionMethodDescription(converterId) {
    var descriptions = {
        'cwebp': '<i>cwebp</i> binary',
        'wpc': 'wpc cloud converter',
        'ewww': 'ewww cloud converter',
        'gd': 'Gd extension',
        'imagick': 'Imagick extension',
        'gmagick': 'Gmagick extension',
    };
    if (descriptions[converterId]) {
        return descriptions[converterId];
    }
    return converterId;
}

function htmlEscape(str) {
    return str
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function generateConverterHTML(converter) {
    html = '<li data-id="' + converter['id'] + '" class="' + (converter.deactivated ? 'deactivated' : '') + ' ' + (converter.working ? 'operational' : 'not-operational') + '">';
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
    if (converter.working) {
        html += '<svg id="status_ok" width="21" height="21" version="1.0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256.000000 256.000000" preserveAspectRatio="xMidYMid meet">';
        html += '<g fill="currentcolor" stroke="none" transform="translate(0.000000,256.000000) scale(0.100000,-0.100000)"><path d="M1064 2545 c-406 -72 -744 -324 -927 -690 -96 -193 -127 -333 -127 -575 0 -243 33 -387 133 -585 177 -351 518 -606 907 -676 118 -22 393 -17 511 8 110 24 252 78 356 136 327 183 569 525 628 887 19 122 19 338 0 460 -81 498 -483 914 -990 1025 -101 22 -389 28 -491 10z m814 -745 c39 -27 73 -59 77 -70 9 -27 10 -25 -372 -590 -345 -510 -357 -524 -420 -512 -19 4 -98 74 -250 225 -123 121 -225 228 -228 238 -3 10 1 31 9 47 20 40 125 132 149 132 11 0 79 -59 162 -140 79 -77 146 -140 149 -140 3 0 38 48 78 108 95 143 465 678 496 720 35 46 64 42 150 -18z"/></g></svg>';
        html += '<div class="popup">' + converter['id'] + ' is operational</div>';
    } else {
        html += '<svg id="status_not_ok" width="21" height="21" title="not operational" version="1.0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500.000000 500.000000" preserveAspectRatio="xMidYMid meet">';
        html += '<g fill="currentcolor" stroke="none" transform="translate(0.000000,500.000000) scale(0.100000,-0.100000)"><path d="M2315 4800 c-479 -35 -928 -217 -1303 -527 -352 -293 -615 -702 -738 -1151 -104 -380 -104 -824 0 -1204 107 -389 302 -724 591 -1013 354 -354 785 -572 1279 -646 196 -30 476 -30 672 0 494 74 925 292 1279 646 354 354 571 784 646 1279 30 197 30 475 0 672 -75 495 -292 925 -646 1279 -289 289 -624 484 -1013 591 -228 62 -528 91 -767 74z m353 -511 c458 -50 874 -272 1170 -624 417 -497 536 -1174 308 -1763 -56 -145 -176 -367 -235 -434 -4 -4 -566 552 -1250 1236 l-1243 1243 94 60 c354 229 754 327 1156 282z m864 -3200 c-67 -59 -289 -179 -434 -235 -946 -366 -2024 172 -2322 1158 -47 155 -66 276 -73 453 -13 362 84 704 290 1023 l60 94 1243 -1243 c684 -684 1240 -1246 1236 -1250z"/></g></svg>';
        html += '<div class="popup">';
        // + converter['id'] + ' is not operational<br>';
        //html += 'not operational. ';
        html += htmlEscape(converter['error']);
        html += '</div>';
    }
    html += '</div>';

    html += '</li>';
    return html;
}

/* Set ids on global converters object */
function setTemporaryIdsOnConverters() {
    var numConverterInstances = [];
    for (var i=0; i<converters.length; i++) {
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

    for (var i=0; i<window.converters.length; i++) {
        var converter = converters[i];
        html += generateConverterHTML(converter);
    }

    var el = document.getElementById('converters');
    el.innerHTML = html;

    var sortable = Sortable.create(el, {
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

function updateCacheControlCustomVisibility() {
    var cacheControlValue = document.getElementById('cache_control_select').value;
    var customEl = document.getElementById('cache_control_custom');
    if (cacheControlValue == 'custom') {
        customEl.setAttribute('type', 'text');
    } else {
        customEl.setAttribute('type', 'hidden');
    }
}

function updateQualityVisibility() {
    var qualityAutoEl = document.getElementById('quality_auto_select');
    if (!qualityAutoEl) {
        return;
    }
    var qualityAutoValue = qualityAutoEl.value;
    var maxQualityRowEl = document.getElementById('max_quality_row');
    var qualitySpecificRowEl = document.getElementById('quality_specific_row');

    //alert(qualityAutoValue);
    if (qualityAutoValue == 'auto_on') {
        maxQualityRowEl.style['display'] = 'table-row';
        qualitySpecificRowEl.style['display'] = 'none';
    } else {
        maxQualityRowEl.style['display'] = 'none';
        qualitySpecificRowEl.style['display'] = 'table-row';
    }

}

document.addEventListener('DOMContentLoaded', function() {
    //resetToDefaultConverters();
    addMissingConvertersAndOptions();
    addMissingOptions();
    setConvertersHTML();
    updateCacheControlCustomVisibility();
    updateQualityVisibility();

    document.getElementById('cache_control_select').addEventListener('change', function() {
        updateCacheControlCustomVisibility();
    });

    document.getElementById('quality_auto_select').addEventListener('change', function() {
        updateQualityVisibility();
    });

    //alert(sortable.toArray());
});

function wpe_addCloudConverter(converter) {

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

function configureConverter(id) {
    var converter = window.convertersMap[id];
    window.currentlyEditing = id;

    switch (converter['converter']) {
        case 'ewww':
            document.getElementById('ewww_key').value = getConverterOption(converter, 'key', '');
            document.getElementById('ewww_key_2').value = getConverterOption(converter, 'key-2', '');
            break;
        case 'wpc':
            document.getElementById('wpc_url').value = getConverterOption(converter, 'url', '');
            document.getElementById('wpc_secret').value = getConverterOption(converter, 'secret', '');
            document.getElementById('wpc_url_2').value = getConverterOption(converter, 'url-2', '');
            document.getElementById('wpc_secret_2').value = getConverterOption(converter, 'secret-2', '');
            break;
        case 'gd':
            document.getElementById('gd_skip_pngs').checked = getConverterOption(converter, 'skip-pngs', '');
            break;
        case 'cwebp':
            document.getElementById('cwebp_use_nice').checked = getConverterOption(converter, 'use-nice', '');
            document.getElementById('cwebp_method').value = getConverterOption(converter, 'method', '');
            document.getElementById('cwebp_try_common_system_paths').checked = getConverterOption(converter, 'try-common-system-paths', '');
            document.getElementById('cwebp_try_supplied_binary').checked = getConverterOption(converter, 'try-supplied-binary-for-os', '');
            document.getElementById('cwebp_set_size').checked = getConverterOption(converter, 'set-size', '');
            document.getElementById('cwebp_size_in_percentage').value = getConverterOption(converter, 'size-in-percentage', '');
            document.getElementById('cwebp_command_line_options').value = getConverterOption(converter, 'command-line-options', '');
            break;

    }
    tb_show("Configure " + converter['id'] + ' converter', '#TB_inline?inlineId=' + converter['converter']);
}

function updateConverterOptions() {
    var converter = window.convertersMap[window.currentlyEditing];

    switch (converter['converter']) {
        case 'ewww':
            setConverterOption(converter, 'key', document.getElementById('ewww_key').value);
            setConverterOption(converter, 'key-2', document.getElementById('ewww_key_2').value);
            break;
        case 'wpc':
            setConverterOption(converter, 'url', document.getElementById('wpc_url').value);
            setConverterOption(converter, 'secret', document.getElementById('wpc_secret').value);
            setConverterOption(converter, 'url-2', document.getElementById('wpc_url_2').value);
            setConverterOption(converter, 'secret-2', document.getElementById('wpc_secret_2').value);
            break;
        case 'gd':
            setConverterOption(converter, 'skip-pngs', document.getElementById('gd_skip_pngs').checked);
            break;
        case 'cwebp':
            setConverterOption(converter, 'use-nice', document.getElementById('cwebp_use_nice').checked);
            setConverterOption(converter, 'method', document.getElementById('cwebp_method').value);
            setConverterOption(converter, 'try-common-system-paths', document.getElementById('cwebp_try_common_system_paths').checked);
            setConverterOption(converter, 'try-supplied-binary-for-os', document.getElementById('cwebp_try_supplied_binary').checked);
            setConverterOption(converter, 'set-size', document.getElementById('cwebp_set_size').checked);
            setConverterOption(converter, 'size-in-percentage', document.getElementById('cwebp_size_in_percentage').value);
            setConverterOption(converter, 'command-line-options', document.getElementById('cwebp_command_line_options').value);
            break;
    }
    updateInputValue();
    tb_remove();
    document.getElementById('webpexpress_settings').submit();
}

function testConverter(id) {
    //alert('h' + id);
    var converter = window.convertersMap[id];

    // https://stackoverflow.com/questions/4321068/to-invoke-thickbox-using-javascript

    var urls = window.webpExpressPaths['urls'];
    var paths = window.webpExpressPaths['filePaths'];

    var url = '/' + urls['webpExpressRoot'] + '/test/test-run.php';
    //alert(url);


    // test images here: http://nottinghamtec.co.uk/~aer/TestPatterns/1080/
    filename = 'test.jpg';
    filename = 'stones.jpg';
    filename = 'architecture2.jpg';
    filename = 'test1.png';
    filename = 'focus.jpg';

    url += '?source=' + paths['webpExpressRoot'] + '/test/' + filename;
    url += '&destination=' + paths['destinationRoot'] + '/test-conversions/' + filename + '.webp';
    url += '&converter=' + converter['converter'];
    url += '&max-quality=' + document.getElementsByName('max-quality')[0].value;
    //url += '&method=' + document.getElementsByName('webp_express_method')[0].value;

    if (converter.options) {
        for (var option in converter.options) {
            if (converter.options.hasOwnProperty(option)) {
                //alert(option);
                url += '&' + option + '=' + converter.options[option];
            }
        }
    }
    url += '&TB_iframe=true&width=400&height=300';
    //alert(url);
    tb_show("Test running converter: " + converter['id'], url);
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

function addConverterClick() {
    // width=600&height=550&inlineId=add-cloud-converter-id
    var options = '#TB_inline?inlineId=add-cloud-converter-id';
    tb_show("Add converter", options);
}
