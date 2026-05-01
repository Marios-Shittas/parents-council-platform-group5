// Arxeio: public\assets\js\admin-orders.jsx
// Rolos: Xeirizetai frontend symperifora sto admin panel, opos formaes, modals, filters i React components.
// Simeiosi: Prosoxi: afora agora/paraggelies, ara ta data prepei na menoun synced me cart/orders services.
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

function markOrderAsSeenOnHover(row, updateCallback) {
    const orderId = row.order_id;
    
    const payload = new FormData();
    payload.append('action', 'mark_order_seen');
    payload.append('order_id', orderId);

    fetch(window.appPublicUrl('admin/Orders.php'), {
        method: 'POST',
        credentials: 'include',
        body: payload
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Enimeronei sidebar badge
                if (data.pending_paid_orders_count !== undefined) {
                    updateOrdersNotificationBadge(data.pending_paid_orders_count);
                }
                // Enimeronei row state to mark as seen
                if (updateCallback) {
                    updateCallback(orderId);
                }
            }
        })
        .catch(err => console.error('Error marking order as seen:', err));
}

function updateOrdersNotificationBadge(count) {
    const ordersLink = document.querySelector('a[href="Orders.php"]');
    
    if (ordersLink) {
        const existingBadge = ordersLink.querySelector('.admin-notification-badge');
        if (count > 0) {
            const badgeText = count > 10 ? '10+' : String(count);
            if (existingBadge) {
                existingBadge.textContent = badgeText;
            } else {
                const newBadge = document.createElement('span');
                newBadge.className = 'admin-notification-badge';
                newBadge.setAttribute('aria-label', `Νέες πληρωμένες παραγγελίες: ${badgeText}`);
                newBadge.textContent = badgeText;
                ordersLink.appendChild(newBadge);
            }
        } else {
            if (existingBadge) {
                existingBadge.remove();
            }
        }
    }
}

