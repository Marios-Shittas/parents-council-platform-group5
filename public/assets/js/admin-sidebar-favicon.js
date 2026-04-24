(function () {
    var script = document.currentScript;
    var faviconHref = (script && script.dataset && script.dataset.faviconSrc) || '';
    var head = document.head || document.getElementsByTagName('head')[0];

    if (!head || faviconHref === '') {
        return;
    }

    var existingIcons = head.querySelectorAll('link[rel="icon"], link[rel="shortcut icon"]');
    existingIcons.forEach(function (icon) {
        icon.parentNode.removeChild(icon);
    });

    var icon = document.createElement('link');
    icon.rel = 'icon';
    icon.type = 'image/png';
    icon.href = faviconHref;
    head.appendChild(icon);
})();
