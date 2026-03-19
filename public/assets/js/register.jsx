function RegisterForm() {
    const [form, setForm] = React.useState({
        first_name: '',
        last_name: '',
        phone: '',
        email: '',
        viber_consent: false,
        consent: false
    });

    const [children, setChildren] = React.useState([
        { child_name: '', child_last_name: '', child_dob: '', child_class: '' }
    ]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!form.consent) {
            alert("Πρέπει να συμφωνήσετε με την πολιτική απορρήτου.");
            return;
        }

        try {
            const response = await fetch('../app/services/RegisteringService.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...form, children })
            });

            const result = await response.json();
            alert(result.message);
        } catch (error) {
            alert("Σφάλμα επικοινωνίας με τον server.");
        }
    };

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setForm(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value
        }));
    };

    const handleChildChange = (index, e) => {
        const { name, value } = e.target;
        setChildren(prev => prev.map((child, i) =>
            i === index ? { ...child, [name]: value } : child
        ));
    };

    const addChild = () => {
        setChildren(prev => [...prev, { child_name: '', child_last_name: '', child_dob: '', child_class: '' }]);
    };

    const removeChild = (index) => {
        setChildren(prev => prev.filter((_, i) => i !== index));
    };
return (
    <div>
        <section className="public-page-header" aria-labelledby="public-page-title">
            <div className="container">
                <div className="public-page-header__card">
                    <div className="public-page-header__icon" aria-hidden="true">
                        <i className="fas fa-user-plus"></i>
                    </div>
                    <div className="public-page-header__content">
                        <span className="public-page-header__eyebrow">Δημόσια Σελίδα</span>
                        <h1 id="public-page-title">Εγγραφή</h1>
                        <p className="public-page-header__subtitle">
                            Εγγραφείτε ως γονέας στο σύστημα για να έχετε πρόσβαση στις υπηρεσίες του σχολείου.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div className="register-page">
            <div className="container">
                <p className="register-intro">
                    Με την εγγραφή σας στον Σύνδεσμο Γονέων μπορείτε να αποκτήσετε πρόσβαση στο σύστημα και
                    να επωφεληθείτε από τις διαθέσιμες υπηρεσίες και λειτουργίες της πλατφόρμας, όπως ενημέρωση
                    για εκδηλώσεις και δραστηριότητες, συμμετοχή σε events, αγορές προϊόντων που προσφέρει ο
                    σύνδεσμος, καθώς και υποβολή αιτήσεων για διάφορες δράσεις και υπηρεσίες κατά τη διάρκεια
                    της σχολικής χρονιάς. Πριν προχωρήσετε, παρακαλούμε συμπληρώστε προσεκτικά τα στοιχεία σας.{' '}
                    <strong>Μετά την υποβολή, η αίτησή σας θα τεθεί σε αναμονή μέχρι να εγκριθεί από τον Σύνδεσμο.
                    Θα λάβετε email με περαιτέρω οδηγίες.</strong>
                </p>

                <form onSubmit={handleSubmit}>

                    {/* ── Στοιχεία Κηδεμόνα ── */}
                    <div className="register-section-card">
                        <div className="register-card-header">
                            <div className="register-card-icon">
                                <i className="fas fa-user"></i>
                            </div>
                            <div>
                                <span className="register-card-eyebrow">Φόρμα Εγγραφής</span>
                                <p className="register-card-title">Στοιχεία Κηδεμόνα</p>
                            </div>
                        </div>
                        <div className="row">
                            <div className="col-md-6">
                                <div className="form-group-inline">
                                    <label>Όνομα:</label>
                                    <input name="first_name" type="text" onChange={handleChange} className="form-control" required />
                                </div>
                            </div>
                            <div className="col-md-6">
                                <div className="form-group-inline">
                                    <label>Επώνυμο:</label>
                                    <input name="last_name" type="text" onChange={handleChange} className="form-control" required />
                                </div>
                            </div>
                            <div className="col-md-6">
                                <div className="form-group-inline">
                                    <label>Τηλέφωνο:</label>
                                    <input name="phone" type="text" onChange={handleChange} pattern="[0-9]{8}" className="form-control" required />
                                </div>
                            </div>
                            <div className="col-md-6">
                                <div className="form-group-inline">
                                    <label>Email:</label>
                                    <input name="email" type="email" onChange={handleChange} className="form-control" required />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* ── Στοιχεία Παιδιών ── */}
                    {children.map((child, index) => (
                        <div key={index} className="register-section-card">
                            <div className="register-card-header">
                                <div className="register-card-icon">
                                    <i className="fas fa-child"></i>
                                </div>
                                <div className="flex-grow-1">
                                    <span className="register-card-eyebrow">{1+index}o Παιδί</span>
                                    <p className="register-card-title">Στοιχεία Παιδιού</p>
                                </div>
                                {children.length > 1 && (
                                    <button type="button" className="btn-register-remove" onClick={() => removeChild(index)}>
                                        <i className="fas fa-times me-1"></i> Αφαίρεση
                                    </button>
                                )}
                            </div>
                            <div className="row">
                                <div className="col-md-6">
                                    <div className="form-group-inline">
                                        <label>Όνομα:</label>
                                        <input name="child_name" type="text" value={child.child_name} onChange={(e) => handleChildChange(index, e)} className="form-control" required />
                                    </div>
                                </div>
                                <div className="col-md-6">
                                    <div className="form-group-inline">
                                        <label>Επώνυμο:</label>
                                        <input name="child_last_name" type="text" value={child.child_last_name} onChange={(e) => handleChildChange(index, e)} className="form-control" required />
                                    </div>
                                </div>
                                <div className="col-md-6">
                                    <div className="form-group-inline">
                                        <label>Ημ. Γέννησης:</label>
                                        <input name="child_dob" type="date" value={child.child_dob} onChange={(e) => handleChildChange(index, e)} className="form-control" required />
                                    </div>
                                </div>
                                <div className="col-md-6">
                                    <div className="form-group-inline">
                                        <label>Τάξη:</label>
                                        <input name="child_class" type="text" value={child.child_class} onChange={(e) => handleChildChange(index, e)} className="form-control" placeholder="πχ. Α΄3" required />
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}

                    <button type="button" className="btn-register-add" onClick={addChild}>
                        <i className="fas fa-plus me-2"></i>Προσθήκη Παιδιού
                    </button>

                    {/* ── Viber ── */}
                    <div className="consent-row">
                        <input type="checkbox" name="viber_consent" id="viber_consent" onChange={handleChange} />
                        <label htmlFor="viber_consent">
                            Αποδέχομαι να προστεθώ στην ομάδα Viber του Συνδέσμου Γονέων.
                        </label>
                    </div>

                    {/* ── Πολιτική Απορρήτου ── */}
                    <div className="consent-row">
                        <input type="checkbox" name="consent" id="consent" onChange={handleChange} required />
                        <label htmlFor="consent">
                            Επιλέγοντας αυτό το πλαίσιο ελέγχου, επιβεβαιώνετε ότι έχετε διαβάσει
                            και κατανοήσει τις πληροφορίες που παρέχονται και συναινείτε στη
                            συλλογή και αποθήκευση των προσωπικών σας δεδομένων. Τα δεδομένα
                            που παρέχετε θα χρησιμοποιηθούν αποκλειστικά για τους σκοπούς της
                            επεξεργασίας του αιτήματός σας και θα αντιμετωπιστούν σύμφωνα με
                            τους ισχύοντες κανονισμούς προστασίας δεδομένων.
                        </label>
                    </div>

                    <div>
                        <button className="btn-register-submit" type="submit">
                            <i className="fas fa-paper-plane me-2"></i>Υποβολή Αίτησης
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
);
}

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(<RegisterForm />);