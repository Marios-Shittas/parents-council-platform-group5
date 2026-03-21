function SubscriptionPage() {

    const [data, setData] = React.useState(null);
    const [includeInsurance, setIncludeInsurance] = React.useState(true);
    const [loading, setLoading] = React.useState(false);

    // ✅ Fetch data ONLY
    React.useEffect(() => {
        fetch("../app/services/Subscription.php")
            .then(res => res.json())
            .then(data => setData(data))
            .catch(err => console.error(err));
    }, []);

    // ✅ ONE handlePayment ONLY
    const handlePayment = () => {
        if (loading) return;

        setLoading(true);

        fetch("../app/services/InsertPayment.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                includeInsurance: includeInsurance
            })
        })
        .then(res => res.json())
        .then(response => {
            console.log(response);
            alert("Η πληρωμή δημιουργήθηκε!");
        })
        .catch(err => console.error(err))
        .finally(() => setLoading(false));
    };

    // ✅ Loading state
    if (!data) {
        return <div className="text-center mt-5">Loading...</div>;
    }

    // ✅ Calculations
    const insuranceTotal = includeInsurance ? data.insurance_total : 0;
    const total = data.subscription_price + insuranceTotal;

    return (
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
    );
}

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(<SubscriptionPage />);