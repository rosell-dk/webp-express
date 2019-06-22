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
        return "&#" + s.charCodeAt(0) + ";";
    });
}
