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
    errorMessage.textContent = "Please enter both email and password.";
  return;
  }
  else if(!emailPattern.test(email)) {
    errorMessage.textContent = "Please enter a valid email address.";
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
          if (data.role === 'admin') {
            window.location.href = 'admin/index.php';
          }
          else if (data.role === 'parent') {
            window.location.href = 'parent/index.php';
          }
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