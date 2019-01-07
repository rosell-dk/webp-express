function setOptionVisibility(elmId, show) {
    var elm = document.getElementById(elmId);
    if (!elm) {
        return;
    }
    if (show) {
        elm.style['visibility'] = 'visible';
        elm.style['position'] = 'static';
    } else {
        elm.style['visibility'] = 'hidden';
        elm.style['position'] = 'absolute';
    }
}
function updateCacheControlCustomVisibility() {

    if (document.getElementById('cache_control_select') == null) {
        // Well, it seems that the cache control option isn't available in this operation mode
        return;
    }
    if (document.getElementById('cache_control_custom') == null) {
        alert('document.getElementById("cache_control_custom") returns null. Strange! Please report.');
        return;
    }
    if (document.getElementById('cache_control_public') == null) {
        alert('document.getElementById("cache_control_public") returns null. Strange! Please report.');
        return;
    }

    var cacheControlValue = document.getElementById('cache_control_select').value;
    /*
    var customEl = document.getElementById('cache_control_custom');
    if (cacheControlValue == 'custom') {
        customEl.setAttribute('type', 'text');
    } else {
        customEl.setAttribute('type', 'hidden');
    }*/

    setOptionVisibility('cache_control_custom_div', (cacheControlValue == 'custom'));

    setOptionVisibility('cache_control_set_div', (cacheControlValue == 'set'));

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

function updateDestinationExtensionVisibility() {
    var destinationFolderEl = document.getElementById('destination_folder');
    if (!destinationFolderEl) {
        return;
    }
    var destinationFolderValue = destinationFolderEl.value;
    var destinationExtensionEl = document.getElementById('destination_extension_row');

    //alert(qualityAutoValue);
    if (destinationFolderValue == 'mingled') {
        destinationExtensionEl.style['display'] = 'table-row';
    } else {
        destinationExtensionEl.style['display'] = 'none';
    }

}

function updateServerSettingsVisibility() {
    var enabledEl = document.getElementById('web_service_enabled');
    if (!enabledEl) {
        return;
    }
    var enabled = enabledEl.checked;
    //document.getElementById('whitelist_row').style['display'] = (enabled ? 'table-row' : 'none');
    //document.getElementById('server_url').style['display'] = (enabled ? 'table-row' : 'none');
    document.getElementById('whitelist_div').style['display'] = (enabled ? 'block' : 'none');
}

document.addEventListener('DOMContentLoaded', function() {
    //resetToDefaultConverters();
    updateCacheControlCustomVisibility();
    updateQualityVisibility();
    updateServerSettingsVisibility();
    updateDestinationExtensionVisibility();

    if (document.getElementById('cache_control_select')) {
        document.getElementById('cache_control_select').addEventListener('change', function() {
            updateCacheControlCustomVisibility();
        });
    }

    if (document.getElementById('quality_auto_select')) {
        document.getElementById('quality_auto_select').addEventListener('change', function() {
            updateQualityVisibility();
        });
    }

    if (document.getElementById('destination_folder')) {
        document.getElementById('destination_folder').addEventListener('change', function() {
            updateDestinationExtensionVisibility();
        });
    }

    if (document.getElementById('web_service_enabled')) {
        document.getElementById('web_service_enabled').addEventListener('change', function() {
            updateServerSettingsVisibility();
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
