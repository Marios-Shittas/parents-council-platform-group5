// Arxeio: public\assets\js\two-factor-authentication.jsx
// Rolos: Xeirizetai frontend symperifora, validation, API calls i React rendering gia tin selida.
// Simeiosi: Prosoxi: afora authentication/security flow, ara den allazoume validation i redirects xoris elegxo.
// React formaa gia elegxo kodika two factor authentication.
const { useState } = React;

function TwoFactorForm() {
  // Kratame state gia kodika, feedback kai loading flags gia ta dio async actions.
  const [code, setCode] = useState('');
  const [errorMessage, setErrorMessage] = useState('');
  const [successMessage, setSuccessMessage] = useState(window.initialTwoFactorSuccess || '');
  const [isLoading, setIsLoading] = useState(false);
  const [isResending, setIsResending] = useState(false);

  const sendCodeAgain = async () => {
    // POST sto service simainei "steile neo 2FA code" gia to trexon session.
    setErrorMessage('');
    setSuccessMessage('');
    setIsResending(true);

    try {
      const response = await fetch(window.appServiceUrl('TwoFactorAuthService.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include'
      });

      const data = await response.json();
      if (data.success) {
        setSuccessMessage('Ένας νέος κωδικός 2FA στάλθηκε στο email σας.');
      } else {
        setErrorMessage(data.message || 'Αποτυχία επαναποστολής κωδικού.');
      }
    } catch (error) {
      console.error('Error resending code:', error);
      setErrorMessage('Παρουσιάστηκε σφάλμα κατά την επαναποστολή του κωδικού.');
    } finally {
      setIsResending(false);
    }
  };

  const handleSubmit = async (event) => {
    // PUT sto service simainei "elegxe ton kodika pou evale o xristis".
    event.preventDefault();
    setErrorMessage('');
    setSuccessMessage('');

    if (code.trim().length !== 8) {
      setErrorMessage('Παρακαλώ εισάγετε τον 8-ψήφιο κωδικό.');
      return;
    }

    setIsLoading(true);

    try {
      const response = await fetch(window.appServiceUrl('TwoFactorAuthService.php'), {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ code: code.trim() })
      });

      const data = await response.json();

      if (data.success) {
        setSuccessMessage('Η επαλήθευση ολοκληρώθηκε επιτυχώς. Μεταφορά...');
        setTimeout(() => {
          fetch(window.appServiceUrl('SessionCheck.php'), {
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
        setErrorMessage(data.message || 'Μη έγκυρος κωδικός.');
      }
    } catch (error) {
      console.error('Error verifying code:', error);
      setErrorMessage('Παρουσιάστηκε σφάλμα. Παρακαλώ προσπαθήστε ξανά.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleCodeKeyDown = (event) => {
    // Epitetai submit me Enter mono otan o kodikas exei to sosto mikos.
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
      <p id="two-factor-label">Παρακαλώ εισάγετε τον κωδικό που στάλθηκε στο email σας:</p>
      <form onSubmit={handleSubmit}>
        <input
          type="text"
          id="code-input"
          placeholder="Εισάγετε 8-ψήφιο κωδικό"
          value={code}
          onChange={(e) => setCode(e.target.value.replace(/[^a-zA-Z0-9]/g, '').slice(0, 8))}
          onKeyDown={handleCodeKeyDown}
          maxLength="8"
          pattern="[A-Za-z0-9]{8}"
          required
        />
        <button id="confirm-button" type="submit" disabled={isLoading || code.length !== 8}>
          {isLoading ? 'Γίνεται επαλήθευση...' : 'Επιβεβαίωση'}
        </button>
      </form>
      <div className="resend-wrapper">
        <span className="resend-hint">Δεν το λάβατε;</span>
        <button id="resend-button" type="button" onClick={sendCodeAgain} disabled={isResending || isLoading}>
          <i className="fas fa-rotate-right" aria-hidden="true"></i>
          <span>{isResending ? 'Αποστολή νέου κωδικού...' : 'Επαναποστολή κωδικού'}</span>
        </button>
      </div>
    </div>
  );
}

const root = document.getElementById('two-factor-root');
ReactDOM.render(<TwoFactorForm />, root);
