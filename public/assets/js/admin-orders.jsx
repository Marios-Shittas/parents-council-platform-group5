const ORDERS_ENDPOINT = '/parents-council-platform-group5/app/services/OrdersService.php';

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

function formatCustomerType(customerType) {
    return customerType === 'public' ? 'Επισκέπτης' : 'Γονέας';
}

function formatPortalContext(portalContext) {
    return portalContext === 'public' ? 'Δημόσια προβολή' : 'Γονική προβολή';
}

function formatSize(size) {
    const normalized = String(size || '').trim();
    if (!normalized) {
        return '';
    }

    return normalized.charAt(0).toUpperCase() + normalized.slice(1);
}

function updateOrdersNotificationBadge(count) {
    const ordersLink = document.querySelector('a[href="Orders.php"]');
    if (!ordersLink) {
        return;
    }

    const existingBadge = ordersLink.querySelector('.admin-notification-badge');
    if (count > 0) {
        const badgeText = count > 10 ? '10+' : String(count);
        if (existingBadge) {
            existingBadge.textContent = badgeText;
            existingBadge.setAttribute('aria-label', `Νέες πληρωμένες παραγγελίες: ${badgeText}`);
            return;
        }

        const newBadge = document.createElement('span');
        newBadge.className = 'admin-notification-badge';
        newBadge.setAttribute('aria-label', `Νέες πληρωμένες παραγγελίες: ${badgeText}`);
        newBadge.textContent = badgeText;
        ordersLink.appendChild(newBadge);
        return;
    }

    if (existingBadge) {
        existingBadge.remove();
    }
}

