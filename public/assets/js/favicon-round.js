(function () {
    var script = document.currentScript;
    var faviconHref = (script && script.dataset && script.dataset.faviconSrc) || '';
    var head = document.head || document.getElementsByTagName('head')[0];
    if (!head || faviconHref === '') {
        return;
    }

    var img = new Image();
    img.onload = function () {
        var size = 128;
        var canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;

        var context = canvas.getContext('2d');
        if (!context) {
            return;
        }

        context.clearRect(0, 0, size, size);
        context.beginPath();
        context.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
        context.closePath();
        context.clip();
        context.drawImage(img, 0, 0, size, size);

        var existingIcons = head.querySelectorAll('link[rel="icon"], link[rel="shortcut icon"]');
        existingIcons.forEach(function (icon) {
            icon.parentNode.removeChild(icon);
        });

        var icon = document.createElement('link');
        icon.rel = 'icon';
        icon.type = 'image/png';
        icon.href = canvas.toDataURL('image/png');
        head.appendChild(icon);
    };

    img.src = faviconHref;
})();
