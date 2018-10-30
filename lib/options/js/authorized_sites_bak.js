/*function updateWhitelistInputValue() {
    //var val = [];

    document.getElementsByName('whitelist')[0].value = JSON.stringify(window.whitelist);
}

function setPassword(i) {
    var password = window.prompt('Enter password' + i);

    window.whitelist[i]['password_hashed'] = '';
    window.whitelist[i]['new_password'] = password;

    setWhitelistHTML();
}
*/
function setAuthorizedSitesHTML() {
    var s = '';

    if (window.authorizedSites && window.authorizedSites.length > 0) {
        s+='<table class="authorized_sites_list" >';
        s+='<tr>';
        s+='<th>Site<span id="whitelist_site_helptext2"></span></th>'
        //s+='<th>Salt<span id="salt_helptext2"></span></th>'
        //s+='<th>Limit<span id="whitelist_quota_helptext2"></span></th>'
        s+='</tr>';
        s=='</th></tr>';

        for (var i=0; i<window.authorizedSites.length; i++) {
            s+='<tr><td>';
            s+=window.authorizedSites[i]['id']
            s+='</td></tr>';
        }
    } else {
        s+='<i>No sites have been authorized to use this server yet.</i>';
    }
    s+='</table>';
    s+='<button type="button" class="button button-secondary" id="server_listen_btn" onclick="whitelistAdd()">Connect website</button>';

/*
    s+='<tr><td colspan="3" class="whitelist-add-site">';
    s+='<button type="button" class="button button-secondary" id="whitelist_add" onclick="whitelistAdd()">+ Add site</button>';
    s+='</td></tr>';
    s+='</table>';*/

    document.getElementById('authorized_sites_div').innerHTML = s;

}

/*
function whitelistAdd() {
    window.whitelist.push({
        site: '',
        password_hashed: '',
        new_password: '',
        //quota: 60
    });
    setWhitelistHTML();
}

function whitelistRemoveRow(i) {
    window.whitelist.splice(i, 1);
    setWhitelistHTML();
}
*/

document.addEventListener('DOMContentLoaded', function() {
    setAuthorizedSitesHTML();


});