function AdminOrdersPage() {
    const [payload, setPayload] = React.useState(null);
    const [loading, setLoading] = React.useState(true);
    const [error, setError] = React.useState('');
    const [feedback, setFeedback] = React.useState(null);
    const [clearLoading, setClearLoading] = React.useState(false);
    const [clearConfirmStep, setClearConfirmStep] = React.useState(0);
    const [clearTarget, setClearTarget] = React.useState(null);
    const [expandedOrderId, setExpandedOrderId] = React.useState(null);

    const handleOrderSeen = React.useCallback((orderId) => {
        setPayload(prevPayload => {
            if (!prevPayload) return prevPayload;
            
            return {
                ...prevPayload,
                orders: prevPayload.orders.map(order =>
                    order.order_id === orderId
                        ? { ...order, is_unseen: false }
                        : order
                )
            };
        });
    }, []);

    const loadOrders = React.useCallback(() => {
        const endpoint = window.appProjectUrl('app/api/get-orders.php');

        setLoading(true);

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

    React.useEffect(() => {
        loadOrders();
    }, [loadOrders]);

    function openClearConfirmation(target = { type: 'all' }) {
        if (clearLoading) {
            return;
        }

        setFeedback(null);
        setClearTarget(target);
        setClearConfirmStep(1);
    }

    function closeClearConfirmation() {
        if (clearLoading) {
            return;
        }

        setClearConfirmStep(0);
        setClearTarget(null);
    }

    function clearOrderHistory() {
        if (clearLoading) {
            return;
        }

        const isSingleOrder = clearTarget && clearTarget.type === 'order' && clearTarget.order;
        const formData = new FormData();
        formData.append('action', isSingleOrder ? 'delete_product_order_history' : 'clear_product_order_history');

        if (isSingleOrder) {
            formData.append('order_id', clearTarget.order.order_id);
        }

        setClearLoading(true);
        setFeedback(null);

        fetch(window.appPublicUrl('admin/Orders.php'), {
            method: 'POST',
            credentials: 'include',
            body: formData
        })
            .then(async (response) => {
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Αποτυχία καθαρισμού ιστορικού.');
                }

                if (data.pending_paid_orders_count !== undefined) {
                    updateOrdersNotificationBadge(data.pending_paid_orders_count);
                }

                const stats = data.stats || {};
                const feedbackMessage = isSingleOrder
                    ? `Διαγράφηκε η παραγγελία #${stats.order_id || clearTarget.order.order_id} και ${stats.product_payments_deleted || 0} συνδεδεμένη πληρωμή e-shop.`
                    : `Καθαρίστηκαν ${stats.orders_deleted || 0} παραγγελίες και ${stats.product_payments_deleted || 0} πληρωμές e-shop.`;

                setFeedback({
                    type: 'success',
                    message: feedbackMessage
                });
                setExpandedOrderId(null);
                setClearConfirmStep(0);
                setClearTarget(null);
                loadOrders();
            })
            .catch((err) => {
                console.error(err);
                setClearConfirmStep(0);
                setClearTarget(null);
                setFeedback({
                    type: 'danger',
                    message: err.message || 'Παρουσιάστηκε σφάλμα κατά τον καθαρισμό.'
                });
            })
            .finally(() => setClearLoading(false));
    }

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
    const isSingleOrderClear = clearTarget && clearTarget.type === 'order' && clearTarget.order;
    const clearTargetOrder = isSingleOrderClear ? clearTarget.order : null;

    return (
        <div className="orders-layout">
            {feedback && (
                <div className={`alert alert-${feedback.type} orders-feedback`} role="alert">
                    {feedback.message}
                </div>
            )}

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
                    <button
                        type="button"
                        className="btn btn-outline-danger orders-clear-btn"
                        onClick={() => openClearConfirmation({ type: 'all' })}
                        disabled={clearLoading}
                    >
                        <i className={`fas ${clearLoading ? 'fa-spinner fa-spin' : 'fa-trash-alt'} mr-1`}></i>
                        {clearLoading ? 'Καθαρισμός...' : 'Καθαρισμός Ιστορικού'}
                    </button>
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
                                    <th></th>
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
                                    <React.Fragment key={order.order_id}>
                                        <tr 
                                            onMouseEnter={() => markOrderAsSeenOnHover(order, handleOrderSeen)}
                                            onClick={() => setExpandedOrderId(expandedOrderId === order.order_id ? null : order.order_id)}
                                            style={{ cursor: 'pointer' }}
                                        >
                                            <td style={{ textAlign: 'center' }}>
                                                <i className={`fas fa-chevron-${expandedOrderId === order.order_id ? 'down' : 'right'}`}></i>
                                            </td>
                                            <td>
                                                #{paraggelia.order_id}
                                                {order.is_unseen && (
                                                    <span className="order-new-badge" style={{marginLeft: '8px', fontSize: '11px', backgroundColor: '#dc3545', color: 'white', padding: '2px 6px', borderRadius: '3px', fontWeight: 'bold'}}>
                                                        ΝΕΑ
                                                    </span>
                                                )}
                                            </td>
                                            <td>{formatDateTime(order.created_at)}</td>
                                            <td>{order.parent_name || '-'}</td>
                                            <td>{order.parent_email || '-'}</td>
                                            <td>{order.total_items}</td>
                                            <td>{formatCurrency(order.total_price)}</td>
                                        </tr>
                                        {expandedOrderId === order.order_id && (
                                            <tr>
                                                <td colSpan="7" style={{ padding: '20px', backgroundColor: '#f8f9fa' }}>
                                                    <div style={{ marginBottom: '15px' }}>
                                                        <h5 style={{ marginBottom: '12px', fontWeight: 'bold' }}>
                                                            <i className="fas fa-box mr-2"></i>Λεπτομέρειες Παραγγελίας
                                                        </h5>
                                                        <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: '14px' }}>
                                                            <thead>
                                                                <tr style={{ borderBottom: '2px solid #dee2e6', backgroundColor: '#e9ecef' }}>
                                                                    <th style={{ padding: '10px', textAlign: 'left', fontWeight: 'bold' }}>Προϊόν</th>
                                                                    <th style={{ padding: '10px', textAlign: 'center', fontWeight: 'bold' }}>Τεμάχια</th>
                                                                    <th style={{ padding: '10px', textAlign: 'center', fontWeight: 'bold' }}>Μέγεθος</th>
                                                                    <th style={{ padding: '10px', textAlign: 'right', fontWeight: 'bold' }}>Τιμή μονάδας</th>
                                                                    <th style={{ padding: '10px', textAlign: 'right', fontWeight: 'bold' }}>Σύνολο</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                {order.items.map((item, idx) => (
                                                                    <tr key={idx} style={{ borderBottom: '1px solid #dee2e6' }}>
                                                                        <td style={{ padding: '10px' }}>{item.product_name}</td>
                                                                        <td style={{ padding: '10px', textAlign: 'center' }}>{item.quantity}</td>
                                                                        <td style={{ padding: '10px', textAlign: 'center' }}>
                                                                            {item.size ? (
                                                                                <span style={{ backgroundColor: '#e7f3ff', padding: '3px 8px', borderRadius: '3px', fontWeight: '500' }}>
                                                                                    {item.size}
                                                                                </span>
                                                                            ) : (
                                                                                <span style={{ color: '#999' }}>-</span>
                                                                            )}
                                                                        </td>
                                                                        <td style={{ padding: '10px', textAlign: 'right' }}>{formatCurrency(item.price_at_purchase)}</td>
                                                                        <td style={{ padding: '10px', textAlign: 'right', fontWeight: 'bold' }}>{formatCurrency(item.line_total)}</td>
                                                                    </tr>
                                                                ))}
                                                            </tbody>
                                                        </table>

                                                        <div className="order-detail-actions">
                                                            <button
                                                                type="button"
                                                                className="btn btn-outline-danger orders-row-delete-btn"
                                                                onClick={(event) => {
                                                                    event.stopPropagation();
                                                                    openClearConfirmation({ type: 'order', order });
                                                                }}
                                                                disabled={clearLoading}
                                                            >
                                                                <i className="fas fa-trash-alt mr-1"></i>
                                                                Διαγραφή αυτής της παραγγελίας
                                                            </button>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        )}
                                    </React.Fragment>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>

            {clearConfirmStep > 0 && (
                <div
                    className="orders-confirm-overlay"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="orders-clear-confirm-title"
                    onClick={closeClearConfirmation}
                >
                    <div className="orders-confirm-modal" role="document" onClick={(event) => event.stopPropagation()}>
                        <div className="orders-confirm-header">
                            <div>
                                <span className="orders-confirm-eyebrow">
                                    {isSingleOrderClear ? `Παραγγελία #${clearTargetOrder.order_id}` : 'Καθαρισμός e-shop'}
                                </span>
                                <h3 id="orders-clear-confirm-title">
                                    {clearConfirmStep === 1
                                        ? (isSingleOrderClear ? 'Έλεγχος πριν τη διαγραφή' : 'Έλεγχος πριν τον καθαρισμό')
                                        : (isSingleOrderClear ? 'Τελική επιβεβαίωση διαγραφής' : 'Τελική επιβεβαίωση')}
                                </h3>
                            </div>
                            <button
                                type="button"
                                className="orders-confirm-close"
                                onClick={closeClearConfirmation}
                                aria-label="Κλείσιμο επιβεβαίωσης"
                                disabled={clearLoading}
                            >
                                <i className="fas fa-times"></i>
                            </button>
                        </div>

                        <div className="orders-confirm-progress" aria-label={`Βήμα ${clearConfirmStep} από 2`}>
                            <span className={`orders-confirm-progress-dot ${clearConfirmStep >= 1 ? 'is-active' : ''}`}>1</span>
                            <span className="orders-confirm-progress-line"></span>
                            <span className={`orders-confirm-progress-dot ${clearConfirmStep >= 2 ? 'is-active' : ''}`}>2</span>
                        </div>

                        <div className="orders-confirm-hero">
                            <div className="orders-confirm-icon">
                                <i className={clearConfirmStep === 1 ? 'fas fa-clipboard-check' : 'fas fa-exclamation-triangle'}></i>
                            </div>
                            <div>
                                <strong>{clearConfirmStep === 1 ? 'Πρώτη επιβεβαίωση' : 'Δεύτερη και τελευταία επιβεβαίωση'}</strong>
                                <p>
                                    {clearConfirmStep === 1
                                        ? (isSingleOrderClear
                                            ? `Ελέγξτε τα στοιχεία της παραγγελίας #${clearTargetOrder.order_id} και τι θα χαθεί από το ιστορικό του γονέα.`
                                            : 'Δείτε τι θα καθαριστεί και τι θα μείνει άθικτο.')
                                        : 'Η ενέργεια θα εκτελεστεί αμέσως και δεν μπορεί να αναιρεθεί.'}
                                </p>
                            </div>
                        </div>

                        {isSingleOrderClear && (
                            <div className="orders-confirm-order-summary">
                                <div>
                                    <span>Order ID</span>
                                    <strong>#{clearTargetOrder.order_id}</strong>
                                </div>
                                <div>
                                    <span>Γονέας</span>
                                    <strong>{clearTargetOrder.parent_name || '-'}</strong>
                                    <small>{clearTargetOrder.parent_email || '-'}</small>
                                </div>
                                <div>
                                    <span>Ημερομηνία</span>
                                    <strong>{formatDateTime(clearTargetOrder.created_at)}</strong>
                                </div>
                                <div>
                                    <span>Ποσό</span>
                                    <strong>{formatCurrency(clearTargetOrder.total_price)}</strong>
                                </div>
                                <div>
                                    <span>Τεμάχια</span>
                                    <strong>{clearTargetOrder.total_items}</strong>
                                </div>
                            </div>
                        )}

                        <div className="orders-confirm-impact-grid">
                            <div className="orders-confirm-impact-card orders-confirm-impact-card--danger">
                                <span className="orders-confirm-impact-icon">
                                    <i className="fas fa-trash-alt"></i>
                                </span>
                                <div>
                                    <strong>{isSingleOrderClear ? 'Θα διαγραφούν' : 'Θα καθαριστούν'}</strong>
                                    <ul>
                                        {isSingleOrderClear ? (
                                            <>
                                                <li>Η παραγγελία #{clearTargetOrder.order_id} θα φύγει από τη λίστα Orders του admin.</li>
                                                <li>Θα διαγραφούν τα προϊόντα, μεγέθη και ποσότητες που ανήκουν μόνο σε αυτή την παραγγελία ({clearTargetOrder.total_items || 0} τεμάχια).</li>
                                                <li>Θα αφαιρεθεί από το profile.php του γονέα η αντίστοιχη πληρωμή προϊόντος και το ποσό της, αν βρεθεί συνδεδεμένη εγγραφή πληρωμής.</li>
                                            </>
                                        ) : (
                                            <>
                                                <li>Πληρωμένες παραγγελίες e-shop από το ιστορικό Orders</li>
                                                <li>Πληρωμές προϊόντων που εμφανίζονται ως αγορές στο profile.php</li>
                                                <li>Αναλυτικές γραμμές, μεγέθη και ποσότητες προϊόντων μέσα στις πληρωμές</li>
                                            </>
                                        )}
                                    </ul>
                                </div>
                            </div>

                            <div className="orders-confirm-impact-card orders-confirm-impact-card--safe">
                                <span className="orders-confirm-impact-icon">
                                    <i className="fas fa-shield-alt"></i>
                                </span>
                                <div>
                                    <strong>Δεν θα πειραχτούν</strong>
                                    <ul>
                                        {isSingleOrderClear ? (
                                            <>
                                                <li>Δεν διαγράφεται ο λογαριασμός του γονέα ή τα προσωπικά του στοιχεία.</li>
                                                <li>Δεν διαγράφονται προϊόντα από το e-shop ούτε οι εικόνες τους.</li>
                                                <li>Δεν επηρεάζονται άλλες παραγγελίες, συνδρομές, ασφάλειες ή πληρωμές άλλου τύπου.</li>
                                            </>
                                        ) : (
                                            <>
                                                <li>Γονείς και λογαριασμοί</li>
                                                <li>Προϊόντα και εικόνες προϊόντων</li>
                                                <li>Συνδρομές, ασφάλειες και πληρωμές άλλου τύπου</li>
                                            </>
                                        )}
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div className="orders-confirm-note">
                            <i className="fas fa-info-circle"></i>
                            <span>
                                {isSingleOrderClear
                                    ? 'Μετά τη διαγραφή, ο parent δεν θα βλέπει πλέον αυτή την αγορά και το ποσό της στο profile.php. Τα πραγματικά προϊόντα στο e-shop μένουν διαθέσιμα.'
                                    : 'Μετά τον καθαρισμό, προϊόντα που μπλοκάρονταν μόνο από παλιό ιστορικό e-shop θα μπορούν να διαγραφούν.'}
                            </span>
                        </div>

                        <div className="orders-confirm-actions">
                            {clearConfirmStep === 1 ? (
                                <>
                                    <button
                                        type="button"
                                        className="btn btn-secondary"
                                        onClick={closeClearConfirmation}
                                    >
                                        Ακύρωση
                                    </button>
                                    <button
                                        type="button"
                                        className="btn orders-confirm-next-btn"
                                        onClick={() => setClearConfirmStep(2)}
                                    >
                                        <i className="fas fa-arrow-right mr-1"></i>
                                        Συνέχεια στη 2η επιβεβαίωση
                                    </button>
                                </>
                            ) : (
                                <>
                                    <button
                                        type="button"
                                        className="btn btn-secondary"
                                        onClick={() => setClearConfirmStep(1)}
                                        disabled={clearLoading}
                                    >
                                        Πίσω
                                    </button>
                                    <button
                                        type="button"
                                        className="btn orders-confirm-danger-btn"
                                        onClick={clearOrderHistory}
                                        disabled={clearLoading}
                                    >
                                        <i className={`fas ${clearLoading ? 'fa-spinner fa-spin' : 'fa-trash-alt'} mr-1`}></i>
                                        {clearLoading
                                            ? (isSingleOrderClear ? 'Γίνεται διαγραφή...' : 'Γίνεται καθαρισμός...')
                                            : (isSingleOrderClear ? 'Ναι, διέγραψε οριστικά' : 'Ναι, καθάρισε οριστικά')}
                                    </button>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

const root = ReactDOM.createRoot(document.getElementById('orders-root'));
root.render(<AdminOrdersPage />);
