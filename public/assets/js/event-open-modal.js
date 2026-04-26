// Arxeio: public\assets\js\event-open-modal.js
// Rolos: Xeirizetai frontend symperifora, validation, API calls i React rendering gia tin selida.
// Simeiosi: Allages edo epireazoun ti symperifora sto browser kai ta API requests pou stelnei to UI.
// Anoigei modal me ta stoixeia mias ekdilosis otan o xristis patisei karta.
document.addEventListener('DOMContentLoaded', function () {
        // Diavazoume to ?open=ID gia deep link se sigkekrimeni ekdilosi.
        var params = new URLSearchParams(window.location.search);
        var openId = params.get('open');

        if (!openId) {
            return;
        }

        // To modal id prepei na tairiazei me to ekdilosi id pou exei ginei render sti selida.
        var modalElement = document.getElementById('eventModal' + openId);
        if (!modalElement || typeof window.jQuery === 'undefined') {
            return;
        }

        window.jQuery(modalElement).modal('show');
    });
