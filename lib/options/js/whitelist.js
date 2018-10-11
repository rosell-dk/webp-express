function updateWhitelistInputValue() {
    //var val = [];

    document.getElementsByName('whitelist')[0].value = JSON.stringify(window.whitelist);
}


function setWhitelistHTML() {
    var s = '';
    s+='<table class="whitelist" id="whitelist_table">';
    s+='<tr>';
    s+='<th>Site<span id="whitelist_site_helptext2"></span></th>'
    s+='<th>Password<span id="password_helptext2"></span></th>'
    s+='<th>Limit<span id="whitelist_quota_helptext2"></span></th>'
    s+='</tr>';
    s=='</th></tr>';
    for (var i=0; i<window.whitelist.length; i++) {
        s+='<tr>';
        s+='<td><input type="text" size="15" id="whitelist-site-' + i + '" placeholder="hostname or IP"></input></td>';
        s+='<td><input type="text" size="10" id="whitelist-password-' + i + '"></input></td>';
        s+='<td class="quota"><nobr><input type="text" size="3" id="whitelist-quota-' + i + '"></input><i> per hour</i></nobr></td>';
        //s+='<td class="buttons"><button type="button" class="button button-secondary" onclick="whitelistRemoveRow(' + i + ')">remove</button></td>';
        s+='<td class="remove"><a href="javascript:whitelistRemoveRow(' + i + ')">remove</a></td>';
        s+='</tr>';
    }
    s+='<tr><td colspan="3" class="whitelist-add-site">';
    s+='<button type="button" class="button button-secondary" id="whitelist_add" onclick="whitelistAdd()">+ Add site</button>';
    s+='</td></tr>';
    s+='</table>';

    document.getElementById('whitelist_div').innerHTML = s;

    for (var i=0; i<window.whitelist.length; i++) {
        document.getElementById('whitelist-site-' + i).value = window.whitelist[i]['site'];
        document.getElementById('whitelist-password-' + i).value = window.whitelist[i]['password'];
        document.getElementById('whitelist-quota-' + i).value = window.whitelist[i]['quota'];
    }
    updateWhitelistInputValue();

    document.getElementById('password_helptext2').innerHTML = document.getElementById('password_helptext').innerHTML;
    document.getElementById('whitelist_site_helptext2').innerHTML = document.getElementById('whitelist_site_helptext').innerHTML;
    document.getElementById('whitelist_quota_helptext2').innerHTML = document.getElementById('whitelist_quota_helptext').innerHTML;

}

function whitelistAdd() {
    window.whitelist.push({
        site: '',
        password: '',
        quota: 60
    });
    setWhitelistHTML();
}

function whitelistRemoveRow(i) {
    window.whitelist.splice(i, 1);
    setWhitelistHTML();
}

document.addEventListener('DOMContentLoaded', function() {
    setWhitelistHTML();

    document.getElementById('whitelist_div').addEventListener("input", function(e){
        console.log(e);
        for (var i=0; i<window.whitelist.length; i++) {
            window.whitelist[i]['site'] = document.getElementById('whitelist-site-' + i).value;
            window.whitelist[i]['password'] = document.getElementById('whitelist-password-' + i).value;
            window.whitelist[i]['quota'] = document.getElementById('whitelist-quota-' + i).value;
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
