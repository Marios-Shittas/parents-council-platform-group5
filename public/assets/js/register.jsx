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
            <div className="title">
                <h1>Εγγραφή Μελών στον Σύνδεσμο Γονέων</h1>
            </div>
            <div className="container mt-3">
                <p>Με την εγγραφή σας στον Σύνδεσμο Γονέων μπορείτε να αποκτήσετε πρόσβαση στο σύστημα και να επωφεληθείτε από τις διαθέσιμες υπηρεσίες και λειτουργίες της πλατφόρμας, όπως ενημέρωση για εκδηλώσεις και δραστηριότητες, συμμετοχή σε events, αγορές προϊόντων που προσφέρει ο σύνδεσμος, καθώς και υποβολή αιτήσεων για διάφορες δράσεις και υπηρεσίες κατά τη διάρκεια της σχολικής χρονιάς. Πριν προχωρήσετε, παρακαλούμε συμπληρώστε προσεκτικά τα στοιχεία σας. <strong>Μετά την υποβολή, η αίτησή σας θα τεθεί σε αναμονή μέχρι να εγκριθεί από τον Σύνδεσμο. Θα λάβετε email με περαιτέρω οδηγίες.</strong></p>
                <form onSubmit={handleSubmit}>

                    {/* Στοιχεία Κηδεμόνα */}
                    <div className="register">
                        <p>Στοιχεία Κηδεμόνα:</p>
                        <div className="row px-3">
                            <div className="col-md-6">
                                <div className="form-group-inline">
                                    <label>Όνομα:</label>
                                    <input name="first_name" type="text" onChange={handleChange} className="form-control" required/>
                                </div>
                            </div>
                            <div className="col-md-6">
                                <div className="form-group-inline">
                                    <label>Επώνυμο:</label>
                                    <input name="last_name" type="text" onChange={handleChange} className="form-control" required/>
                                </div>
                            </div>
                            <div className="col-md-6">
                                <div className="form-group-inline">
                                    <label>Τηλέφωνο:</label>
                                    <input name="phone" type="text" onChange={handleChange} pattern="[0-9]{8}" className="form-control" required/>
                                </div>
                            </div>
                            <div className="col-md-6">
                                <div className="form-group-inline">
                                    <label>Email:</label>
                                    <input name="email" type="email" onChange={handleChange} className="form-control" required/>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Στοιχεία Παιδιών */}
                    <div className="register mt-4">
                        <p>Στοιχεία Παιδιών:</p>
                        {children.map((child, index) => (
                            <div key={index} className="border rounded p-3 mb-3" style={{borderColor: 'rgba(255,255,255,0.3)'}}>
                                <div className="d-flex justify-content-between align-items-center mb-2">
                                    <strong>Παιδί {index + 1}</strong>
                                    {children.length > 1 && (
                                        <button type="button" className="btn btn-danger btn-sm" onClick={() => removeChild(index)}>
                                            Αφαίρεση
                                        </button>
                                    )}
                                </div>
                                <div className="row">
                                    <div className="col-md-6">
                                        <div className="form-group-inline">
                                            <label>Όνομα:</label>
                                            <input name="child_name" type="text" value={child.child_name} onChange={(e) => handleChildChange(index, e)} className="form-control" required/>
                                        </div>
                                    </div>
                                    <div className="col-md-6">
                                        <div className="form-group-inline">
                                            <label>Επώνυμο:</label>
                                            <input name="child_last_name" type="text" value={child.child_last_name} onChange={(e) => handleChildChange(index, e)} className="form-control" required/>
                                        </div>
                                    </div>
                                    <div className="col-md-6">
                                        <div className="form-group-inline">
                                            <label>Ημ. Γέννησης:</label>
                                            <input name="child_dob" type="date" value={child.child_dob} onChange={(e) => handleChildChange(index, e)} className="form-control" required/>
                                        </div>
                                    </div>
                                    <div className="col-md-6">
                                        <div className="form-group-inline">
                                            <label>Τάξη:</label>
                                            <input name="child_class" type="text" value={child.child_class} onChange={(e) => handleChildChange(index, e)} className="form-control" placeholder="πχ. Α΄3" required/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                        <button type="button" className="btn btn-secondary" onClick={addChild}>
                            + Προσθήκη Παιδιού
                        </button>
                    </div>

                    {/* Viber */}
                    <div className="m-3 mt-4 d-flex align-items-center gap-2">
                        <input type="checkbox" name="viber_consent" onChange={handleChange} required/>
                        <label className="email-consent px-3">
                            Αποδέχομαι να προστεθώ στην ομάδα Viber του Συνδέσμου Γονέων.
                        </label>
                    </div>

                    {/* Πολιτική Απορρήτου */}
                    <div className="m-3 d-flex align-items-start gap-2">
                        <input type="checkbox" name="consent" onChange={handleChange} className="mt-1" required/>
                        <label className="email-consent px-3">
                            Επιλέγοντας αυτό το πλαίσιο ελέγχου, επιβεβαιώνετε ότι έχετε διαβάσει
                            και κατανοήσει τις πληροφορίες που παρέχονται και συναινείτε στη
                            συλλογή και αποθήκευση των προσωπικών σας δεδομένων. Τα δεδομένα
                            που παρέχετε θα χρησιμοποιηθούν αποκλειστικά για τους σκοπούς της
                            επεξεργασίας του αιτήματός σας και θα αντιμετωπιστούν σύμφωνα με
                            τους ισχύοντες κανονισμούς προστασίας δεδομένων.
                        </label>
                    </div>

                    <button className="btn btn-primary mt-3 m-5" type="submit">Υποβολή Αίτησης</button>
                </form>
            </div>
        </div>
    );
}

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(<RegisterForm />);