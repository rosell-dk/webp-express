
function openDeleteLogFilesPopup() {
    var html = '';
    html += '<p><b>Are you sure you want to do that?</b></p>';
    html += '<p>' +
      'The log files contain information about the conversion settings used for each webp and the libraries ' +
      'used, and the versions. This information will be visible in the conversion manager in a not too far future. ' +
      'The information might also be used to notify you if the libraries / version of a library you have is ' +
      'significantly better than the one used for the conversion. ' +
      'If you delete the log files, you will not benefit from this functionality. ' +
    '</p>';

    html += '<p>' +
      'The log files are btw located in <i>wp-content/webp-express/log/</i>, if you want to have a closer look.' +
    '</p>';
    //html += '<p>In a not too far future, the log files will be used in the conversion manager.</p>'
    //html += 'They could become handy.</p>'
    /*
    html += '<p>This action cannot be reversed. Your log files will be gone. '
    html += 'Dead. Completely. Forever. '
    html += '(Unless of course you have a backup. Or, of course, there are ways of recovery... Anyway...). '
    html += 'Ok, sorry for the babbeling. The dialog seemed bare without text.</p>';*/
    html += '<button onclick="purgeLog()" class="button button-secondary" type="button">Yes, delete!</button>';

    document.getElementById('purgelogcontent').innerHTML = '<div>' + html + '</div>';
    tb_show('Delete all log Files?', '#TB_inline?inlineId=purgelogpopup&height=320&width=450');

}

function closePurgeLogDialog() {
    tb_remove();
}

function purgeLog() {
    var data = {
        'action': 'webpexpress_purge_log',
        'nonce' : window.webpExpress['ajax-nonces']['purge-log'],
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

        var html = '<div><p>';

        if (result['fail-count'] == 0) {
            if (result['delete-count'] == 0) {
                html += 'No log files were found, so none was deleted.';
            } else {
                html += 'Successfully deleted ' + result['delete-count'] + ' log files';
            }
        } else {
            if (result['delete-count'] == 0) {
                html += 'Failed deleting ' + result['fail-count'] + ' log files. None was deleted, in fact.';
            } else {
                html += 'Deleted ' + result['delete-count'] + ' log files. However, failed deleting ' + result['fail-count'] + ' log files.';
            }
        }
        html += '</p>';
        html += '<button onclick="closePurgeLogDialog()" class="button button-secondary" type="button">Ok</button>';
        html += '</div>';

        document.getElementById('purgelogcontent').innerHTML = html;

    });
}
