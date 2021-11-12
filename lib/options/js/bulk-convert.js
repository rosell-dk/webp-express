
function openBulkConvertPopup() {
    document.getElementById('bulkconvertlog').innerHTML = '';
    document.getElementById('bulkconvertcontent').innerHTML = '<div>Receiving list of files to convert...</div>';
    tb_show('Bulk Convert', '#TB_inline?inlineId=bulkconvertpopup');

    var data = {
		    'action': 'list_unconverted_files',
        'nonce' : window.webpExpress['ajax-nonces']['list-unconverted-files'],
    };
    jQuery.ajax({
      type: "POST",
      url: ajaxurl,
      data: data,
      dataType: 'text',
      timeout: 30000,
      error: function (jqXHR, status, errorThrown) {
          html = '<h1>Error: ' + status + '</h1>';
          html += errorThrown;
          document.getElementById('bulkconvertcontent').innerHTML = html;
      },
      success: function(response) {
          if ((typeof response == 'object') && (response['success'] == false)) {
              html = '<h1>Error</h1>';
              if (response['data'] && ((typeof response['data']) == 'string')) {
                  html += webpexpress_escapeHTML(response['data']);
              }
              document.getElementById('bulkconvertcontent').innerHTML = html;
              return;
          }
          if (response == '') {
              html = '<h1>Error</h1>';
              html += '<p>Could not fetch list of files to convert. The server returned nothing (which is unexpected - ' +
                  'it is not simply because there are no files to convert.)</p>';
              document.getElementById('bulkconvertcontent').innerHTML = html;
              return;
          }
          var responseObj;
          try {
              responseObj = JSON.parse(response);
          } catch (e) {
              html = '<h1>Error</h1>';
              html += '<p>The ajax call did not return valid JSON, as expected.</p>';
              html += '<p>Check the javascript console to see what was returned.</p>';
              console.log('The ajax call did not return valid JSON, as expected');
              console.log('Here is what was received:');
              console.log(response);
              document.getElementById('bulkconvertcontent').innerHTML = html;
              return;
          }
          var bulkInfo = {
              'groups': responseObj,
              'groupPointer': 0,
              'filePointer': 0,
              'paused': false,
              'webpTotalFilesize': 0,
              'orgTotalFilesize': 0,
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
              html += '<p>There are ' + numFiles + ' unconverted files.</p>';
              html += '<p><i>Note that in a typical setup, you will have redirect rules which trigger conversion when needed, ' +
                      'and thus you have no need for bulk conversion. In fact, in that case, you should probably not bulk convert ' +
                      'because bulk conversion will also convert images and thumbnails which are not in use, and thus take up ' +
                      'more disk space than necessary. The bulk conversion feature was only added in order to make the plugin usable even when ' +
                      'there are problems with redirects (ie on Nginx in case you do not have access to the config or on Microsoft IIS). ' +
                      '</i></p><br>';
              html += '<button onclick="startBulkConversion()" class="button button-primary" type="button">Start conversion</button>';
              html += '</div>';
          }
          document.getElementById('bulkconvertcontent').innerHTML = html;
      }
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
    html += '<style>' +
        '.has-tip {cursor:pointer; position:static;}\n' +
        '.has-tip .tip {display: none}\n' +
        '.has-tip:hover .tip {display: block}\n' +
        '.tip{padding: 5px 10px; background-color:#ff9;min-width:310px; font-size:10px; color: black; border:1px solid black; max-width:90%;z-index:10}\n' +
        '.reduction {float:right;}\n' +
        '</style>';
    html += '<button id="bulkPauseResumeBtn" onclick="pauseOrResumeBulkConversion()" class="button button-primary" type="button">Pause</button>';
    //html += '<div id="conversionlog" class="das-popup">test</div>';
    //html += '<div id="bulkconvertlog"></div>';
    document.getElementById('bulkconvertcontent').innerHTML = html;
    document.getElementById('bulkconvertlog').innerHTML = '';

    convertNextInBulkQueue();
}

function convertDone() {
    var bulkInfo = window.webpexpress_bulkconvert;
    document.getElementById('bulkconvertlog').innerHTML += '<p><b>Done!</b></p>' +
        '<p>Total reduction: ' + getReductionHtml(bulkInfo['orgTotalFilesize'], bulkInfo['webpTotalFilesize'], 'Total size of converted originals', 'Total size of converted webp files') + '</p>'

    document.getElementById('bulkPauseResumeBtn').style.display = 'none';
}

function getPrintableSizeInfo(orgSize, webpSize) {
    if (orgSize < 10000) {
        return {
            'org': orgSize + ' bytes',
            'webp': webpSize + ' bytes'
        };
    } else {
        return {
            'org': Math.round(orgSize / 1024) + ' kb',
            'webp': Math.round(webpSize / 1024) + ' kb'
        };
    }
}

function getReductionHtml(orgSize, webpSize, sizeOfOriginalText, sizeOfWebpText) {
    var reduction = Math.round((orgSize - webpSize)/orgSize * 100);
    var sizeInfo = getPrintableSizeInfo(orgSize, webpSize);
    var hoverText = sizeOfOriginalText + ': ' + sizeInfo['org'] + '.<br>' + sizeOfWebpText + ': ' + sizeInfo['webp'];

    // ps: this is all safe to print
    return '<span class="has-tip reduction">' + reduction + '%' +
        '<span class="tip">' + hoverText + '</span>' +
    '</span><br>';
}

function logLn() {
    var html = '';
    for (i = 0; i < arguments.length; i++) {
        html += arguments[i];
    }
    var spanEl = document.createElement('span');
    spanEl.innerHTML = html;

    document.getElementById('bulkconvertlog').appendChild(spanEl);

    //document.getElementById('bulkconvertlog').innerHTML += html;
}

function webpexpress_viewLog(groupPointer, filePointer) {

/*
    disabled until I am certain that security is in place.

    var bulkInfo = window.webpexpress_bulkconvert;
    var group = bulkInfo.groups[groupPointer];
    var filename = group.files[filePointer];
    var source = group.root + '/' + filename;

    var w = Math.min(1200, Math.max(200, document.documentElement.clientWidth - 100));
    var h = Math.max(250, document.documentElement.clientHeight - 80);

    document.getElementById('conversionlog_content').innerHTML = 'loading log...'; // + source;

    jQuery.ajax({
        method: 'POST',
        url: ajaxurl,
        data: {
            'action': 'webpexpress_view_log',
            'nonce' : window.webpExpress['ajax-nonces']['view-log'],
            'source': source
        },
        success: (response) => {
            //alert(response);
            if ((typeof response == 'object') && (response['success'] == false)) {
                html = '<h1>Error</h1>';
                if (response['data'] && ((typeof response['data']) == 'string')) {
                    html += webpexpress_escapeHTML(response['data']);
                }
                document.getElementById('conversionlog_content').innerHTML = html;
                return;
            }

            var result = JSON.parse(response);

            // the "log" result is a simply form of markdown, using just italic, bold and newlines.
            // It ought not to return anything evil, but for good practice, let us encode.
            result = webpexpress_escapeHTML(result);

            var html = '<h1>Conversion log</h1><br>' + '<pre style="white-space:pre-wrap">' + result + '</pre>';

            document.getElementById('conversionlog_content').innerHTML = html;
        },
        error: () => {
            //responseCallback({requestError: true});
        },
    });

    //<h1>Conversion log</h1>
    //tb_show('Conversion log', '#TB_inline?inlineId=conversionlog');
    openDasPopup('conversionlog', w, h);
*/
}

function convertNextInBulkQueue() {
    var html;
    var bulkInfo = window.webpexpress_bulkconvert;
    //console.log('convertNextInBulkQueue', bulkInfo);

    // Current group might contain 0, - skip if that is the case
    while ((bulkInfo.groupPointer < bulkInfo.groups.length) && (bulkInfo.filePointer >= bulkInfo.groups[bulkInfo.groupPointer].files.length)) {
        logLn(
            '<h3>' + bulkInfo.groups[bulkInfo.groupPointer].groupName + '</h3>',
            '<p>Nothing to convert</p>'
        );

        bulkInfo.groupPointer++;
        bulkInfo.filePointer = 0;
    }

    if (bulkInfo.groupPointer >= bulkInfo.groups.length) {
        convertDone();
        return;
    }

    var group = bulkInfo.groups[bulkInfo.groupPointer];
    var filename = group.files[bulkInfo.filePointer];

    if (bulkInfo.filePointer == 0) {
        logLn('<h3>' + group.groupName + '</h3>');
    }

    logLn('Converting <i>' + filename + '</i>');

    var data = {
		'action': 'convert_file',
        'nonce' : window.webpExpress['ajax-nonces']['convert'],
        'filename': group.root + '/' + filename

		//'whatever': ajax_object.we_value      // We pass php values differently!
    };

    function responseCallback(response){
        if ((typeof response == 'object') && (response['success'] == false)) {
            html = '<h1>Error</h1>';
            if (response['data'] && ((typeof response['data']) == 'string')) {
                // disabled. Need to check if it is secure
                //html += webpexpress_escapeHTML(response['data']);
            }
            logLn(html);
            return
        }

        var result = typeof response.requestError !== 'boolean' ? JSON.parse(response) : {
            success: false,
            msg: '',
            log: '',
        };

        var bulkInfo = window.webpexpress_bulkconvert;
        var group = bulkInfo.groups[bulkInfo.groupPointer];
        var filename = group.files[bulkInfo.filePointer];

        var result = JSON.parse(response);  // TODO: An parse error has been experienced (perhaps when token expired?)

        //console.log(result);

        var html = '';

        var htmlViewLog = '';

        // uncommented until I'm certain that security is in place
        //var htmlViewLog = '&nbsp;&nbsp;<a style="cursor:pointer" onclick="webpexpress_viewLog(' + bulkInfo.groupPointer + ',' + bulkInfo.filePointer  + ')">view log</a>';
        if (result['success']) {

            //console.log('nonce tick:' + result['nonce-tick']);

            if (result['new-convert-nonce']) {
                //console.log('new convert nonce:' + result['new-convert-nonce']);
                window.webpExpress['ajax-nonces']['convert'] = result['new-convert-nonce'];
            }

            var orgSize = result['filesize-original'];
            var webpSize = result['filesize-webp'];
            var orgSizePrint, webpSizePrint;

            bulkInfo['orgTotalFilesize'] += orgSize;
            bulkInfo['webpTotalFilesize'] += webpSize;
            //'- Saved at: ' + result['destination-path'] +
/*
            html += ' <span style="color:green">ok</span></span>' +
                htmlViewLog +
                getReductionHtml(orgSize, webpSize, 'Size of original', 'Size of webp')*/

            html += ' <span style="color:green" class="has-tip">ok' +
                    '<span class="tip">' +
                        '<b>Destination:</b><br>' + result['destination-path'] + '<br><br>' +
                        '<b>Url:</b><br><a href="' + result['destination-url'] + '">' + result['destination-url'] + '<br>' +
                    '</span>' +
                '</span>' +
                getReductionHtml(orgSize, webpSize, 'Size of original', 'Size of webp')
        } else {
            html += ' <span style="color:red">failed</span>' + htmlViewLog;

            if (result['msg']) {
                logLn(html);
                logLn('<br><br><span style="color:red; font-size:15px">' + webpexpress_escapeHTML(result['msg']) + '</span>');
            }
            if (result['stop']) {
                return;
            }
            /*
            if (result['msg'] != '') {
                html += ' <span style="">' + result['msg'] + '</span>';
            }
            */
            html += '<br>';
            /*if (result['log'] != '') {
                html += ' <span style="font-size:10px">' + result['log'] + '</span>';
            }*/
        }
        logLn(html);


        // Get next
        bulkInfo.filePointer++;
        if (bulkInfo.filePointer == group.files.length) {
            bulkInfo.filePointer = 0;
            bulkInfo.groupPointer++;
        }
        if (bulkInfo.groupPointer == bulkInfo.groups.length) {
            convertDone();
        } else {
            if (bulkInfo.paused) {
                document.getElementById('bulkconvertlog').innerHTML += '<p><i>on pause</i><br>' +
                    'Reduction this far: ' + getReductionHtml(bulkInfo['orgTotalFilesize'], bulkInfo['webpTotalFilesize'], 'Total size of originals this far', 'Total size of webp files this far') + '</p>'

                bulkInfo['orgTotalFilesize'] += orgSize;
                bulkInfo['webpTotalFilesize'] += webpSize;

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
