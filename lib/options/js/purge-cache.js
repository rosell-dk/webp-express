
function openDeleteConvertedFilesPopup() {
//    document.getElementById('bulkconvertcontent').innerHTML = '<div>Reiceiving list of files to convert...</div>';
//    tb_show('Bulk Convert', '#TB_inline?inlineId=bulkconvertpopup');
    purgeCache();
}

function purgeCache() {
    var data = {
        'action': 'webpexpress_purge_cache',
        'only-png': false
    };
    jQuery.post(ajaxurl, data, function(response) {
        var result = JSON.parse(response);
        console.log(result);

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
