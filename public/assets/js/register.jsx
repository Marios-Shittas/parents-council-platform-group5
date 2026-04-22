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
    const [showPhoneError, setShowPhoneError] = React.useState(false);
    const [notice, setNotice] = React.useState({
        open: false,
        variant: 'success',
        title: '',
        message: ''
    });

    React.useEffect(() => {
        if (!notice.open) {
            return undefined;
        }

        const originalOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        return () => {
            document.body.style.overflow = originalOverflow;
        };
    }, [notice.open]);

    const isPhoneValid = (value) => /^\d{8}$/.test(value);

    const handlePhoneKeyDown = (e) => {
        if (e.key === 'Enter' && !isPhoneValid(form.phone)) {
            e.preventDefault();
            setShowPhoneError(true);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!isPhoneValid(form.phone)) {
            setShowPhoneError(true);
            return;
        }

        try {
            const response = await fetch('../app/services/RegisteringService.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({
                    ...form,
                    phone: `+357${form.phone}`,
                    children
                })
            });

            const result = await response.json();

            if (result.success) {
                setNotice({
                    open: true,
                    variant: 'success',
                    title: 'Η εγγραφή καταχωρήθηκε επιτυχώς',
                    message: 'Θα ενημερωθείτε μέσω email όταν εγκριθεί από τον διαχειριστή.'
                });
                return;
            }

            setNotice({
                open: true,
                variant: 'error',
                title: 'Η εγγραφή δεν ολοκληρώθηκε',
                message: result.message || 'Παρουσιάστηκε σφάλμα κατά την υποβολή της αίτησης.'
            });

        } catch (error) {
            setNotice({
                open: true,
                variant: 'error',
                title: 'Σφάλμα επικοινωνίας',
                message: 'Σφάλμα επικοινωνίας με τον server.'
            });
        }
    };

    const closeNotice = () => {
        const shouldRedirect = notice.variant === 'success';
        setNotice({ open: false, variant: 'success', title: '', message: '' });

        if (shouldRedirect) {
            window.location.href = '../public/home.php';
        }
    };

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setForm(prev => ({
            ...prev,
            [name]: type === 'checkbox'
                ? checked
                : name === 'phone'
                    ? value.replace(/\D/g, '').slice(0, 8)
                    : value
        }));

        if (name === 'phone' && isPhoneValid(value.replace(/\D/g, '').slice(0, 8))) {
            setShowPhoneError(false);
        }
    };

    const handleChildChange = (index, e) => {
        const { name, value } = e.target;
        setChildren(prev => prev.map((child, i) =>
            i === index ? { ...child, [name]: value } : child
        ));
    };

    const addChild = () => {
        setChildren(prev => [
            ...prev,
            { child_name: '', child_last_name: '', child_dob: '', child_class: '' }
        ]);
    };

    const removeChild = (index) => {
        setChildren(prev => prev.filter((_, i) => i !== index));
    };

    const getChildOrdinalLabel = (index) => `${index + 1}ο Παιδί`;

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
                        για εκδηλώσεις και δραστηριότητες, συμμετοχή σε εκδηλώσεις, αγορές προϊόντων που προσφέρει ο
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
                                        <div className="register-input-stack">
                                            <input
                                                name="first_name"
                                                type="text"
                                                value={form.first_name}
                                                onChange={handleChange}
                                                className="form-control"
                                                required
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div className="col-md-6">
                                    <div className="form-group-inline">
                                        <label>Επώνυμο:</label>
                                        <div className="register-input-stack">
                                            <input
                                                name="last_name"
                                                type="text"
                                                value={form.last_name}
                                                onChange={handleChange}
                                                className="form-control"
                                                required
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div className="col-md-6">
                                    <div className="form-group-inline">
                                        <label>Τηλέφωνο:</label>
                                        <div className="register-input-stack">
                                            <div className="register-phone-shell">
                                                <span className="register-phone-prefix">+357</span>
                                                <input
                                                    name="phone"
                                                    type="text"
                                                    value={form.phone}
                                                    onChange={handleChange}
                                                    onKeyDown={handlePhoneKeyDown}
                                                    className="form-control"
                                                    inputMode="numeric"
                                                    autoComplete="tel-national"
                                                    maxLength="8"
                                                    pattern="\d{8}"
                                                    title="Το κινητό πρέπει να έχει ακριβώς 8 ψηφία."
                                                    placeholder="99123456"
                                                    required
                                                />
                                            </div>
                                            {showPhoneError && (
                                                <div className="register-field-note register-field-note--error">
                                                    Το κινητό πρέπει να έχει ακριβώς 8 ψηφία μετά το +357.
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                <div className="col-md-6">
                                    <div className="form-group-inline">
                                        <label>Email:</label>
                                        <div className="register-input-stack">
                                            <input
                                                name="email"
                                                type="email"
                                                value={form.email}
                                                onChange={handleChange}
                                                className="form-control"
                                                autoComplete="email"
                                                required
                                            />
                                        </div>
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
                                        <span className="register-card-eyebrow register-card-eyebrow-child">{getChildOrdinalLabel(index)}</span>
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
                                            <div className="register-input-stack">
                                                <input
                                                    name="child_name"
                                                    type="text"
                                                    value={child.child_name}
                                                    onChange={(e) => handleChildChange(index, e)}
                                                    className="form-control"
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-md-6">
                                        <div className="form-group-inline">
                                            <label>Επώνυμο:</label>
                                            <div className="register-input-stack">
                                                <input
                                                    name="child_last_name"
                                                    type="text"
                                                    value={child.child_last_name}
                                                    onChange={(e) => handleChildChange(index, e)}
                                                    className="form-control"
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-md-6">
                                        <div className="form-group-inline">
                                            <label>Ημ. Γέννησης:</label>
                                            <div className="register-input-stack">
                                                <input
                                                    name="child_dob"
                                                    type="date"
                                                    value={child.child_dob}
                                                    onChange={(e) => handleChildChange(index, e)}
                                                    className="form-control"
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="col-md-6">
                                        <div className="form-group-inline">
                                            <label>Τάξη:</label>
                                            <div className="register-input-stack">
                                                <input
                                                    name="child_class"
                                                    type="text"
                                                    value={child.child_class}
                                                    onChange={(e) => handleChildChange(index, e)}
                                                    className="form-control"
                                                    placeholder="πχ. Α΄3"
                                                    required
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}

                        <button type="button" className="btn-register-add" onClick={addChild}>
                            <i className="fas fa-plus me-2"></i>Προσθήκη Παιδιού
                        </button>

                        {/* ── Viber ── */}
                        <div className={`consent-row ${form.viber_consent ? 'is-checked' : ''}`}>
                            <input type="checkbox" name="viber_consent" id="viber_consent" checked={form.viber_consent} onChange={handleChange} required />
                            <label htmlFor="viber_consent">
                                Αποδέχομαι να προστεθώ στην ομάδα Viber του Συνδέσμου Γονέων.
                            </label>
                        </div>

                        {/* ── Πολιτική Απορρήτου ── */}
                        <div className={`consent-row ${form.consent ? 'is-checked' : ''}`}>
                            <input type="checkbox" name="consent" id="consent" checked={form.consent} onChange={handleChange} required />
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

                    {notice.open && (
                        <div className="register-success-modal" role="dialog" aria-modal="true" aria-labelledby="register-notice-title">
                            <div className="register-success-modal__backdrop" onClick={closeNotice}></div>
                            <div className={`register-success-modal__card register-success-modal__card--${notice.variant}`} role="document">
                                <div className="register-success-modal__icon" aria-hidden="true">
                                    <i className={notice.variant === 'success' ? 'fas fa-circle-check' : 'fas fa-triangle-exclamation'}></i>
                                </div>
                                <h2 id="register-notice-title" className="register-success-modal__title">
                                    {notice.title}
                                </h2>
                                <p className="register-success-modal__message">
                                    {notice.message}
                                </p>
                                <button type="button" className="register-success-modal__button" onClick={closeNotice}>
                                    Εντάξει
                                </button>
                            </div>
                        </div>
                    )}

                </div>
            </div>
        </div>
    );
}

const root = ReactDOM.createRoot(document.getElementById('root'));
root.render(<RegisterForm />);
