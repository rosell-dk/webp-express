function toggleVisibility(elmId, show) {
    var elm = document.getElementById(elmId);
    if (!elm) {
        return;
    }
    elm.classList.add('toggler');
    /*
    if (!elm.style.maxHeight) {
        elm.style['maxHeight'] = (elm.clientHeight + 40) + 'px';
    }*/
    if (show) {
        elm.classList.remove('closed');
    } else {
        elm.classList.add('closed');
    }
}

function updateAlterHTMLChartVisibility(show) {
    function el(elmId) {
        return document.getElementById(elmId);
    }


    var elm = el('alter_html_comparison_chart');
    //elm.style['maxHeight'] = (elm.clientHeight + 40) + 'px';
    //elm.style['maxHeight'] = '600px';
    //elm.style.display = (show ? 'block' : 'none');
    if (show) {
        elm.classList.remove('closed');
    } else {
        elm.classList.add('closed');
    }


    el('hide_alterhtml_chart_btn').style.display = (show ? 'block' : 'none');
    el('show_alterhtml_chart_btn').style.display = (show ? 'none' : 'inline-block');
    el('ui_show_alter_html_chart').value = (show ? 'true' : 'false');


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


    // In "No conversion" and "CDN friendly" mode, toggle cache control div when redirect is enabled/disabled
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

    if (el('only_redirect_to_converter_for_webp_enabled_browsers_row') && el('enable_redirection_to_converter')) {
        el('only_redirect_to_converter_for_webp_enabled_browsers_row').classList.add('effect-opacity');
        el('only_redirect_to_converter_on_cache_miss_row').classList.add('effect-opacity');
        function updateRedirectionOnlyWebPVisibility() {
            toggleVisibility('only_redirect_to_converter_for_webp_enabled_browsers_row', el('enable_redirection_to_converter').checked);
            toggleVisibility('only_redirect_to_converter_on_cache_miss_row', el('enable_redirection_to_converter').checked);

        }
        updateRedirectionOnlyWebPVisibility();
        el('enable_redirection_to_converter').addEventListener('change', function() {
            updateRedirectionOnlyWebPVisibility();
        });
    }


    // Toggle Quality (auto / specific)
    if (el('quality_auto_select') && el('max_quality_div') && el('quality_specific_div')) {
        function updateQualityVisibility() {
            var qualityAutoValue = el('quality_auto_select').value;
            toggleVisibility('max_quality_div', el('quality_auto_select').value == 'auto_on');
            toggleVisibility('quality_specific_div', el('quality_auto_select').value != 'auto_on');
            /*
            if (qualityAutoValue == 'auto_on') {
                el('max_quality_div').style['display'] = 'inline-block';
                el('quality_specific_div').style['display'] = 'none';
            } else {
                el('max_quality_div').style['display'] = 'none';
                el('quality_specific_div').style['display'] = 'inline-block';
            }*/
        }
        updateQualityVisibility();
        el('quality_auto_select').addEventListener('change', function() {
            updateQualityVisibility();
        });
    }

    // Jpeg encoding changing
    if (el('jpeg_encoding_select') && el('jpeg_quality_lossless_div')) {
        function updateJpgEncoding() {
            toggleVisibility('jpeg_quality_lossless_div', el('jpeg_encoding_select').value != 'lossy');
        }
        updateJpgEncoding();
        el('jpeg_encoding_select').addEventListener('change', function() {
            updateJpgEncoding();
        });
    }

    // Jpeg near-lossless toggling
    if (el('jpeg_enable_near_lossless') && el('jpeg_near_lossless_div')) {
        function updateNearLosslessVisibilityJpeg() {
            toggleVisibility('jpeg_near_lossless_div', el('jpeg_enable_near_lossless').value == 'on');
        }
        updateNearLosslessVisibilityJpeg();
        el('jpeg_enable_near_lossless').addEventListener('change', function() {
            updateNearLosslessVisibilityJpeg();
        });
    }

    // PNG encoding changing
    if (el('image_types') && el('png_row')) {
        function updatePngAndJpgRowVisibilities() {
            var imageTypes = parseInt(el('image_types').value, 10);
            var pngEnabled = (imageTypes & 2);
            var jpegEnabled = (imageTypes & 1);
            toggleVisibility('png_row', pngEnabled);
            toggleVisibility('jpeg_row', jpegEnabled);
        }
        updatePngAndJpgRowVisibilities();
        el('image_types').addEventListener('change', function() {
            updatePngAndJpgRowVisibilities();
        });
    }



    // PNG encoding changing
    if (el('png_encoding_select') && el('png_quality_lossy_div')) {
        function updatePngEncoding() {
            toggleVisibility('png_quality_lossy_div', el('png_encoding_select').value != 'lossless');
        }
        updatePngEncoding();
        el('png_encoding_select').addEventListener('change', function() {
            updatePngEncoding();
        });
    }

    // PNG near-lossless toggling
    if (el('png_enable_near_lossless') && el('png_near_lossless_div')) {
        function updateNearLosslessVisibilityPng() {
            toggleVisibility('png_near_lossless_div', el('png_enable_near_lossless').value == 'on');
        }
        updateNearLosslessVisibilityPng();
        el('png_enable_near_lossless').addEventListener('change', function() {
            updateNearLosslessVisibilityPng();
        });
    }

    // If "doc-root" cannot be used for structuring, disable the option and set to "image-roots"
    if (!window.webpExpress['can-use-doc-root-for-structuring']) {
        el('destination_structure').classList.add('effect-opacity');
        toggleVisibility('destination_structure', false);

        if (el('destination_structure').value == 'doc-root') {
            el('destination_structure').value = 'image-roots';
        }

    }

    // Toggle File Extension (only show when "mingled" is selected)
    if (el('destination_folder') && el('destination_extension')) {
        el('destination_extension').classList.add('effect-opacity');
        function updateDestinationExtensionVisibility() {
            toggleVisibility('destination_extension', el('destination_folder').value == 'mingled');

            if (el('destination_folder').value == 'separate') {
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

    // Toggle Alter HTML options
    if (el('alter_html_enabled') && (el('alter_html_options_div'))) {
        el('alter_html_options_div').classList.add('effect-opacity');
        el('alter_html_comparison_chart').classList.add('effect-slider');
        function updateAlterHTMLVisibility() {
            toggleVisibility('alter_html_options_div', el('alter_html_enabled').checked);
        //    toggleVisibility('alter_html_comparison_chart', el('alter_html_enabled').checked);

        }
        updateAlterHTMLVisibility();
        el('alter_html_enabled').addEventListener('change', function() {
            updateAlterHTMLVisibility();
        });
    }

    // Show/hide "Only do the replacements in webp enabled browsers" when "What to replace" is changed
    if (el('alter_html_replacement_url') && el('alter_html_url_options_div')) {
        el('alter_html_url_options_div').classList.add('effect-opacity');
        el('alter_html_picture_options_div').classList.add('effect-opacity');
        function updateAlterHTMLReplaceVisibility() {
            toggleVisibility('alter_html_url_options_div', el('alter_html_replacement_url').checked);
            toggleVisibility('alter_html_picture_options_div', el('alter_html_replacement_picture').checked);
        }
        updateAlterHTMLReplaceVisibility();

        el('alter_html_replacement_url').addEventListener('change', function() {
            updateAlterHTMLReplaceVisibility();
        });
        el('alter_html_replacement_picture').addEventListener('change', function() {
            updateAlterHTMLReplaceVisibility();
        });
    }

    if (el('ui_show_alter_html_chart') && el('alter_html_comparison_chart')) {
        var elm = el('alter_html_comparison_chart');
        elm.style['maxHeight'] = (elm.clientHeight + 80) + 'px';

        updateAlterHTMLChartVisibility(el('ui_show_alter_html_chart').value == 'true');
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
