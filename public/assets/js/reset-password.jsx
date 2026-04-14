(function() {
    const resetPasswordForm = document.getElementById('reset-password-form');
    const confirmButton = document.getElementById('confirm-button');
    const errorMessage = document.getElementById('error-message');
    const passwordRuleRegex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/;
    const whitespaceRegex = /\s/;

    function handleResetSubmit(event) {
        if (event) {
            event.preventDefault();
        }

        const newPassword = document.getElementById('new-password').value;
        const confirmPassword = document.getElementById('confirm-password').value;

        if (!newPassword || !confirmPassword) {
            showError('Please enter and confirm your new password');
            return;
        }
        if (newPassword !== confirmPassword) {
            showError('Passwords do not match');
            return;
        }

        if (whitespaceRegex.test(newPassword)) {
            showError('Password cannot contain spaces');
            return;
        }

        if (!passwordRuleRegex.test(newPassword)) {
            showError('Password must be at least 8 characters and include letters, numbers, and 1 special character');
            return;
        }

        resetPassword(newPassword);
    }

    if (resetPasswordForm) {
        resetPasswordForm.addEventListener('submit', handleResetSubmit);
    } else if (confirmButton) {
        confirmButton.addEventListener('click', handleResetSubmit);
    }

    function resetPassword(newPassword) {
        const serviceUrl = window.RESET_PASSWORD_SERVICE_URL || '/parents-council-platform-group5/app/services/ResetPasswordService.php';

        fetch(serviceUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                email: window.RESET_EMAIL,
                token: window.RESET_TOKEN,
                newPassword: newPassword
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                errorMessage.style.color = 'green';
                errorMessage.textContent = data.message || 'Password reset successful';
                errorMessage.style.display = 'block';
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1000);
            } else {
                errorMessage.style.color = 'red';
                errorMessage.textContent = data.message || 'Password reset failed';
                errorMessage.style.display = 'block';
            }
        })
        .catch(error => {
            errorMessage.style.color = 'red';
            errorMessage.textContent = 'An error occurred. Please try again.';
            errorMessage.style.display = 'block';
            console.error('Error:', error);
        });
    }

    function showError(message) {
        if (errorMessage) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
        }
    }

        function setupPasswordToggle(inputId, toggleId) {
        const toggleIcon = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        let isVisible = false;

        if (toggleIcon && input) {
            toggleIcon.innerHTML = '<i class="fa-regular fa-eye-slash"></i>';
            toggleIcon.style.cursor = 'pointer';

            toggleIcon.addEventListener('click', function(e) {
                e.preventDefault();
                isVisible = !isVisible;
                input.type = isVisible ? 'text' : 'password';
                toggleIcon.innerHTML = isVisible 
                    ? '<i class="fa-regular fa-eye"></i>' 
                    : '<i class="fa-regular fa-eye-slash"></i>';
            });
        }
    }

    setupPasswordToggle('new-password', 'new-password-toggle');
    setupPasswordToggle('confirm-password', 'confirm-password-toggle');
})();
