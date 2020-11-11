if (typeof WebPExpress == 'undefined') {
    WebPExpress = {};
}

WebPExpress.SelfTest = {
    'clear': function() {
        document.getElementById('webpexpress_test_redirection_content').innerHTML = '';
    },
    'write': function(html) {
        //var el = document.getElementById('webpexpress_test_redirection_content');
        //el.innerHTML += html;
        var el = document.createElement('div');
        el.innerHTML = html;
        document.getElementById('webpexpress_test_redirection_content').appendChild(el);
    },
    'simpleMdToHtml': function(line) {
        //return s.replace(/./gm, function(s) {


        if (line.substr(0, 3) == '```') {
            return '<pre>' + line.replace(/\`/gm, '') + '</pre>';
        }

        // Bold with inline attributtes (ie: "hi **bold**{: .red}")
        line = line.replace(/\*\*([^\*]+)\*\*\{:\s([^}]+)\}/gm, function(s, g1, g2) {
            // g2 is the inline attributes.
            // right now we only support classes, and only ONE class.
            // so it is easy.
            var className = g2.substr(1);

            return '<b class="' + className + '">' + g1 + '</b>';
            //return '<b>' + s.substr(2, s.length - 4) + '</b>';
        });

        // Bold
        line = line.replace(/(\*\*[^\*]+\*\*)/gm, function(s) {
            return '<b>' + s.substr(2, s.length - 4) + '</b>';
        });

        // Italic
        line = line.replace(/(\*[^\*]+\*)/gm, function(s) {
            return '<i>' + s.substr(1, s.length - 2) + '</i>';
        });

        // Headline
        if (line.substr(0, 2) == '# ') {
            line = '<h1>' + line.substr(2) + '</h1>';
        }
        if (line.substr(0, 3) == '## ') {
            line = '<h2>' + line.substr(3) + '</h2>';
        }
        if (line.substr(0, 4) == '### ') {
            line = '<h3>' + line.substr(4) + '</h3>';
        }
        if (line.substr(0, 5) == '#### ') {
            line = '<h4>' + line.substr(5) + '</h4>';
        }

        if (line.substr(0, 15) == '&#39;&#39;&#39;') {
            line = '<pre>' + line.substr(15) + '</pre>';
        }

        // Empty line
        if (line == '') {
            line = '<br>';
        }


        // "ok!" green
        line = line.replace(/ok\!/gmi, function(s) {
            return '<span style="color:green; font-weight: bold;">ok</span>';
        });

        // "great" green
        /*
        line = line.replace(/great/gmi, function(s) {
            return '<span style="color:green; font-weight: bold;">' + s + '</span>';
        });*/

        // "failed" red
        line = line.replace(/failed/gmi, function(s) {
            return '<span style="color:red; font-weight:bold">FAILED</span>';
        });

        return '<div class="webpexpress md">' + line + '</div>';

    },
    'responseHandler': function(response) {
        if ((typeof response == 'object') && (response['success'] == false)) {
            html = '<h1>Error</h1>';
            if (response['data'] && ((typeof response['data']) == 'string')) {
                html += webpexpress_escapeHTML(response['data']);
            }
            WebPExpress.SelfTest.write(html);
            document.getElementById('bulkconvertcontent').innerHTML = html;
            return;
        }

        var responseObj = JSON.parse(response);
        var result = responseObj['result'];
        if (typeof result == 'string') {
            result = [result];
        }

        for (var i=0; i<result.length; i++) {
            var line = result[i];
            if (typeof line != 'string') {
                continue;
            }
            line = webpexpress_escapeHTML(line);
            line = WebPExpress.SelfTest.simpleMdToHtml(line);

            WebPExpress.SelfTest.write(line);
        }
        //result = result.join('<br>');



        var next = responseObj['next'];
        if (next == 'done') {
            //WebPExpress.SelfTest.write('<br>done');
        } else if (next == 'break') {
            WebPExpress.SelfTest.write('breaking');
        } else {
            WebPExpress.SelfTest.runTest(next);
        }

    },
    'runTest': function(testId) {
        var data = {
    		'action': 'webpexpress_self_test',
            'testId': testId,
            'nonce' : window.webpExpress['ajax-nonces']['self-test'],
    	};

        jQuery.ajax({
            method: 'POST',
            url: ajaxurl,
            data: data,
            success: WebPExpress.SelfTest.responseHandler,
            error: function() {
                WebPExpress.SelfTest.write('PHP error. Check your debug.log for more info (make sure debugging is enabled)');
            },
        });
    },
    'openPopup': function(testId) {
        WebPExpress.SelfTest.clear();
        var w = Math.min(1000, Math.max(200, document.documentElement.clientWidth - 100));
        var h = Math.max(250, document.documentElement.clientHeight - 80);

        var title = 'Self testing';
        if (testId == 'redirectToExisting') {
            title = 'Testing redirection to existing webp';
        }
        tb_show(title, '#TB_inline?inlineId=webpexpress_test_redirection_popup&width=' + w + '&height=' + h);

        WebPExpress.SelfTest.runTest(testId);

    }
}
