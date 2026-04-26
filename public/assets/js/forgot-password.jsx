// Arxeio: public\assets\js\forgot-password.jsx
// Rolos: Xeirizetai frontend symperifora, validation, API calls i React rendering gia tin selida.
// Simeiosi: Allages edo epireazoun ti symperifora sto browser kai ta API requests pou stelnei to UI.
/* FORGOT PASSWORD FUNCTIONALITY */
function handleForgotPassword() {
  const email = document.getElementById("email-input").value;
  const errorMessage = document.getElementById("error-message");
  const serviceUrl = window.FORGOT_PASSWORD_SERVICE_URL || '/parents-council-platform-group5/app/services/ForgotPasswordService.php';

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (!email) {
    errorMessage.textContent = "Παρακαλώ εισάγετε το email σας.";
    return;
  }
  else if(!emailPattern.test(email)) {
    errorMessage.textContent = "Παρακαλώ εισάγετε έγκυρη διεύθυνση email.";
    return;
  }
  fetch (serviceUrl, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'include',
      body: JSON.stringify({email})
  })
  .then(respone => respone.json())
  .then(data => {
      if (data.success) {
        errorMessage.style.color = 'green';
        errorMessage.textContent = data.message;
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 1000);
      } else {
        errorMessage.textContent = data.message;
      }
  })
  .catch(error => console.error('Error during login:', error));
}
document.getElementById("send-email-button").addEventListener("click", handleForgotPassword);
