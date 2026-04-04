function SubscriptionPage() {

    const [data, setData] = React.useState(null);
    const [error, setError] = React.useState("");
    const [includeInsurance, setIncludeInsurance] = React.useState(true);
    const [loading, setLoading] = React.useState(false);
    const [notice, setNotice] = React.useState({
        open: false,
        title: '',
        message: '',
        variant: 'warning'
    });
    const token = window.APPROVAL_TOKEN || "";
    const baseServiceUrl = "/parents-council-platform-group5/app/services";

    const showNotice = React.useCallback((message, options = {}) => {
        setNotice({
            open: true,
            title: options.title || 'Ειδοποίηση',
            message: message || 'Συνέβη ένα απρόσμενο σφάλμα.',
            variant: options.variant || 'warning'
        });
    }, []);

    React.useEffect(() => {
        if (!notice.open) {
            return undefined;
        }

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        return () => {
            document.body.style.overflow = previousOverflow;
        };
    }, [notice.open]);

    const closeNotice = React.useCallback(() => {
        setNotice({ open: false, title: '', message: '', variant: 'warning' });
    }, []);

    React.useEffect(() => {
        fetch(baseServiceUrl + "/Subscription.php?token=" + encodeURIComponent(token))
            .then(res => {
                if (!res.ok) {
                    throw new Error("Αποτυχία φόρτωσης στοιχείων πληρωμής.");
                }
                return res.json();
            })
            .then(response => {
                if (!response.success) {
                    throw new Error(response.message || "Μη έγκυρο token.");
                }
                setData(response.data);
                setError("");
            })
            .catch(err => {
                console.error(err);
                setError(err.message || "Δεν ήταν δυνατή η φόρτωση των τιμών.");
            });
    }, [token, baseServiceUrl]);


    const handlePayment = () => {
        if (loading) return;

        setLoading(true);

        fetch(baseServiceUrl + "/InsertPayment.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                includeInsurance: includeInsurance,
                token: token
            })
        })
        .then(res => res.json())
        .then(response => {
            if (!response.success) {
                showNotice(response.message || "Αποτυχία πληρωμής.", {
                    title: 'Αποτυχία πληρωμής',
                    variant: 'error'
                });
                return;
            }

            if (!response.redirect_url) {
                showNotice("Δεν επιστράφηκε σύνδεσμος πληρωμής από την JCC.", {
                    title: 'Σφάλμα πληρωμής',
                    variant: 'error'
                });
                return;
            }

            window.location.href = response.redirect_url;
        })
        .catch(err => {
            console.error(err);
            showNotice("Παρουσιάστηκε σφάλμα κατά τη δημιουργία πληρωμής.", {
                title: 'Σφάλμα επικοινωνίας',
                variant: 'error'
            });
        })
        .finally(() => setLoading(false));
    };

    if (error) {
        return <div className="alert alert-danger mt-5 text-center">{error}</div>;
    }

    if (!data) {
        return <div className="text-center mt-5">Loading...</div>;
    }

    const insuranceTotal = includeInsurance ? data.insurance_total : 0;
    const total = data.subscription_price + insuranceTotal;

    return (
        <>
        <div className="subscription-wrapper">

            <div className="subscription-header text-center">
                <div className="subscription-icon">
                    <i className="fas fa-check-circle"></i>
                </div>

                <h2>Η αίτησή σας εγκρίθηκε!</h2>
                <p className="subtitle">
                    Συγχαρητήρια! Η εγγραφή σας έχει εγκριθεί από τον Σύνδεσμο Γονέων.
                </p>
            </div>

            <div className="subscription-card">

                <h4>Ανάλυση Πληρωμής</h4>

                <div className="subscription-breakdown">

                    <div className="d-flex justify-content-between">
                        <span>Συνδρομή</span>
                        <strong>€{data.subscription_price}</strong>
                    </div>

                    <div className="d-flex justify-content-between">
                        <span>Ασφάλεια ανά παιδί</span>
                        <strong>€{data.insurance_price}</strong>
                    </div>

                    <div className="d-flex justify-content-between">
                        <span>Αριθμός παιδιών</span>
                        <strong>{data.children_count}</strong>
                    </div>

                    {/* Toggle */}
                    <div className="mt-3">
                        <label>
                            <input 
                                type="checkbox"
                                checked={includeInsurance}
                                onChange={() => setIncludeInsurance(!includeInsurance)}
                            />{" "}
                            Συμπερίληψη ασφάλειας
                        </label>
                    </div>

                    <hr />

                    <div className="d-flex justify-content-between">
                        <span>Σύνολο ασφάλειας</span>
                        <strong>€{insuranceTotal}</strong>
                    </div>

                    <div className="d-flex justify-content-between font-weight-bold mt-2">
                        <span>Τελικό Σύνολο</span>
                        <strong>€{total}</strong>
                    </div>

                </div>

                <button 
                    className="btn btn-primary btn-block btn-pay mt-4"
                    onClick={handlePayment}
                    disabled={loading}
                >
                    {loading ? "Processing..." : "Πληρωμή μέσω JCC"}
                </button>

            </div>
        </div>

        {notice.open && (
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="subscription-notice-title"
                onClick={closeNotice}
                style={{
                    position: 'fixed',
                    inset: 0,
                    zIndex: 1600,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    background: 'rgba(11, 25, 41, 0.62)',
                    padding: '1rem'
                }}
            >
                <div
                    role="document"
                    onClick={(event) => event.stopPropagation()}
                    style={{
                        width: 'min(500px, 100%)',
                        background: '#fff',
                        borderRadius: '18px',
                        borderTop: `5px solid ${notice.variant === 'error' ? '#c84545' : notice.variant === 'warning' ? '#d79f0d' : '#2f6ea0'}`,
                        boxShadow: '0 20px 50px rgba(11,25,41,.32)',
                        padding: '1.25rem 1.1rem'
                    }}
                >
                    <h3 id="subscription-notice-title" style={{ fontFamily: 'Montserrat, sans-serif', fontWeight: 700, color: '#1a3a5c', textAlign: 'center', marginBottom: '.5rem' }}>
                        {notice.title}
                    </h3>
                    <div style={{ fontFamily: 'Lato, sans-serif', color: '#344055', lineHeight: 1.55, textAlign: 'center', whiteSpace: 'pre-wrap' }}>
                        {notice.message}
                    </div>
                    <div style={{ display: 'flex', justifyContent: 'center', marginTop: '1rem' }}>
                        <button
                            type="button"
                            onClick={closeNotice}
                            style={{
                                border: 0,
                                borderRadius: '999px',
                                padding: '.48rem 1.2rem',
                                background: notice.variant === 'error' ? '#bb3535' : notice.variant === 'warning' ? '#b8860b' : '#1f5f93',
                                color: '#fff',
                                fontWeight: 700,
                                fontFamily: 'Lato, sans-serif'
                            }}
                        >
                            Εντάξει
                        </button>
                    </div>
                </div>
            </div>
        )}
        </>
    );
}

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(<SubscriptionPage />);