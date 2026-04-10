const { useState } = React;

function TwoFactorForm() {
  const [code, setCode] = useState('');
  const [errorMessage, setErrorMessage] = useState('');
  const [successMessage, setSuccessMessage] = useState(window.initialTwoFactorSuccess || '');
  const [isLoading, setIsLoading] = useState(false);
  const [isResending, setIsResending] = useState(false);

  const sendCodeAgain = async () => {
    setErrorMessage('');
    setSuccessMessage('');
    setIsResending(true);

    try {
      const response = await fetch('/parents-council-platform-group5/app/services/TwoFactorAuthService.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include'
      });

      const data = await response.json();
      if (data.success) {
        setSuccessMessage('A new 2FA code was sent to your email.');
      } else {
        setErrorMessage(data.message || 'Failed to resend code.');
      }
    } catch (error) {
      console.error('Error resending code:', error);
      setErrorMessage('An error occurred while resending the code.');
    } finally {
      setIsResending(false);
    }
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    setErrorMessage('');
    setSuccessMessage('');

    if (code.trim().length !== 8) {
      setErrorMessage('Please enter the 8-character code.');
      return;
    }

    setIsLoading(true);

    try {
      const response = await fetch('/parents-council-platform-group5/app/services/TwoFactorAuthService.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ code: code.trim() })
      });

      const data = await response.json();

      if (data.success) {
        setSuccessMessage('Verification successful. Redirecting...');
        setTimeout(() => {
          fetch('/parents-council-platform-group5/app/services/SessionCheck.php', {
            credentials: 'include'
          })
            .then((res) => res.json())
            .then((sessionData) => {
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
        setErrorMessage(data.message || 'Invalid code.');
      }
    } catch (error) {
      console.error('Error verifying code:', error);
      setErrorMessage('An error occurred. Please try again.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleCodeKeyDown = (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      if (!isLoading && code.trim().length === 8) {
        handleSubmit(event);
      }
    }
  };

  return (
    <div className="two-factor-container">
      {errorMessage && <span id="error-message" className="two-factor-message">{errorMessage}</span>}
      {successMessage && <span id="success-message" className="two-factor-message">{successMessage}</span>}
      <p id="two-factor-label">Please enter the code sent to your email:</p>
      <form onSubmit={handleSubmit}>
        <input
          type="text"
          id="code-input"
          placeholder="Enter 8-character code"
          value={code}
          onChange={(e) => setCode(e.target.value.replace(/[^a-zA-Z0-9]/g, '').slice(0, 8))}
          onKeyDown={handleCodeKeyDown}
          maxLength="8"
          pattern="[A-Za-z0-9]{8}"
          required
        />
        <button id="confirm-button" type="submit" disabled={isLoading || code.length !== 8}>
          {isLoading ? 'Verifying...' : 'Confirm'}
        </button>
      </form>
      <div className="resend-wrapper">
        <span className="resend-hint">Didn't receive it?</span>
        <button id="resend-button" type="button" onClick={sendCodeAgain} disabled={isResending || isLoading}>
          <i className="fas fa-rotate-right" aria-hidden="true"></i>
          <span>{isResending ? 'Sending new code...' : 'Resend code'}</span>
        </button>
      </div>
    </div>
  );
}

const root = document.getElementById('two-factor-root');
ReactDOM.render(<TwoFactorForm />, root);
