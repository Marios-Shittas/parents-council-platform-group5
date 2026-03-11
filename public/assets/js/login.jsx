const { useState } = React;

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
