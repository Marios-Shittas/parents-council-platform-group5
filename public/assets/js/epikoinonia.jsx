// Arxeio: public\assets\js\epikoinonia.jsx
// Rolos: Xeirizetai frontend symperifora, validation, API calls i React rendering gia tin selida.
// Simeiosi: Allages edo epireazoun ti symperifora sto browser kai ta API requests pou stelnei to UI.
// Contact forma component me React
const EpikoinoniaForm = () => {
    const handleKeyDown = (e) => {
        if (e.key === 'Enter' && e.ctrlKey) {
            e.target.closest('form').submit();
        }
    };

    return (
        <div className="contact-footer-spacing"></div>
    );
};

// Mount to component otan DOM is diavasmay
document.addEventListener('DOMContentLoaded', function() {
    // Note: To forma is rendered server-side in PHP
    // This component is here gia potential future React integration
    // To keyboard syntomeusi is handled apo inline ekdilosi listener below
    
    const messageTextarea = document.getElementById('message');
    if (messageTextarea) {
        messageTextarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.ctrlKey) {
                document.querySelector('form').submit();
            }
        });
    }
});
