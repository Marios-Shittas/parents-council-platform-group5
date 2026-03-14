// Contact form component with React
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

// Mount the component when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Note: The form is rendered server-side in PHP
    // This component is here for potential future React integration
    // The keyboard shortcut is handled by inline event listener below
    
    const messageTextarea = document.getElementById('message');
    if (messageTextarea) {
        messageTextarea.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.ctrlKey) {
                document.querySelector('form').submit();
            }
        });
    }
});
