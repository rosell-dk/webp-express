
function openBulkConvertPopup() {
    document.getElementById('bulkconvertcontent').innerHTML = '<div>Reiceiving list of files to convert...</div>';
    tb_show('Bulk Convert', '#TB_inline?inlineId=bulkconvertpopup');

    var data = {
		'action': 'list_unconverted_files',
		//'whatever': ajax_object.we_value      // We pass php values differently!
	};
    jQuery.post(ajaxurl, data, function(response) {
        var bulkInfo = {
            'groups': JSON.parse(response),
            'groupPointer': 0,
            'filePointer': 0,
            'paused': false
        };
        window.webpexpress_bulkconvert = bulkInfo;

        // count files
        var numFiles = 0;
        for (var i=0; i<bulkInfo.groups.length; i++) {
            numFiles += bulkInfo.groups[i].files.length;
        }

        //console.log(JSON.parse(response));
        var html = '';
        if (numFiles == 0) {
            html += '<p>There are no unconverted files</p>';
        } else {
            html += '<div>'
            html += '<p>There are ' + numFiles + ' unconverted files.</p><br>';
            html += '<button onclick="startBulkConversion()" class="button button-primary" type="button">Start conversion</button>';
            html += '<p>PS: Using the last saved settings</p>';
            html += '</div>';

        }
        document.getElementById('bulkconvertcontent').innerHTML = html;
    });
}

function pauseBulkConversion() {
    var bulkInfo = window.webpexpress_bulkconvert;
    bulkInfo.paused = true;
}

function pauseOrResumeBulkConversion() {
    var bulkInfo = window.webpexpress_bulkconvert;
    bulkInfo.paused = !bulkInfo.paused;

    document.getElementById('bulkPauseResumeBtn').innerText = (bulkInfo.paused ? 'Resume' : 'Pause');

    if (!bulkInfo.paused) {
        convertNextInBulkQueue();
    }
}

function startBulkConversion() {
    var html = '<br>';
    html += '<button id="bulkPauseResumeBtn" onclick="pauseOrResumeBulkConversion()" class="button button-primary" type="button">Pause</button>';
    html += '<div id="bulkconvertlog"></div>';
    document.getElementById('bulkconvertcontent').innerHTML = html;

    convertNextInBulkQueue();
}

function convertNextInBulkQueue() {
    var html;
    var bulkInfo = window.webpexpress_bulkconvert;

    if ((bulkInfo.groupPointer >= bulkInfo.groups.length) || (bulkInfo.filePointer >= bulkInfo.groups[bulkInfo.groupPointer].files.length)) {
        // this presumably only happens when there are no files to convert.
        //document.getElementById('bulkconvertcontent').innerHTML += 'Nothing to convert';
        return;
    }

    var group = bulkInfo.groups[bulkInfo.groupPointer];
    var filename = group.files[bulkInfo.filePointer];

    if (bulkInfo.filePointer == 0) {
        html = '<h3>' + group.groupName + '</h3>';
        //html += '<p>root: ' + group.root + '</p><br>';
        document.getElementById('bulkconvertlog').innerHTML += html;
    }

    html = 'Converting <i>' + filename + '</i>';
    document.getElementById('bulkconvertlog').innerHTML += html;

    var data = {
		'action': 'convert_file',
        'filename': group.root + '/' + filename

		//'whatever': ajax_object.we_value      // We pass php values differently!
    };
    
    function responseCallback(response){
        var result = typeof response.requestError !== 'boolean' ? JSON.parse(response) : {
            success: false,
            msg: '',
            log: '',
        };
        //console.log(result);

        var html = '';
        if (result['success']) {
            html += ' <span style="color:green">ok</span><br>';
        } else {
            html += ' <span style="color:red">failed</span><br>';
            if (result['msg'] != '') {
                html += ' <span style="">' + result['msg'] + '</span>';
            }
            if (result['log'] != '') {
                html += ' <span style="font-size:10px">' + result['log'] + '</span>';
            }
        }
        document.getElementById('bulkconvertlog').innerHTML += html;

        var bulkInfo = window.webpexpress_bulkconvert;
        var group = bulkInfo.groups[bulkInfo.groupPointer];

        // Get next
        bulkInfo.filePointer++;
        if (bulkInfo.filePointer == group.files.length) {
            bulkInfo.filePointer = 0;
            bulkInfo.groupPointer++;
        }
        if (bulkInfo.groupPointer == bulkInfo.groups.length) {
            document.getElementById('bulkconvertlog').innerHTML += '<p><b>Done!</b></p>';
            document.getElementById('bulkPauseResumeBtn').style.display = 'none';
        } else {
            if (bulkInfo.paused) {
                document.getElementById('bulkconvertlog').innerHTML += '<p><i>on pause</i></p>';
            } else {
                convertNextInBulkQueue();
            }
        }

    }

    // jQuery.post(ajaxurl, data, responseCallback);
    jQuery.ajax({
        method: 'POST',
        url: ajaxurl,
        data: data,
        success: (response) => {
            responseCallback(response);
        },
        error: () => {
            responseCallback({requestError: true});
        },
    });
}



//alert('bulk');
/*
jQuery(document).ready(function($) {
});
*/
