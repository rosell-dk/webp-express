
window["wcfmoptions"] = {};

window["wcfmoptions"]['poster'] = function(action, options, successCallback, errorCallback) {

  //console.log('wcfmoptions.poster called. Action: ' + action);
  //console.log('wcfmoptions.poster called. Options: ' + JSON.stringify(options));

  // Call the API
  jQuery.ajax({
    type: "POST",
    url: ajaxurl,
    data: {
        'action': 'webpexpress-wcfm-api',
        'nonce' : window.webpExpressWCFMNonce,
        'command': action,
        'args': options
    },
    dataType: 'text',
    timeout: 30000,
    error: function (jqXHR, status, errorThrown) {
      console.log(errorThrown);
    },
    success: function(responseText) {
      //console.log('ajax response', responseText);
      //response = "[{name:'hello'}]";
      try {
          responseObj = JSON.parse(responseText);
      } catch (e) {
        console.log('The "' + action + '" response could not be parsed as JSON. ' + e.name + ':' + e.message);
        console.log('response:' + responseText);
        errorCallback(responseText);
        return
      }
      successCallback(responseObj);
    }
  });
}

function adjustWCFMHeight() {
  var usedHeight =
    document.getElementById('wpadminbar').offsetHeight +
    document.getElementById('wpfooter').offsetHeight +
    document.getElementById('screen-meta').offsetHeight +
    document.getElementById('wcfmintro').offsetHeight;

  var wcfm = document.getElementById('webpconvert-filemanager');

  var h = Math.max(document.body.clientHeight - usedHeight - 30, 300);
  //console.log('setting height', h, document.body.clientHeight, usedHeight);
  wcfm.style.height = h + 'px';
}

window.addEventListener('load', function(event) {
  adjustWCFMHeight();
});


window.addEventListener('resize', function(event) {
  adjustWCFMHeight();
});
