
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

    document.getElementById('cache_control_select').addEventListener('change', function() {
        updateCacheControlCustomVisibility();
    });

    if (document.getElementById('quality_auto_select')) {
        document.getElementById('quality_auto_select').addEventListener('change', function() {
            updateQualityVisibility();
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
