function openDasPopup(id, w, h) {

    // Create overlay, if it isn't already created
    if (!document.getElementById('das_overlay')) {
        var el = document.createElement('div');
        el.setAttribute('id', 'das_overlay');
        document.body.appendChild(el);
    }

    // Show overlay
    document.getElementById('das_overlay').style['display'] = 'block';

    // Set width and height on popup
    var popupEl = document.getElementById(id);
    popupEl.style['width'] = w + 'px';
    popupEl.style['margin-left'] = -Math.floor(w/2) + 'px';
    popupEl.style['height'] = h + 'px';
    popupEl.style['margin-top'] = -Math.floor(h/2) + 'px';

    // Show popup
    popupEl.style['visibility'] = 'visible';
    window.currentDasPopupId = id;
}

function closeDasPopup() {
    if (document.getElementById('das_overlay')) {
        document.getElementById('das_overlay').style['display'] = 'none';
    }

    if (window.currentDasPopupId) {
        document.getElementById(window.currentDasPopupId).style['visibility'] = 'hidden';

    }
}

document.onkeydown = function(evt) {
    evt = evt || window.event;
    var isEscape = false;
    if ("key" in evt) {
        isEscape = (evt.key == "Escape" || evt.key == "Esc");
    } else {
        isEscape = (evt.keyCode == 27);
    }
    if (isEscape) {
        closeDasPopup();
    }
};
