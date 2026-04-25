(function () {
    // Fortonei favicon apo data attributes gia na min exoume inline JavaScript sta PHP views.
    function initFavicon(script) {
        if (!script) return;

        var faviconHref = script.getAttribute('data-favicon-href') || '';
        var shape = script.getAttribute('data-favicon-shape') || 'direct';
        var head = document.head || document.getElementsByTagName('head')[0];
        if (!faviconHref || !head) return;

        if (shape !== 'circle') {
            replaceIcon(head, faviconHref);
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
                replaceIcon(head, faviconHref);
                return;
            }

            context.clearRect(0, 0, size, size);
            context.beginPath();
            context.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
            context.closePath();
            context.clip();
            context.drawImage(img, 0, 0, size, size);

            replaceIcon(head, canvas.toDataURL('image/png'));
        };

        img.onerror = function () {
            replaceIcon(head, faviconHref);
        };

        img.src = faviconHref;
    }

    // Allazei to favicon kathara xoris na afinei duplicate link tags.
    function replaceIcon(head, href) {
        var existingIcons = head.querySelectorAll('link[rel="icon"], link[rel="shortcut icon"]');
        existingIcons.forEach(function (icon) {
            if (icon.parentNode) {
                icon.parentNode.removeChild(icon);
            }
        });

        var icon = document.createElement('link');
        icon.rel = 'icon';
        icon.type = 'image/png';
        icon.href = href;
        head.appendChild(icon);
    }

    initFavicon(document.currentScript);
})();
