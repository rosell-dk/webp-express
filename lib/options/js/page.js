
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
    updateCacheControlCustomVisibility();
    updateQualityVisibility();

    document.getElementById('cache_control_select').addEventListener('change', function() {
        updateCacheControlCustomVisibility();
    });

    if (document.getElementById('quality_auto_select')) {
        document.getElementById('quality_auto_select').addEventListener('change', function() {
            updateQualityVisibility();
        });
    }

    //alert(sortable.toArray());
});
