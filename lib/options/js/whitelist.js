function updateWhitelistInputValue() {
    if (document.getElementById('whitelist') == null) {
        console.log('document.getElementById("whitelist") returns null. Strange! Please report.');
        return;
    }
    document.getElementById('whitelist').value = JSON.stringify(window.whitelist);
}

function whitelistStartPolling() {

    jQuery.post(window.ajaxurl, {
            'action': 'webpexpress_start_listening',
        }, function(response) {
            window.whitelistTid = window.setInterval(function() {
                jQuery.post(window.ajaxurl, {
                        'action': 'webpexpress_get_request',
                    }, function(response) {
                        if (response && (response.substr(0,1) == '{')) {
                            var r = JSON.parse(response);
                            window.webpexpress_incoming_request = r;
                            //console.log(r);
                            window.clearInterval(window.whitelistTid);
                            closeDasPopup();

                            // Show request
                            openDasPopup('whitelist_accept_request', 300, 200);

                            var s = '';
                            s += 'Website: ' + r['label'] + '<br>';
                            s += 'IP: ' + r['ip'] + '<br>';

                            document.getElementById('request_details').innerHTML = s;
                        } else {
                            console.log('Got this from the server: ' + response);
                        }
                    }
                );
            }, 2000);
        }
    );
}

function whitelistCancelListening() {
    /*
    jQuery.post(window.ajaxurl, {
            'action': 'webpexpress_stop_listening',
        }, function(response) {}
    );
    */
}

function whitelistCreateUid() {
    var timestamp = (new Date()).getTime();
    var randomNumber = Math.floor(Math.random() * 10000);
    return (timestamp * 10000 + randomNumber).toString(36);
}

/*
function whitelistAcceptRequest() {
    whitelistCancelListening();
    closeDasPopup();

    var r = window.webpexpress_incoming_request;
    window.whitelist.push({
        uid: whitelistCreateUid(),
        label: r['label'],
        'new-api-key': r['api-key'],
        ip: r['ip'],
//        new_password: '',
        //quota: 60
    });
    updateWhitelistInputValue();
    whitelistSetHTML();
}

function whitelistDenyRequest() {
    whitelistCancelListening();
    closeDasPopup();
}*/

function whitelistAddSite() {
    whitelistStartPolling();
    openDasPopup('whitelist_listen_popup', 400, 300);
}

function whitelistRemoveEntry(i) {
    window.whitelist.splice(i, 1);
    whitelistSetHTML();
}

function whitelistSetHTML() {
    updateWhitelistInputValue();
    var s = '';

    if (window.whitelist && window.whitelist.length > 0) {
        s+='<br><i>Authorized web sites:</i>';
        s+='<ul>';
        for (var i=0; i<window.whitelist.length; i++) {
            s+='<li>';
            s+= webpexpress_escapeHTML(window.whitelist[i]['label']);
            s+='<div class="whitelist-links">'
            s+='<a href="javascript:whitelistEditEntry(' + i + ')">edit</a>';
            s+='<a href="javascript:whitelistRemoveEntry(' + i + ')">remove</a>';
            s+='</div>'
            s+='</li>';
        }
        s+='</ul>';
    } else {
        s+='<p style="margin:12px 0"><i>No sites have been authorized to use the web service yet.</i></p>';
    }
    s+='<button type="button" class="button button-secondary" id="server_listen_btn" onclick="whitelistAddManually()">+ Authorize website</button>';

    document.getElementById('whitelist_div').innerHTML = s;

}

function whitelistClearWhitelistEntryForm() {
    document.getElementById('whitelist_label').value = '';
    document.getElementById('whitelist_ip').value = '';
    document.getElementById('whitelist_api_key').value = '';
    document.getElementById('whitelist_require_api_key_to_be_crypted_in_transfer').checked = true;
}

function whitelistAddWhitelistEntry() {

    if (document.getElementById('whitelist_label').value == '') {
        alert('Label must be filled out');
        return;
    }
    if (document.getElementById('whitelist_ip').value == '') {
        alert('IP must be filled out. To allow any IP, enter "*"');
        return;
    }
    // TODO: Validate IP syntax
    if (document.getElementById('whitelist_api_key').value == '') {
        alert('API key must be filled in');
        return;
    }
    window.whitelist.push({
        uid: whitelistCreateUid(),
        label: document.getElementById('whitelist_label').value,
        ip: document.getElementById('whitelist_ip').value,
        'new-api-key': document.getElementById('whitelist_api_key').value,
        'require-api-key-to-be-crypted-in-transfer': document.getElementById('whitelist_require_api_key_to_be_crypted_in_transfer').checked,
//        new_password: '',
        //quota: 60
    });
    updateWhitelistInputValue();
    whitelistSetHTML();

    closeDasPopup();
}

function whitelistAddManually() {
//    alert('not implemented yet');
    whitelistClearWhitelistEntryForm();

    document.getElementById('whitelist_properties_popup').className = 'das-popup mode-add';

//    whitelistCancelListening();
//    closeDasPopup();
    openDasPopup('whitelist_properties_popup', 400, 300);
}

function whitelistChangeApiKey() {
    document.getElementById('whitelist_api_key').value = prompt('Enter new api key');
}

function whitelistUpdateWhitelistEntry() {
    var i = parseInt(document.getElementById('whitelist_i').value, 10);

    window.whitelist[i]['uid'] = document.getElementById('whitelist_uid').value;
    window.whitelist[i]['label'] = document.getElementById('whitelist_label').value;
    window.whitelist[i]['ip'] = document.getElementById('whitelist_ip').value;

    if (document.getElementById('whitelist_api_key').value != '') {
        window.whitelist[i]['new-api-key'] = document.getElementById('whitelist_api_key').value;
    }
    window.whitelist[i]['require-api-key-to-be-crypted-in-transfer'] = document.getElementById('whitelist_require_api_key_to_be_crypted_in_transfer').checked;
    whitelistSetHTML();
    closeDasPopup();
}

function whitelistEditEntry(i) {
    var entry = window.whitelist[i];
    whitelistClearWhitelistEntryForm();

    document.getElementById('whitelist_properties_popup').className = 'das-popup mode-edit';

    document.getElementById('whitelist_uid').value = entry['uid'];
    document.getElementById('whitelist_i').value = i;
    document.getElementById('whitelist_label').value = entry['label'];
    document.getElementById('whitelist_ip').value = entry['ip'];
    document.getElementById('whitelist_api_key').value = '';
    document.getElementById('whitelist_require_api_key_to_be_crypted_in_transfer').checked = entry['require-api-key-to-be-crypted-in-transfer'];

    openDasPopup('whitelist_properties_popup', 400, 300);
}

document.addEventListener('DOMContentLoaded', function() {
    updateWhitelistInputValue();
    whitelistSetHTML();
});
