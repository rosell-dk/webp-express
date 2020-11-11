
function openDeleteConvertedFilesPopup() {
    var html = '';
    html += '<p>To delete all converted files, click this button:<br>';
    html += '<button onclick="purgeCache(false)" class="button button-secondary" type="button">Delete all converted files</button>';
    html += '</p>';
    html += '<p>Or perhaps, you only want to delete the converted <i>PNGs</i>? Then this button is for you:<br>';
    html += '<button onclick="purgeCache(true)" class="button button-secondary" type="button">Delete converted PNGs</button>';
    html += '</p>';

    document.getElementById('purgecachecontent').innerHTML = html;
    tb_show('Purge cache', '#TB_inline?inlineId=purgecachepopup');
//    purgeCache();
}

function purgeCache(onlyPng) {
    var data = {
        'action': 'webpexpress_purge_cache',
        'nonce' : window.webpExpress['ajax-nonces']['purge-cache'],
        'only-png': onlyPng
    };
    jQuery.post(ajaxurl, data, function(response) {
        if ((typeof response == 'object') && (response['success'] == false)) {
            if (response['data'] && ((typeof response['data']) == 'string')) {
                alert(response['data']);
            } else {
                alert('Something failed');
            }
            return;
        }

        var result = JSON.parse(response);
        //console.log(result);

        if (result['fail-count'] == 0) {
            if (result['delete-count'] == 0) {
                alert('No webp files were found, so none was deleted.');
            } else {
                alert('Successfully deleted ' + result['delete-count'] + ' webp files');
            }
        } else {
            if (result['delete-count'] == 0) {
                alert('Failed deleting ' + result['fail-count'] + ' webp files. None was deleted, in fact.');
            } else {
                alert('Deleted ' + result['delete-count'] + ' webp files. However, failed deleting ' + result['fail-count'] + ' webp files.');
            }
        }

    });
}