function postOrderAction(action, extraFields = {}) {
    const body = new FormData();
    body.append('action', action);

    Object.entries(extraFields).forEach(([key, value]) => {
        body.append(key, value);
    });

    return fetch(ORDERS_ENDPOINT, {
        method: 'POST',
        credentials: 'include',
        body
    }).then(async (response) => {
        const text = await response.text();
        const data = text ? JSON.parse(text) : {};
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Αποτυχία ενημέρωσης παραγγελίας.');
        }
        return data;
    });
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
    const seenRequests = React.useRef(new Set());

    const markOrderSeenLocally = React.useCallback((orderId) => {
        setPayload((prevPayload) => {
            if (!prevPayload) {
                return prevPayload;
            }

            return {
                ...prevPayload,
                orders: prevPayload.orders.map((order) => (
                    order.order_id === orderId ? { ...order, is_unseen: false } : order
                ))
            };
        });
    }, []);

    const markOrderSeen = React.useCallback((order) => {
        if (!order || !order.is_unseen || seenRequests.current.has(order.order_id)) {
            return;
        }

        seenRequests.current.add(order.order_id);
        markOrderSeenLocally(order.order_id);

        postOrderAction('mark_order_seen', { order_id: order.order_id })
            .then((data) => {
                updateOrdersNotificationBadge(Number(data.pending_paid_orders_count || 0));
            })
            .catch((err) => {
                seenRequests.current.delete(order.order_id);
                console.error('Error marking order as seen:', err);
            });
    }, [markOrderSeenLocally]);

    const loadOrders = React.useCallback(() => {
        setLoading(true);

        fetch(ORDERS_ENDPOINT, {
            credentials: 'include'
        })
            .then(async (response) => {
                const text = await response.text();
                const data = text ? JSON.parse(text) : {};

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Αποτυχία φόρτωσης πληρωμένων παραγγελιών.');
                }

                setPayload(data);
                updateOrdersNotificationBadge(Number(data.pending_paid_orders_count || 0));
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
        const action = isSingleOrder ? 'delete_product_order_history' : 'clear_product_order_history';
        const extraFields = isSingleOrder ? { order_id: clearTarget.order.order_id } : {};

        setClearLoading(true);
        setFeedback(null);

        postOrderAction(action, extraFields)
            .then((data) => {
                const stats = data.stats || {};
                const feedbackMessage = isSingleOrder
                    ? `Διαγράφηκε η παραγγελία #${stats.order_id || clearTarget.order.order_id} και ${stats.product_payments_deleted || 0} συνδεδεμένη πληρωμή e-shop.`
                    : `Καθαρίστηκαν ${stats.orders_deleted || 0} παραγγελίες και ${stats.product_payments_deleted || 0} πληρωμές e-shop.`;

                updateOrdersNotificationBadge(Number(data.pending_paid_orders_count || 0));
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
                    <h3><i className="fas fa-warehouse mr-2"></i>Σύνολα Ανά Προϊόν</h3>
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
                        <table className="admin-table orders-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Order ID</th>
                                    <th>Ημερομηνία</th>
                                    <th>Πελάτης</th>
                                    <th>Μαθητής/τρια</th>
                                    <th>Τμήμα</th>
                                    <th>Email</th>
                                    <th>Τηλέφωνο</th>
                                    <th>Τύπος</th>
                                    <th>View</th>
                                    <th>Τεμάχια</th>
                                    <th>Σύνολο</th>
                                </tr>
                            </thead>
                            <tbody>
                                {orders.map((order) => {
                                    const isExpanded = expandedOrderId === order.order_id;
                                    const customerName = order.customer_name || order.parent_name || '-';
                                    const customerEmail = order.customer_email || order.parent_email || '-';
                                    const orderItems = order.items || [];

                                    return (
                                        <React.Fragment key={order.order_id}>
                                            <tr
                                                className={order.is_unseen ? 'order-row-unseen' : ''}
                                                onMouseEnter={() => markOrderSeen(order)}
                                                onClick={() => {
                                                    setExpandedOrderId(isExpanded ? null : order.order_id);
                                                    markOrderSeen(order);
                                                }}
                                                tabIndex="0"
                                                onKeyDown={(event) => {
                                                    if (event.key === 'Enter' || event.key === ' ') {
                                                        event.preventDefault();
                                                        setExpandedOrderId(isExpanded ? null : order.order_id);
                                                        markOrderSeen(order);
                                                    }
                                                }}
                                            >
                                                <td className="orders-expand-cell">
                                                    <i className={`fas fa-chevron-${isExpanded ? 'down' : 'right'}`}></i>
                                                </td>
                                                <td>
                                                    <span className="orders-id">#{order.order_id}</span>
                                                    {order.is_unseen && (
                                                        <span className="order-new-badge" aria-label="Νέα παραγγελία">1</span>
                                                    )}
                                                </td>
                                                <td>{formatDateTime(order.created_at)}</td>
                                                <td>{customerName}</td>
                                                <td>{order.student_name || '-'}</td>
                                                <td>{order.student_class || '-'}</td>
                                                <td>{customerEmail}</td>
                                                <td>{order.customer_phone || '-'}</td>
                                                <td>{formatCustomerType(order.customer_type)}</td>
                                                <td>{formatPortalContext(order.portal_context)}</td>
                                                <td>{order.total_items}</td>
                                                <td>{formatCurrency(order.total_price)}</td>
                                            </tr>
                                            {isExpanded && (
                                                <tr className="order-details-row">
                                                    <td colSpan="12">
                                                        <div className="order-details-panel">
                                                            <div className="order-details-head">
                                                                <h4><i className="fas fa-box-open mr-2"></i>Λεπτομέρειες Παραγγελίας</h4>
                                                                <strong>{formatCurrency(order.total_price)}</strong>
                                                            </div>

                                                            {orderItems.length === 0 ? (
                                                                <div className="order-details-empty">Δεν υπάρχουν προϊόντα για αυτή την παραγγελία.</div>
                                                            ) : (
                                                                <table className="order-items-table">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Ρούχο / Προϊόν</th>
                                                                            <th>Μέγεθος</th>
                                                                            <th>Ποσότητα</th>
                                                                            <th>Τιμή μονάδας</th>
                                                                            <th>Σύνολο</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        {orderItems.map((item, index) => (
                                                                            <tr key={`${item.product_id}-${item.size || 'no-size'}-${index}`}>
                                                                                <td>{item.product_name}</td>
                                                                                <td>
                                                                                    {item.size ? (
                                                                                        <span className="order-size-text">{item.size_label || formatSize(item.size)}</span>
                                                                                    ) : (
                                                                                        <span className="text-muted">-</span>
                                                                                    )}
                                                                                </td>
                                                                                <td>{item.quantity}</td>
                                                                                <td>{formatCurrency(item.price_at_purchase)}</td>
                                                                                <td><strong>{formatCurrency(item.line_total)}</strong></td>
                                                                            </tr>
                                                                        ))}
                                                                    </tbody>
                                                                </table>
                                                            )}

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
                                    );
                                })}
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
                                            ? `Ελέγξτε τα στοιχεία της παραγγελίας #${clearTargetOrder.order_id} και τι θα χαθεί από το ιστορικό.`
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
                                    <span>Πελάτης</span>
                                    <strong>{clearTargetOrder.customer_name || clearTargetOrder.parent_name || '-'}</strong>
                                    <small>{clearTargetOrder.customer_email || clearTargetOrder.parent_email || '-'}</small>
                                </div>
                                <div>
                                    <span>Μαθητής/τρια</span>
                                    <strong>{clearTargetOrder.student_name || '-'}</strong>
                                    <small>{clearTargetOrder.student_class || '-'}</small>
                                </div>
                                <div>
                                    <span>Ημερομηνία</span>
                                    <strong>{formatDateTime(clearTargetOrder.created_at)}</strong>
                                </div>
                                <div>
                                    <span>Ποσό</span>
                                    <strong>{formatCurrency(clearTargetOrder.total_price)}</strong>
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
                                                <li>Θα αφαιρεθεί η αντίστοιχη πληρωμή προϊόντος και το ποσό της, αν βρεθεί συνδεδεμένη εγγραφή πληρωμής.</li>
                                            </>
                                        ) : (
                                            <>
                                                <li>Πληρωμένες παραγγελίες e-shop από το ιστορικό Orders</li>
                                                <li>Πληρωμές προϊόντων που εμφανίζονται ως αγορές</li>
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
                                                <li>Δεν διαγράφεται ο λογαριασμός γονέα ή τα στοιχεία άλλων πελατών.</li>
                                                <li>Δεν διαγράφονται προϊόντα από το e-shop ούτε οι εικόνες τους.</li>
                                                <li>Δεν επηρεάζονται άλλες παραγγελίες, συνδρομές, ασφάλειες ή πληρωμές άλλου τύπου.</li>
                                            </>
                                        ) : (
                                            <>
                                                <li>Γονείς, επισκέπτες και λογαριασμοί</li>
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
                                    ? 'Μετά τη διαγραφή, αυτή η αγορά και το ποσό της δεν θα εμφανίζονται πλέον ως ιστορικό e-shop. Τα πραγματικά προϊόντα στο e-shop μένουν διαθέσιμα.'
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
