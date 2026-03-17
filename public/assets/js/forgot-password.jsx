/* FORGOT PASSWORD FUNCTIONALITY */
function handleForgotPassword() {
  const email = document.getElementById("email-input").value;
  const errorMessage = document.getElementById("error-message");

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (!email) {
    errorMessage.textContent = "Please enter your email.";
    return;
  }
  else if(!emailPattern.test(email)) {
    errorMessage.textContent = "Please enter a valid email address.";
    return;
  }
  fetch ('/parents-council-platform-group5/app/services/ForgotPasswordService.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
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