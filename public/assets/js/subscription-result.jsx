function SubscriptionResultPage() {
    const [state, setState] = React.useState({
        type: "loading",
        title: "Επεξεργασία πληρωμής",
        text: "Γίνεται έλεγχος της συναλλαγής σας με την JCC...",
        tx: ""
    });

    React.useEffect(() => {
        const params = window.SUBSCRIPTION_RESULT_PARAMS || {};
        const query = new URLSearchParams(params).toString();
        const endpoint = "/parents-council-platform-group5/app/services/SubscriptionJcc.php?" + query;

        fetch(endpoint)
            .then(function (res) {
                return res.json();
            })
            .then(function (response) {
                if (!response.success) {
                    setState({
                        type: "failed",
                        title: "Αποτυχία ελέγχου",
                        text: response.message || "Η επεξεργασία της πληρωμής απέτυχε.",
                        tx: ""
                    });
                    return;
                }

                if (response.payment_status === "completed") {
                    setState({
                        type: "success",
                        title: "Η πληρωμή ολοκληρώθηκε",
                        text: "Η συνδρομή σας ενεργοποιήθηκε επιτυχώς και μπορείτε να συνδεθείτε στο σύστημα.",
                        tx: response.transaction_id || ""
                    });
                    return;
                }

                if (response.payment_status === "pending") {
                    setState({
                        type: "pending",
                        title: "Η πληρωμή εκκρεμεί",
                        text: "Η πληρωμή είναι σε εκκρεμότητα. Ελέγξτε ξανά σε λίγα λεπτά.",
                        tx: ""
                    });
                    return;
                }

                setState({
                    type: "failed",
                    title: "Η πληρωμή απέτυχε",
                    text: "Η πληρωμή δεν ολοκληρώθηκε. Παρακαλώ δοκιμάστε ξανά.",
                    tx: ""
                });
            })
            .catch(function () {
                setState({
                    type: "failed",
                    title: "Σφάλμα επικοινωνίας",
                    text: "Παρουσιάστηκε σφάλμα κατά τον έλεγχο της συναλλαγής.",
                    tx: ""
                });
            });
    }, []);

    function renderIcon(type) {
        if (type === "success") return "\u2713";
        if (type === "pending") return "!";
        if (type === "failed") return "\u00d7";
        return "...";
    }

    return (
        <div className="container py-5 d-flex align-items-center subscription-result-screen">
            <div className="row justify-content-center w-100">
                <div className="col-lg-8 col-md-10">
                    <div className="card result-card">
                        <div className="card-body p-4 p-md-5 text-center">
                            <div className={"result-badge " + state.type}>{renderIcon(state.type)}</div>
                            <h2 className="result-title mt-3 mb-2">{state.title}</h2>
                            <p className="result-subtitle mb-0">{state.text}</p>

                            {state.tx ? (
                                <div>
                                    <span className="tx-pill">Transaction ID: {state.tx}</span>
                                </div>
                            ) : null}

                            <div className="mt-4">
                                <a className="btn btn-primary" href="/parents-council-platform-group5/public/login.php">
                                    Μετάβαση στη Σύνδεση
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

const resultRoot = ReactDOM.createRoot(document.getElementById("subscription-result-root"));
resultRoot.render(<SubscriptionResultPage />);
