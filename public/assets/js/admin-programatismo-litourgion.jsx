function formatLogDateTime(value) {
    if (!value) {
        return '-';
    }

    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('el-GR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
}

function AdminProgramatismoLitourgionLogSearch() {
    const rootElement = document.getElementById('admin-programatismo-log-search-root');
    const initialEmail = (rootElement?.dataset.initialEmail || '').trim();
    const [email, setEmail] = React.useState(initialEmail);
    const [submittedEmail, setSubmittedEmail] = React.useState(initialEmail);
    const [loading, setLoading] = React.useState(false);
    const [error, setError] = React.useState('');
    const [parent, setParent] = React.useState(null);
    const [logs, setLogs] = React.useState([]);

    React.useEffect(() => {
        if (initialEmail) {
            void handleSearch(initialEmail, false);
        }
    }, []);

    async function handleSearch(nextEmail, shouldUpdateState = true) {
        const normalizedEmail = String(nextEmail || '').trim();

        if (shouldUpdateState) {
            setSubmittedEmail(normalizedEmail);
        }

        if (!normalizedEmail) {
            setParent(null);
            setLogs([]);
            setError('');
            if (shouldUpdateState) {
                updateQueryString('');
            }
            return;
        }

        setLoading(true);
        setError('');

        try {
            const endpoint = '/parents-council-platform-group5/app/services/AdminLogsService.php?email=' + encodeURIComponent(normalizedEmail);
            const response = await fetch(endpoint, {
                credentials: 'include'
            });
            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Αποτυχία φόρτωσης logs.');
            }

            setParent(payload.parent || null);
            setLogs(Array.isArray(payload.logs) ? payload.logs : []);

            if (!payload.found) {
                setError('Δεν βρέθηκε γονέας με αυτό το email.');
            } else {
                setError('');
            }

            if (shouldUpdateState) {
                updateQueryString(normalizedEmail);
            }
        } catch (fetchError) {
            console.error(fetchError);
            setParent(null);
            setLogs([]);
            setError(fetchError.message || 'Παρουσιάστηκε σφάλμα κατά την αναζήτηση.');
        } finally {
            setLoading(false);
        }
    }

    function updateQueryString(nextEmail) {
        const url = new URL(window.location.href);
        if (nextEmail) {
            url.searchParams.set('email', nextEmail);
        } else {
            url.searchParams.delete('email');
        }

        window.history.replaceState({}, '', url.toString());
    }

    function handleSubmit(event) {
        event.preventDefault();
        void handleSearch(email);
    }

    const emptyState = !loading && !error && !parent && submittedEmail === '';
    const noLogsState = !loading && parent && logs.length === 0;

    return (
        <div className="program-log-search-layout">
            <form className="program-log-search-bar" onSubmit={handleSubmit}>
                <div className="program-log-search-input-wrap">
                    <i className="fas fa-search"></i>
                    <input
                        type="email"
                        className="form-control"
                        placeholder="Πληκτρολόγησε email γονέα"
                        value={email}
                        onChange={(event) => setEmail(event.target.value)}
                    />
                </div>
                <button type="submit" className="btn btn-primary-custom" disabled={loading}>
                    {loading ? 'Αναζήτηση...' : 'Αναζήτηση'}
                </button>
            </form>

            {error ? (
                <div className="program-log-alert program-log-alert--warning">
                    <i className="fas fa-exclamation-triangle me-2"></i>
                    {error}
                </div>
            ) : null}

            {emptyState ? (
                <div className="program-log-empty-state">
                    <i className="fas fa-user-clock"></i>
                    <h3>Αναζήτησε έναν γονέα</h3>
                    <p>Μόλις εισάγεις ένα email, θα εμφανιστούν τα στοιχεία του και οι καταγεγραμμένες ενέργειές του.</p>
                </div>
            ) : null}

            {parent ? (
                <section className="program-log-result-card">
                    <div className="program-log-result-head">
                        <div>
                            <span className="program-feature-kicker">Αποτέλεσμα Αναζήτησης</span>
                            <h3>{parent.name} {parent.surname}</h3>
                        </div>
                        <span className="program-log-email-pill">{parent.email}</span>
                    </div>

                    <div className="program-log-meta-grid">
                        <div>
                            <span>Κατάσταση</span>
                            <strong>{parent.account_status || '-'}</strong>
                        </div>
                        <div>
                            <span>Ημερομηνία Δημιουργίας</span>
                            <strong>{formatLogDateTime(parent.created_at)}</strong>
                        </div>
                        <div>
                            <span>Τηλέφωνο</span>
                            <strong>{parent.phone_number || '-'}</strong>
                        </div>
                        <div>
                            <span>Σύνολο Logs</span>
                            <strong>{logs.length}</strong>
                        </div>
                    </div>

                    {noLogsState ? (
                        <div className="program-log-empty-inline">
                            Δεν βρέθηκαν καταγεγραμμένες ενέργειες για αυτόν τον γονέα.
                        </div>
                    ) : null}

                    {logs.length > 0 ? (
                        <div className="table-responsive mt-3">
                            <table className="table align-middle program-log-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Ημερομηνία</th>
                                        <th>Ενέργεια</th>
                                        <th>Περιγραφή</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {logs.map((log) => (
                                        <tr key={log.log_id}>
                                            <td>{formatLogDateTime(log.created_at)}</td>
                                            <td>
                                                <span className="program-log-action-pill">{log.action || '-'}</span>
                                            </td>
                                            <td>{log.description || '-'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : null}
                </section>
            ) : null}
        </div>
    );
}

const programatismoLogRoot = ReactDOM.createRoot(document.getElementById('admin-programatismo-log-search-root'));
programatismoLogRoot.render(<AdminProgramatismoLitourgionLogSearch />);
