
window["wcfmoptions"] = {};

window["wcfmoptions"]['poster'] = function(action, options, successCallback, errorCallback) {

  // Call the API
  jQuery.ajax({
    type: "POST",
    url: ajaxurl,
    data: {
        'action': 'webpexpress-wcfm-api',
        'nonce' : window.webpExpressWCFMNonce,
        'command': action,
        'options': options
    },
    dataType: 'text',
    timeout: 30000,
    error: function (jqXHR, status, errorThrown) {
      console.log(errorThrown);
    },
    success: function(responseText) {
      console.log('ajax response', responseText);
      //response = "[{name:'hello'}]";
      try {
          responseObj = JSON.parse(responseText);
          successCallback(responseObj);
      } catch (e) {
        errorCallback(response)
      }
    }
  });
}
