const { useState } = React;

/* SHOW/HIDE PASSWORD */
function PasswordToggle() {
  const [showPassword, setShowPassword] = useState(false);

  const togglePassword = () => {
    const input = document.getElementById("password-input");
    input.type = showPassword ? "password" : "text";
    setShowPassword(!showPassword);
  };

  return (
    <i
      className={`fa-regular ${showPassword ? "fa-eye" : "fa-eye-slash"}`}
      onClick={togglePassword}
      style={{ cursor: "pointer" }}
    />
  );
}
const root = document.getElementById("password-toggle-root");
ReactDOM.render(<PasswordToggle />, root);


/* LOGIN FUNCTIONALITY */
function handleLogin() {
  const email = document.getElementById("email-input").value;
  const password = document.getElementById("password-input").value;
  const errorMessage = document.getElementById("error-message");

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (!email || !password) {
    errorMessage.textContent = "Παρακαλώ συμπληρώστε email και κωδικό πρόσβασης.";
  return;
  }
  else if(!emailPattern.test(email)) {
    errorMessage.textContent = "Παρακαλώ εισάγετε έγκυρη διεύθυνση email.";
    return;
  }
  fetch ('/parents-council-platform-group5/app/services/LoginService.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      credentials: 'include',
      body: JSON.stringify({ email, password })
  })

  .then(respone => respone.json())
  .then(data => {
      if (data.success) {
        window.location.href = 'two-factor-authentication.php';
      } else {
        errorMessage.textContent = data.message;
      }
  })
  .catch(error => console.error('Error during login:', error));
}
document.getElementById("login-button").addEventListener("click", handleLogin);

function handleKeyPress(event) {
  if (event.key === 'Enter') {
    handleLogin();
  }
}
document.getElementById("email-input").addEventListener("keypress", handleKeyPress);
document.getElementById("password-input").addEventListener("keypress", handleKeyPress);