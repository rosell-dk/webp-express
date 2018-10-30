function updateWhitelistInputValue() {
    //var val = [];

    document.getElementsByName('whitelist')[0].value = JSON.stringify(window.whitelist);
}

function openPasswordPopup(i) {
    window.currentWhitelistPassword = i;
    document.getElementById('whitelist_enter_password').value = '';
    document.getElementById('whitelist_hash_password').checked = false;

    var options = '#TB_inline?inlineId=whitelist_enter_password_popup&width=400&height=300';
    tb_show("Enter password", options);

}
function setPassword() {

    var i = window.currentWhitelistPassword;
    var password = document.getElementById('whitelist_enter_password').value;
    var hashPassword = document.getElementById('whitelist_hash_password').checked;

    //var password = window.prompt('Enter password');

    window.whitelist[i]['password'] = '';
    window.whitelist[i]['new_password'] = password;
    window.whitelist[i]['hash_new_password'] = hashPassword;

    setWhitelistHTML();
    tb_remove();
}

function setWhitelistHTML() {
    var s = '';
    s+='<table class="whitelist" id="whitelist_table">';
    s+='<tr>';
    s+='<th>Site<span id="whitelist_site_helptext2"></span></th>'
    s+='<th>Password<span id="password_helptext2"></span></th>'
    //s+='<th>Salt<span id="salt_helptext2"></span></th>'
    //s+='<th>Limit<span id="whitelist_quota_helptext2"></span></th>'
    s+='</tr>';
    s=='</th></tr>';
    if (window.whitelist) {
        for (var i=0; i<window.whitelist.length; i++) {
            s+='<tr>';
            s+='<td><input type="text" size="15" id="whitelist-site-' + i + '" placeholder="hostname or IP"></input></td>';
            //s+='<td><input type="text" size="10" id="whitelist-password-' + i + '"></input></td>';

            s+='<td><a href="javascript:openPasswordPopup(' + i + ')">Click to ' +
                ((window.whitelist[i]['password'] || window.whitelist[i]['new_password']) ? 'change' : 'set')
                + '</td>';
            //s+='<td>' + (window.whitelist[i]['salt'] || '') + '</td>';

            //s+='<td class="quota"><nobr><input type="text" size="3" id="whitelist-quota-' + i + '"></input><i> per hour</i></nobr></td>';
            //s+='<td class="buttons"><button type="button" class="button button-secondary" onclick="whitelistRemoveRow(' + i + ')">remove</button></td>';
            s+='<td class="remove"><a href="javascript:whitelistRemoveRow(' + i + ')">remove row</a></td>';
            s+='</tr>';
        }
    }
    s+='<tr><td colspan="3" class="whitelist-add-site">';
    s+='<button type="button" class="button button-secondary" id="whitelist_add" onclick="whitelistAddSite()">+ Add site</button>';
    s+='</td></tr>';
    s+='</table>';

    document.getElementById('whitelist_div').innerHTML = s;

    if (window.whitelist) {
        for (var i=0; i<window.whitelist.length; i++) {
            document.getElementById('whitelist-site-' + i).value = window.whitelist[i]['site'];
            //document.getElementById('whitelist-password-hashed' + i).value = window.whitelist[i]['password_hashed'];
            //document.getElementById('whitelist-quota-' + i).value = window.whitelist[i]['quota'];
        }
    }
    updateWhitelistInputValue();

    document.getElementById('password_helptext2').innerHTML = document.getElementById('password_helptext').innerHTML;
    document.getElementById('whitelist_site_helptext2').innerHTML = document.getElementById('whitelist_site_helptext').innerHTML;
    //document.getElementById('whitelist_quota_helptext2').innerHTML = document.getElementById('whitelist_quota_helptext').innerHTML;

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
                            s += 'Website: ' + r['website'] + '<br>';
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
    jQuery.post(window.ajaxurl, {
            'action': 'webpexpress_stop_listening',
        }, function(response) {}
    );
    closeDasPopup();
}

function whitelistAcceptRequest() {
    whitelistCancelListening();

    var r = window.webpexpress_incoming_request;
    window.whitelist.push({
        id: r['website'],
        new_password: r['password'],
//        new_password: '',
        //quota: 60
    });
    setWhitelistHTML();
}

function whitelistDenyRequest() {
    whitelistCancelListening();
}

function whitelistAddSite() {
    whitelistStartPolling();
    openDasPopup('whitelist_listen_popup', 400, 300);
}

function whitelistAdd() {
}

function whitelistRemoveRow(i) {
    window.whitelist.splice(i, 1);
    setWhitelistHTML();
}

document.addEventListener('DOMContentLoaded', function() {

    window.setInterval(function() {
        var el = document.getElementById('animated_dots');
        if (el.innerText == '....') {
            el.innerText = '';
        } else {
            el.innerText += '.';
        }
    }, 500);

    setWhitelistHTML();

    document.getElementById('whitelist_div').addEventListener("input", function(e){
        console.log(e);
        for (var i=0; i<window.whitelist.length; i++) {
            window.whitelist[i]['site'] = document.getElementById('whitelist-site-' + i).value;
            //window.whitelist[i]['password_hashed'] = document.getElementById('whitelist-password-hashed' + i).value;
            //window.whitelist[i]['quota'] = document.getElementById('whitelist-quota-' + i).value;
        }
        updateWhitelistInputValue();
    });

    document.getElementById('whitelist_table').addEventListener("change", function(e){
        // Clean up
        for (var i=0; i<window.whitelist.length; i++) {
            var s = document.getElementById('whitelist-site-' + i).value;
            var s2 = s.replace(/^https?:\/\//, '');
            if (s2 != s) {
                e.target.value = s2;
                window.whitelist[i][0] = s2;
                updateWhitelistInputValue();
            }
        }
    });

});
