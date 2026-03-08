function RegisterForm() {
    const [form, setForm] = React.useState({
        first_name: '',
        last_name: '',
        phone: '',
        children_count: '',
        email: '',
        password: '',
        password_confirm: '',
        cc_number: '',
        cc_name: '',
        cc_exp: '',
        cc_cvv: '',
        consent: false
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!emailVerified) {
            alert("Πρέπει πρώτα να επικυρώσετε το email σας!");
            return;
        }

        if (form.password !== form.password_confirm) {
            alert("Ο κωδικός και η επιβεβαίωση δεν ταιριάζουν!");
            return;
        }

        if (!/^\d{16,19}$/.test(form.cc_number.replace(/\s/g, ''))) {
            alert("Ο αριθμός κάρτας πρέπει να είναι 16-19 ψηφία.");
            return;
        }

        if (!/^\d{2}\/\d{2}$/.test(form.cc_exp)) {
            alert("Η ημερομηνία λήξης πρέπει να είναι στο format MM/YY.");
            return;
        }

        if (!/^\d{3}$/.test(form.cc_cvv)) {
            alert("Το CVV πρέπει να είναι 3 ψηφία.");
            return;
        }

        if (!form.consent) {
            alert("Πρέπει να συμφωνήσετε με την πολιτική απορρήτου.");
            return;
        }

        alert("Επιτυχής εγγραφή και πληρωμή!");
            // εδώ μπορείς να στείλεις τα δεδομένα στο backend
    }

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setForm(prev => ({
            ...prev,
            [name]: type === 'checkbox' ? checked : value
        }));
    }

    const [emailVerified, setEmailVerified] = React.useState(false);

    const EmailhandleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setForm(prev => ({
        ...prev,
        [name]: type === 'checkbox' ? checked : value
    }));

    // Reset email verification if email is changed
    if (name === 'email') setEmailVerified(false);
};

    const formatCardNumber = (value) => {
            return value.replace(/\D/g, '').replace(/(.{4})/g, '$1 ').trim();
        };

        const handleCardNumberChange = (e) => {
            const formatted = formatCardNumber(e.target.value);
            setForm(prev => ({
                ...prev,
                cc_number: formatted
            }));
        };
    
    const formatExpiry = (value) => {
    // Remove non-digit characters
        let digits = value.replace(/\D/g, '');

        // Limit month to 12
        if (digits.length >= 2) {
            let month = parseInt(digits.slice(0, 2), 10);
            if (month > 12) month = 12;
            digits = month.toString().padStart(2, '0') + digits.slice(2);
        }

        // Add '/' after 2 digits
        if (digits.length > 2) {
            return digits.slice(0, 2) + '/' + digits.slice(2, 4);
        }
        return digits;
    };

    const handleExpiryChange = (e) => {
        const formatted = formatExpiry(e.target.value);
            setForm(prev => ({
                ...prev,
                cc_exp: formatted
        }));
    };
    
    const handleEmailVerify = () => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(form.email)) {
            alert("Το email δεν είναι έγκυρο!");
            setEmailVerified(false);
            return;
        }
        alert("Το email επικυρώθηκε!");
        setEmailVerified(true);
    };

    return (
        <div className="container mt-3">
            <form onSubmit={handleSubmit}>
                <h1 className="mt-1">Εγγραφή Μελών στον Σύνδεσμο Γονέων</h1>
                <p>Με την εγγραφή σας στον Σύνδεσμο Γονέων μπορείτε να αποκτήσετε πρόσβαση στο σύστημα και να επωφεληθείτε από τις διαθέσιμες υπηρεσίες και λειτουργίες της πλατφόρμας. Μέσω της συνδρομής σας θα έχετε τη δυνατότητα να ενημερώνεστε για εκδηλώσεις και δραστηριότητες, να συμμετέχετε σε events, να πραγματοποιείτε αγορές προϊόντων που προσφέρει ο σύνδεσμος, καθώς και να υποβάλλετε αιτήσεις για διάφορες δράσεις και υπηρεσίες. Συμπληρώστε τα προσωπικά σας στοιχεία και τα στοιχεία της κάρτας σας στα παρακάτω πεδία, ώστε να ολοκληρωθεί η εγγραφή και η πληρωμή της συνδρομής σας με ασφάλεια.</p>
                <div className="register">
                    <h2>Εγγραφή</h2>
                    <p>Για να εγγραφείτε στον Σύνδεσμο Γονέων, παρακαλούμε συμπληρώστε τα παρακάτω πεδία με τα προσωπικά σας στοιχεία. Η εγγραφή σας θα σας επιτρέψει να αποκτήσετε πρόσβαση σε όλες τις υπηρεσίες και λειτουργίες της πλατφόρμας μας.</p>
                    <div className="row px-3">
                        <div className="col-md-4">
                            <label>Όνομα:</label>
                            <input name="first_name"
                                type="text"
                                onChange={handleChange}
                                className="form-control"
                                required/>
                        </div>
                        <div className="col-md-4">
                            <label>Επώνυμο:</label>
                            <input name="last_name"
                                type="text"
                                onChange={handleChange}
                                className="form-control"
                                required/>
                        </div>
                        <div className="col-md-4">
                            <label>Τηλέφωνο:</label>
                            <input 
                                name="phone"
                                type="text"
                                onChange={handleChange}
                                pattern="[0-9]{8}"
                                className="form-control"
                                required/>
                        </div>
                        <div className="col-md-4 mt-3">
                            <label>Αριθμός παιδιών που φοιτούν στο γυμνάσιο:</label>
                            <input 
                                name="children_count"
                                type="text"
                                maxlength="1"
                                onChange={handleChange}
                                pattern="[0-9]{1}"
                                className="form-control"
                                required/>
                        </div>
                        <div className="col-md-4 mt-3">
                            <label>Email:</label>
                            <input name="email"
                                type="email"
                                onChange={EmailhandleChange}
                                className="form-control"
                                required/>
                        </div>
                    </div>
                    <div className="row md-4 px-3">
                        <div className="col-md-4 mt-3">
                            <label>Κωδικός:</label>
                            <input name="password"
                                type="password"
                                minlength="8"
                                onChange={handleChange}
                                className="form-control"
                                required/>
                        </div>
                        <div className="col-md-4 mt-3">
                            <label>Επιβεβαίωση Κωδικού:</label>
                            <input name="password_confirm"
                                type="password"
                                onChange={handleChange}
                                className="form-control"
                                required/>
                        </div>
                    </div>
                    <div className="col-md-4">
                        <button className="mt-4 btn btn-primary" type="button" onClick={handleEmailVerify}>Επικύρωση email</button>
                    </div>
                </div>
                
                <div className="mt-3">
                    <h3>Πληρωμή Συνδρομής</h3>
                </div>
                <div className="credit-card">
                    <h4>Στοιχεία Κάρτας</h4>
                    <div className="row-card-number-cardholder">
                        <div>
                            <label>Αριθμός Κάρτας:</label>
                            <input name="cc_number"
                                type="text" 
                                value={form.cc_number}
                                onChange={handleCardNumberChange}
                                pattern="[0-9]{16/19}"
                                className="form-control"  
                                maxlength="19"
                                placeholder="0000 0000 0000 0000"
                                required/>
                        </div>
                        <div>
                            <label>Ονοματεπώνυμο Κατόχου:</label>
                            <input name="cc_name"
                                type="text" 
                                className="form-control" 
                                onChange={handleChange}
                                placeholder="NAME & SURNAME"
                                required/>
                        </div>
                    </div>
                    <div className="row-cvv-expiry">
                        <div className="col-md-2">
                            <label>Ημ.Λήξης:</label>
                            <input name="cc_exp"  
                                value={form.cc_exp}
                                onChange={handleExpiryChange} 
                                pattern="[0-9//]{5}" 
                                type="text" 
                                className="form-control" 
                                maxlength="5" 
                                placeholder="00/00" 
                                required/>
                        </div>
                        <div className="col-md-2">
                            <label>CVV:</label>
                            <input name="cc_cvv" onChange={handleChange} pattern="[0-9]{3}" type="password" className="form-control" maxlength="3" placeholder="•••" required/>
                        </div>
                    </div>
                </div>
                <div className="m-3">
                    <label>
                    Επιλέγοντας αυτό το πλαίσιο ελέγχου, επιβεβαιώνετε ότι έχετε διαβάσει 
                    και κατανοήσει τις πληροφορίες που παρέχονται και συναινείτε στη 
                    συλλογή και αποθήκευση των προσωπικών σας δεδομένων. Τα δεδομένα 
                    που παρέχετε θα χρησιμοποιηθούν αποκλειστικά για τους σκοπούς της 
                    επεξεργασίας του αιτήματός σας και θα αντιμετωπιστούν σύμφωνα με 
                    τους ισχύοντες κανονισμούς προστασίας δεδομένων.
                    </label>
                    <input type="checkbox" name="consent" onChange={handleChange} required/>
                </div>
                <button className="btn btn-primary mt-3 m-5" type="submit">Εγγραφή & Πληρωμή €9.99</button>
            </form>
        </div>
    );
}

// Mount React
const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(<RegisterForm />);