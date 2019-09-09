/*
function htmlEscape(str) {
    return str
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}
*/
function webpexpress_escapeHTML(s)
{
    return s.replace(/./gm, function(s) {
        var safe = /[0-9a-zA-Z\!]/;
        if (safe.test(s.charAt(0))) {
            return s.charAt(0);
        }

        switch (s.charAt(0)) {
            case '*':
            case '#':
            case ' ':
            case '{':
            case '}':
            case ':':
            case '.':
            case '`':
                return s.charAt(0);
            default:
                return "&#" + s.charCodeAt(0) + ";";
        }

    });
}
