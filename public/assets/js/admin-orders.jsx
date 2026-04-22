function formatCurrency(value) {
    const safeValue = Number(value || 0);
    return new Intl.NumberFormat('el-GR', {
        style: 'currency',
        currency: 'EUR'
    }).format(safeValue);
}

function formatDateTime(dateValue) {
    if (!dateValue) {
        return '-';
    }

    const date = new Date(dateValue.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) {
        return dateValue;
    }

    return new Intl.DateTimeFormat('el-GR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
}

function AdminOrdersPage() {
    const [payload, setPayload] = React.useState(null);
    const [loading, setLoading] = React.useState(true);
    const [error, setError] = React.useState('');

    React.useEffect(() => {
        const endpoint = '/parents-council-platform-group5/app/services/OrdersService.php';

        fetch(endpoint, {
            credentials: 'include'
        })
            .then(async (response) => {
                const text = await response.text();
                const data = text ? JSON.parse(text) : {};

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Αποτυχία φόρτωσης πληρωμένων παραγγελιών.');
                }

                setPayload(data);
                setError('');
            })
            .catch((err) => {
                console.error(err);
                setError(err.message || 'Παρουσιάστηκε σφάλμα κατά τη φόρτωση.');
            })
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return (
            <div className="orders-state-card">
                <i className="fas fa-spinner fa-spin mr-2"></i>
                Φόρτωση δεδομένων...
            </div>
        );
    }

    if (error) {
        return (
            <div className="orders-state-card orders-error-card">
                <i className="fas fa-exclamation-triangle mr-2"></i>
                {error}
            </div>
        );
    }

    const summary = payload.summary || {
        paid_orders_count: 0,
        total_revenue: 0,
        total_items_count: 0
    };

    const totalsByProduct = payload.totals_by_product || [];
    const orders = payload.orders || [];

    return (
        <div className="orders-layout">
            <section className="orders-summary-grid">
                <article className="orders-summary-card">
                    <span className="orders-summary-label">Πληρωμένες Παραγγελίες</span>
                    <strong>{summary.paid_orders_count}</strong>
                </article>

                <article className="orders-summary-card">
                    <span className="orders-summary-label">Συνολικό Ποσό</span>
                    <strong>{formatCurrency(summary.total_revenue)}</strong>
                </article>

                <article className="orders-summary-card">
                    <span className="orders-summary-label">Συνολικά Τεμάχια</span>
                    <strong>{summary.total_items_count}</strong>
                </article>
            </section>

            <section className="orders-panel card-custom mt-4">
                <div className="orders-panel-head">
                    <h3><i className="fas fa-warehouse mr-2"></i>Σύνολα Ανα Προϊόν</h3>
                </div>

                {totalsByProduct.length === 0 ? (
                    <div className="empty-state">
                        <i className="fas fa-box-open"></i>
                        <h3>Δεν υπάρχουν πληρωμένες παραγγελίες</h3>
                        <p>Μόλις ολοκληρωθούν πληρωμές, θα εμφανιστούν τα σύνολα προϊόντων εδώ.</p>
                    </div>
                ) : (
                    <div className="table-responsive">
                        <table className="admin-table">
                            <thead>
                                <tr>
                                    <th>Προϊόν</th>
                                    <th>Συνολική Ποσότητα</th>
                                    <th>Συνολική Αξία</th>
                                </tr>
                            </thead>
                            <tbody>
                                {totalsByProduct.map((row) => (
                                    <tr key={row.product_id}>
                                        <td>{row.product_name}</td>
                                        <td><strong>{row.total_quantity}</strong></td>
                                        <td>{formatCurrency(row.total_value)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>

            <section className="orders-panel card-custom mt-4">
                <div className="orders-panel-head">
                    <h3><i className="fas fa-receipt mr-2"></i>Πληρωμένες Παραγγελίες</h3>
                </div>

                {orders.length === 0 ? (
                    <div className="empty-state">
                        <i className="fas fa-receipt"></i>
                        <h3>Δεν βρέθηκαν παραγγελίες</h3>
                        <p>Δεν υπάρχουν πληρωμένες παραγγελίες αυτή τη στιγμή.</p>
                    </div>
                ) : (
                    <div className="table-responsive">
                        <table className="admin-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Ημερομηνία</th>
                                    <th>Γονέας</th>
                                    <th>Email</th>
                                    <th>Τεμάχια</th>
                                    <th>Σύνολο</th>
                                </tr>
                            </thead>
                            <tbody>
                                {orders.map((order) => (
                                    <tr key={order.order_id}>
                                        <td>#{order.order_id}</td>
                                        <td>{formatDateTime(order.created_at)}</td>
                                        <td>{order.parent_name || '-'}</td>
                                        <td>{order.parent_email || '-'}</td>
                                        <td>{order.total_items}</td>
                                        <td>{formatCurrency(order.total_price)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>
        </div>
    );
}

const root = ReactDOM.createRoot(document.getElementById('orders-root'));
root.render(<AdminOrdersPage />);
