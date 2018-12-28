
function updateCacheControlCustomVisibility() {

    if (document.getElementById('cache_control_select') == null) {
        // this ought not to happen,
        // but it does? https://wordpress.org/support/topic/console-errors-9/#post-11018243
        alert('document.getElementById("cache_control_select") returns null. Strange! Please report.');
        return;
    }
    if (document.getElementById('cache_control_custom') == null) {
        alert('document.getElementById("cache_control_custom") returns null. Strange! Please report.');
        return;
    }
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

    document.getElementById('cache_control_select').addEventListener('change', function() {
        updateCacheControlCustomVisibility();
    });

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

    document.getElementById('web_service_enabled').addEventListener('change', function() {
        updateServerSettingsVisibility();
    });

    // Dot animation
    window.setInterval(function() {
        var dotElms = document.getElementsByClassName('animated-dots');
        for (var i=0; i<dotElms.length; i++) {
            var el = dotElms[i];
            if (el.innerText == '....') {
                el.innerText = '';
            } else {
                el.innerText += '.';
            }
        }
    }, 500);

    //alert(sortable.toArray());
});
