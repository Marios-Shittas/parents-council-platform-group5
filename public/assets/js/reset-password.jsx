// Reset password functionality
(function() {
    const confirmButton = document.getElementById('confirm-button');
    const errorMessage = document.getElementById('error-message');

    // Handle confirm button
    if (confirmButton) {
        confirmButton.addEventListener('click', function() {
            // Get password values
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;

            // Validate passwords
            if (!newPassword || !confirmPassword) {
                showError('Please enter and confirm your new password');
                return;
            }
            if (newPassword !== confirmPassword) {
                showError('Passwords do not match');
                return;
            }

            // Send to server
            resetPassword(newPassword);
        });
    }

    function resetPassword(newPassword) {
        // Get email from URL parameter if available
        const params = new URLSearchParams(window.location.search);
        const email = params.get('email');

        // Send reset request
        fetch('/parents-council-platform-group5/app/services/ResetPasswordService.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: email,
                newPassword: newPassword
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to login with success message
                window.location.href = 'login.php?reset=success';
            } else {
                showError(data.message || 'Password reset failed');
            }
        })
        .catch(error => {
            showError('An error occurred. Please try again.');
            console.error('Error:', error);
        });
    }

    function showError(message) {
        if (errorMessage) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
        }
    }
})();
