function toggleVisibility(elmId, show) {
    var elm = document.getElementById(elmId);
    if (!elm) {
        return;
    }
    elm.classList.add('toggler');
    if (!elm.style.maxHeight) {
        elm.style['maxHeight'] = (elm.clientHeight + 40) + 'px';
    }
    if (show) {
        elm.classList.remove('closed');
    } else {
        elm.classList.add('closed');
    }
}




document.addEventListener('DOMContentLoaded', function() {
    //resetToDefaultConverters();
    function el(elmId) {
        return document.getElementById(elmId);
    }

    if (el('cache_control_select') && el('cache_control_custom_div') && el('cache_control_set_div')) {
        el('cache_control_custom_div').classList.add('effect-visibility');
        el('cache_control_set_div').classList.add('effect-visibility');
        function updateCacheControlCustomVisibility() {

            var cacheControlValue = document.getElementById('cache_control_select').value;
            /*
            var customEl = document.getElementById('cache_control_custom');
            if (cacheControlValue == 'custom') {
                customEl.setAttribute('type', 'text');
            } else {
                customEl.setAttribute('type', 'hidden');
            }*/

            toggleVisibility('cache_control_custom_div', (cacheControlValue == 'custom'));

            toggleVisibility('cache_control_set_div', (cacheControlValue == 'set'));

        }
        updateCacheControlCustomVisibility();
        el('cache_control_select').addEventListener('change', function() {
            updateCacheControlCustomVisibility();
        });
    }


    // In "No conversion" mode, toggle cache control div when redirect is enabled/disabled
    if (el('operation_mode') && (el('operation_mode').value == 'no-conversion')) {
        if (el('redirect_to_existing_in_htaccess') && el('cache_control_div')) {
            el('cache_control_div').classList.add('effect-opacity');
            function updateCacheControlHeaderVisibility() {
                toggleVisibility('cache_control_div', el('redirect_to_existing_in_htaccess').checked);
            }
            updateCacheControlHeaderVisibility();
            el('redirect_to_existing_in_htaccess').addEventListener('change', function() {
                updateCacheControlHeaderVisibility();
            });
        }

    }

    // Toggle Quality (auto / specific)
    if (el('quality_auto_select') && el('max_quality_row') && el('quality_specific_row')) {
        function updateQualityVisibility() {
            var qualityAutoValue = el('quality_auto_select').value;
            if (qualityAutoValue == 'auto_on') {
                el('max_quality_row').style['display'] = 'table-row';
                el('quality_specific_row').style['display'] = 'none';
            } else {
                el('max_quality_row').style['display'] = 'none';
                el('quality_specific_row').style['display'] = 'table-row';
            }
        }
        updateQualityVisibility();
        el('quality_auto_select').addEventListener('change', function() {
            updateQualityVisibility();
        });
    }

    // Toggle File Extension (only show when "mingled" is selected)
    if (el('destination_folder') && el('destination_extension_row') && el('destination_extension')) {
        el('destination_extension_row').classList.add('effect-opacity');
        function updateDestinationExtensionVisibility() {
            toggleVisibility('destination_extension_row', el('destination_folder').value == 'mingled');

            if (el('destination_folder').value == 'mingled') {
                el('destination_extension').value = 'append';
            }
            /*
            if (document.getElementById('destination_folder').value == 'mingled') {
                el('destination_extension_row').style['display'] = 'table-row';
            } else {
                el('destination_extension_row').style['display'] = 'none';
            }*/

        }
        updateDestinationExtensionVisibility();
        document.getElementById('destination_folder').addEventListener('change', function() {
            updateDestinationExtensionVisibility();
        });
    }

    // Toggle webservice
    if (el('web_service_enabled') && el('whitelist_div')) {
        el('whitelist_div').classList.add('effect-opacity');
        function updateServerSettingsVisibility() {
            toggleVisibility('whitelist_div', el('web_service_enabled').checked);
            //document.getElementById('whitelist_div').style['display'] = (el('web_service_enabled').checked ? 'block' : 'none');
        }
        updateServerSettingsVisibility();
        document.getElementById('web_service_enabled').addEventListener('change', function() {
            updateServerSettingsVisibility();
        });
    }

    // Toggle Alter HTML options when Alter HTML is enabled / disabled
    if (el('alter_html_enabled') && (el('alter_html_options_div'))) {
        el('alter_html_options_div').classList.add('effect-opacity');
        function updateAlterHTMLVisibility() {
            toggleVisibility('alter_html_options_div', el('alter_html_enabled').checked);
        }
        updateAlterHTMLVisibility();
        el('alter_html_enabled').addEventListener('change', function() {
            updateAlterHTMLVisibility();
        });
    }

    // Show/hide "Only do the replacements in webp enabled browsers" when "What to replace" is changed
    if (el('alter_html_replacement_url') && el('alter_html_url_options_div')) {
        el('alter_html_url_options_div').classList.add('effect-opacity');
        function updateAlterHTMLReplaceVisibility() {
            toggleVisibility('alter_html_url_options_div', el('alter_html_replacement_url').checked);
        }
        updateAlterHTMLReplaceVisibility();

        el('alter_html_replacement_url').addEventListener('change', function() {
            updateAlterHTMLReplaceVisibility();
        });
        el('alter_html_replacement_picture').addEventListener('change', function() {
            updateAlterHTMLReplaceVisibility();
        });
    }





    document.getElementById('change_operation_mode').addEventListener('change', function() {
        var msg;
        if (document.getElementById('operation_mode').value == 'tweaked') {
            msg = 'Save configuration and change mode? Any tweaks will be lost';
        } else {
            if (document.getElementById('change_operation_mode').value == 'tweaked') {
                msg = 'Save configuration and change to tweaked mode? No options are lost when changing to tweaked mode (it will behave the same way as currently, until you start tweaking)';
            } else {
                msg = 'Save configuration and change mode?';
            }
        }

        if (confirm(msg)) {
            document.getElementById('webpexpress_settings').submit();
        } else {
            // undo select box change
            document.getElementById('change_operation_mode').value = document.getElementById('operation_mode').value;
            return;
        }

    });

});
