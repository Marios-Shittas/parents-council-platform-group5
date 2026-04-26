// Arxeio: public\assets\js\announcement-open-modal.js
// Rolos: Xeirizetai frontend symperifora, validation, API calls i React rendering gia tin selida.
// Simeiosi: Allages edo epireazoun ti symperifora sto browser kai ta API requests pou stelnei to UI.
// Anoigei modal me ta stoixeia mias anakoinosis otan o xristis patisei karta.
document.addEventListener('DOMContentLoaded', function () {
        // Diavazoume to ?open=ID apo to URL gia na anoiksoume apefthias tin sosti karta.
        var params = new URLSearchParams(window.location.search);
        var openId = params.get('open');

        if (!openId) {
            return;
        }

        // To modal id ftiaxnetai apo to anakoinosi id pou exei dwsei to backend sto HTML.
        var modalElement = document.getElementById('announcementModal' + openId);
        if (!modalElement || typeof window.jQuery === 'undefined') {
            return;
        }

        window.jQuery(modalElement).modal('show');
    });
