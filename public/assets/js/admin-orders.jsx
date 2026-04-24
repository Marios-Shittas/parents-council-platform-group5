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

    fetch('/parents-council-platform-group5/public/admin/Orders.php', {
        method: 'POST',
        credentials: 'include',
        body: payload
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update sidebar badge
                if (data.pending_paid_orders_count !== undefined) {
                    updateOrdersNotificationBadge(data.pending_paid_orders_count);
                }
                // Update row state to mark as seen
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

    React.useEffect(() => {
        const endpoint = '/parents-council-platform-group5/app/api/get-orders.php';

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
                                                #{order.order_id}
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
        </div>
    );
}

const root = ReactDOM.createRoot(document.getElementById('orders-root'));
root.render(<AdminOrdersPage />);
