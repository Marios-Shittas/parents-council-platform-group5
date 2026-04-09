const { useState } = React;

function TwoFactorForm() {
  const [code, setCode] = useState('');
  const [errorMessage, setErrorMessage] = useState('');
  const [successMessage, setSuccessMessage] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrorMessage('');
    setSuccessMessage('');
    setIsLoading(true);

    if (!code.trim()) {
      setErrorMessage('Please enter the 2FA code');
      setIsLoading(false);
      return;
    }

    try {
      const response = await fetch('/parents-council-platform-group5/app/services/TwoFactorAuthService.php', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({ code: code.trim() })
      });

      const data = await response.json();

      if (data.success) {
        setSuccessMessage('Verification successful! Redirecting...');
        // Redirect based on user role
        setTimeout(() => {
          fetch('/parents-council-platform-group5/app/services/SessionCheck.php', {
            credentials: 'include'
          })
          .then(res => res.json())
          .then(sessionData => {
            if (sessionData.role === 'admin') {
              window.location.href = 'admin/index.php';
            } else {
              window.location.href = 'parent/index.php';
            }
          })
          .catch(() => {
            window.location.href = 'index.php';
          });
        }, 1000);
      } else {
        setErrorMessage(data.message);
      }
    } catch (error) {
      console.error('Error:', error);
      setErrorMessage('An error occurred. Please try again.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="two-factor-container">
      <p id="two-factor-label">Please enter the code sent to your email:</p>
      <form onSubmit={handleSubmit}>
        <input
          type="text"
          id="code-input"
          placeholder="Enter 6-digit code"
          value={code}
          onChange={(e) => setCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
          maxLength="6"
          pattern="[0-9]{6}"
          required
        />
        <button
          id="confirm-button"
          type="submit"
          disabled={isLoading || code.length !== 6}
        >
          {isLoading ? 'Verifying...' : 'Confirm'}
        </button>
      </form>
      {errorMessage && <span id="error-message">{errorMessage}</span>}
      {successMessage && <span id="success-message">{successMessage}</span>}
    </div>
  );
}

const root = document.getElementById("two-factor-root");
ReactDOM.render(<TwoFactorForm />, root);