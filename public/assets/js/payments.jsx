function Payments() {
    const eshopContext = window.__ESHOP_CONTEXT__ || {};
    const cartMode = eshopContext.cartMode === 'parent' ? 'parent' : 'public';
    const returnContext = eshopContext.returnContext === 'parent' ? 'parent' : 'public';
    const isAuthenticatedParent = Boolean(eshopContext.isAuthenticatedParent) && cartMode === 'parent';
    const isPublicCheckout = !isAuthenticatedParent;

    const [products, setProducts] = React.useState([]);
    const [cart, setCart] = React.useState([]);
    const [selectedSizes, setSelectedSizes] = React.useState({});
    const [sizeErrors, setSizeErrors] = React.useState({});
    const [cartLoading, setCartLoading] = React.useState(true);
    const [checkoutLoading, setCheckoutLoading] = React.useState(false);
    const [paymentFeedback, setPaymentFeedback] = React.useState(null);
    const [paymentResultStatus, setPaymentResultStatus] = React.useState('');
    const [notice, setNotice] = React.useState({
        open: false,
        title: '',
        message: '',
        variant: 'warning'
    });
    const [checkoutForm, setCheckoutForm] = React.useState({
        customer_name: '',
        customer_surname: '',
        student_name: '',
        student_class: '',
        customer_email: '',
        customer_phone: ''
    });

    const productsUrl = "/parents-council-platform-group5/app/services/ProductFetch.php";
    const cartUrl = eshopContext.cartUrl || "/parents-council-platform-group5/public/cart.php";
    const checkoutUrl = eshopContext.checkoutUrl || "/parents-council-platform-group5/app/services/EshopJCC.php";

    function getPaymentFeedback(status, message) {
        const normalizedStatus = (status || '').toLowerCase();
        const normalizedMessage = (message || '').trim() || 'Η πληρωμή σας ενημερώθηκε.';

        if (normalizedStatus === 'completed') {
            return {
                title: 'Η πληρωμή ολοκληρώθηκε',
                message: normalizedMessage,
                variant: 'success'
            };
        }

        if (normalizedStatus === 'pending') {
            return {
                title: 'Η πληρωμή είναι σε αναμονή',
                message: normalizedMessage,
                variant: 'info'
            };
        }

        if (normalizedStatus === 'refunded') {
            return {
                title: 'Η πληρωμή σημειώθηκε ως επιστροφή',
                message: normalizedMessage,
                variant: 'warning'
            };
        }

        return {
            title: 'Η πληρωμή δεν ολοκληρώθηκε',
            message: normalizedMessage,
            variant: 'error'
        };
    }

    const clearPaymentResultParams = React.useCallback(() => {
        const params = new URLSearchParams(window.location.search);
        params.delete("payment_status");
        params.delete("payment_message");

        const nextSearch = params.toString();
        const nextUrl = `${window.location.pathname}${nextSearch ? `?${nextSearch}` : ''}${window.location.hash || ''}`;
        window.history.replaceState({}, document.title, nextUrl);
    }, []);

    const consumePaymentResult = React.useCallback(() => {
        const params = new URLSearchParams(window.location.search);
        const paymentStatus = params.get("payment_status");
        const paymentMessage = params.get("payment_message");

        if (!paymentStatus || !paymentMessage) {
            return false;
        }

        setPaymentFeedback(getPaymentFeedback(paymentStatus, paymentMessage));
        setPaymentResultStatus((paymentStatus || '').toLowerCase());
        clearPaymentResultParams();
        return true;
    }, [clearPaymentResultParams]);

    const showNotice = React.useCallback((message, options = {}) => {
        setNotice({
            open: true,
            title: options.title || 'Ειδοποίηση',
            message: message || 'Συνέβη ένα απρόσμενο σφάλμα.',
            variant: options.variant || 'warning'
        });
    }, []);

    React.useEffect(() => {
        if (!notice.open) {
            return undefined;
        }

        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        return () => {
            document.body.style.overflow = previousOverflow;
        };
    }, [notice.open]);

    const closeNotice = React.useCallback(() => {
        setNotice({ open: false, title: '', message: '', variant: 'warning' });
    }, []);

    React.useEffect(() => {
        fetch(productsUrl)
            .then((res) => res.json())
            .then((data) => setProducts(Array.isArray(data) ? data : []))
            .catch((err) => console.error("Fetch products error:", err));
    }, []);

    const loadCart = React.useCallback(() => {
        setCartLoading(true);

        fetch(`${cartUrl}?action=get`)
            .then(async (res) => {
                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || "Σφάλμα φόρτωσης καλαθιού.");
                }

                const items = (data.cart && data.cart.items) ? data.cart.items : [];
                setCart(items);
            })
            .catch((err) => {
                console.error("Fetch cart error:", err);
                setCart([]);
            })
            .finally(() => {
                setCartLoading(false);
            });
    }, [cartUrl]);

    React.useEffect(() => {
        loadCart();
    }, [loadCart]);

    React.useEffect(() => {
        consumePaymentResult();
    }, [consumePaymentResult]);

    React.useEffect(() => {
        function handlePageShow() {
            setCheckoutLoading(false);
            loadCart();
            consumePaymentResult();
        }

        window.addEventListener('pageshow', handlePageShow);

        return () => {
            window.removeEventListener('pageshow', handlePageShow);
        };
    }, [consumePaymentResult, loadCart]);

    React.useEffect(() => {
        if (paymentResultStatus !== 'completed' || !isPublicCheckout) {
            return;
        }

        fetch(cartUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"
            },
            body: new URLSearchParams({ action: 'clear' }).toString()
        })
        .then(async (res) => {
            const data = await res.json();
            if (res.ok && data.success && data.cart && Array.isArray(data.cart.items)) {
                setCart(data.cart.items);
            }
        })
        .catch((err) => {
            console.error("Clear public cart after payment error:", err);
        });
    }, [paymentResultStatus, isPublicCheckout, cartUrl]);

    function postCartAction(formData) {
        return fetch(cartUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"
            },
            body: new URLSearchParams(formData).toString()
        })
        .then(async (res) => {
            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.message || "Σφάλμα καλαθιού.");
            }

            const items = (data.cart && data.cart.items) ? data.cart.items : [];
            setCart(items);
            return data;
        });
    }

    function updateSelectedSize(productId, size) {
        setSelectedSizes((prev) => ({
            ...prev,
            [productId]: size
        }));

        if (size) {
            setSizeErrors((prev) => ({
                ...prev,
                [productId]: ''
            }));
        }
    }

    function updateCheckoutField(field, value) {
        setCheckoutForm((prev) => ({
            ...prev,
            [field]: value
        }));
    }

    function addToCart(product) {
        const selectedSize = selectedSizes[product.product_id];

        if (!selectedSize || selectedSize.trim() === '') {
            setSizeErrors((prev) => ({
                ...prev,
                [product.product_id]: 'Πρέπει να επιλέξετε μέγεθος πριν προστεθεί το προϊόν στο καλάθι.'
            }));
            return;
        }

        postCartAction({
            action: 'add',
            product_id: product.product_id,
            quantity: 1,
            size: selectedSize
        })
        .then(() => {
            setSizeErrors((prev) => ({
                ...prev,
                [product.product_id]: ''
            }));
        })
        .catch((err) => {
            console.error("Add to cart error:", err);
            showNotice(err.message || 'Σφάλμα κατά την προσθήκη στο καλάθι.', {
                title: 'Αποτυχία προσθήκης',
                variant: 'error'
            });
        });
    }

    function removeFromCart(productId, size) {
        postCartAction({
            action: 'remove',
            product_id: productId,
            size: size || ''
        })
        .catch((err) => {
            console.error("Remove from cart error:", err);
            showNotice(err.message || 'Σφάλμα κατά την αφαίρεση από το καλάθι.', {
                title: 'Αποτυχία αφαίρεσης',
                variant: 'error'
            });
        });
    }

    function updateQuantity(productId, size, newQuantity) {
        if (newQuantity < 1) return;

        postCartAction({
            action: 'update',
            product_id: productId,
            size: size || '',
            quantity: newQuantity
        })
        .catch((err) => {
            console.error("Update cart error:", err);
            showNotice(err.message || 'Σφάλμα κατά την ενημέρωση ποσότητας.', {
                title: 'Αποτυχία ενημέρωσης',
                variant: 'error'
            });
        });
    }

    function calculateTotal() {
        return cart
            .reduce((total, item) => total + (Number(item.price_at_purchase) * Number(item.quantity)), 0)
            .toFixed(2);
    }

    function calculateItemCount() {
        return cart.reduce((total, item) => total + Number(item.quantity), 0);
    }

    function dismissPaymentFeedback() {
        setPaymentFeedback(null);
    }

    function validatePublicCheckout() {
        const name = (checkoutForm.customer_name || '').trim();
        const surname = (checkoutForm.customer_surname || '').trim();
        const studentName = (checkoutForm.student_name || '').trim();
        const studentClass = (checkoutForm.student_class || '').trim();
        const email = (checkoutForm.customer_email || '').trim();
        const phone = (checkoutForm.customer_phone || '').trim();

        if (!name) {
            return 'Συμπληρώστε το όνομα πελάτη.';
        }

        if (!surname) {
            return 'Συμπληρώστε το επώνυμο πελάτη.';
        }

        if (!studentName) {
            return 'Συμπληρώστε το ονοματεπώνυμο μαθητή/τριας.';
        }

        if (!studentClass) {
            return 'Συμπληρώστε το τμήμα του/της μαθητή/τριας.';
        }

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            return 'Συμπληρώστε έγκυρο email επικοινωνίας.';
        }

        if (!phone) {
            return 'Συμπληρώστε τηλέφωνο επικοινωνίας.';
        }

        return '';
    }

    function handleCheckout() {
        if (cart.length === 0) {
            showNotice('Το καλάθι είναι κενό!', {
                title: 'Δεν υπάρχει παραγγελία',
                variant: 'warning'
            });
            return;
        }

        if (checkoutLoading) {
            return;
        }

        if (isPublicCheckout) {
            const validationMessage = validatePublicCheckout();
            if (validationMessage) {
                showNotice(validationMessage, {
                    title: 'Ελλιπή στοιχεία παραγγελίας',
                    variant: 'warning'
                });
                return;
            }
        }

        setCheckoutLoading(true);

        const payload = {
            action: 'checkout',
            return_context: returnContext
        };

        if (isPublicCheckout) {
            payload.customer_name = checkoutForm.customer_name.trim();
            payload.customer_surname = checkoutForm.customer_surname.trim();
            payload.student_name = checkoutForm.student_name.trim();
            payload.student_class = checkoutForm.student_class.trim();
            payload.customer_email = checkoutForm.customer_email.trim();
            payload.customer_phone = checkoutForm.customer_phone.trim();
        }

        fetch(checkoutUrl, {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: new URLSearchParams(payload).toString()
        })
        .then(async (res) => {
            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Δεν ήταν δυνατή η εκκίνηση της πληρωμής.');
            }

            if (!data.redirect_url) {
                throw new Error('Δεν επιστράφηκε σύνδεσμος πληρωμής από την JCC.');
            }

            window.location.assign(data.redirect_url);
        })
        .catch((err) => {
            console.error("Checkout error:", err);
            setCheckoutLoading(false);
            showNotice(err.message || 'Παρουσιάστηκε σφάλμα κατά τη μετάβαση στην πληρωμή.', {
                title: 'Σφάλμα πληρωμής',
                variant: 'error'
            });
        });
    }

    function renderCheckoutModeCard() {
        if (isAuthenticatedParent) {
            return (
                <div className="eshop-mode-card eshop-mode-card--parent">
                    <div className="eshop-mode-card__icon">
                        <i className="fas fa-user-check"></i>
                    </div>
                    <div className="eshop-mode-card__copy">
                        <strong>Αγορά ως γονέας</strong>
                        <span>Θα χρησιμοποιηθούν τα στοιχεία του λογαριασμού σας για την παραγγελία και την απόδειξη πληρωμής.</span>
                    </div>
                </div>
            );
        }

        return (
            <div className="eshop-mode-card eshop-mode-card--public">
                <div className="eshop-mode-card__icon">
                    <i className="fas fa-user"></i>
                </div>
                <div className="eshop-mode-card__copy">
                    <strong>Αγορά ως επισκέπτης</strong>
                    <span>Μπορείτε να ολοκληρώσετε την παραγγελία σας χωρίς σύνδεση σε λογαριασμό. Πριν από τη μετάβαση στη JCC θα συμπληρώσετε τα στοιχεία επικοινωνίας και τα στοιχεία του/της μαθητή/τριας για την παραγγελία και την απόδειξη πληρωμής.</span>
                </div>
            </div>
        );
    }

    function renderPublicCheckoutForm() {
        if (!isPublicCheckout) {
            return null;
        }

        return (
            <section className="checkout-customer-card">
                <div className="checkout-customer-card__head">
                    <div>
                        <span className="checkout-customer-card__eyebrow">Αγορά ως επισκέπτης</span>
                        <h3>Στοιχεία παραγγελίας</h3>
                    </div>
                    <p>Συμπληρώστε τα στοιχεία επικοινωνίας και τα στοιχεία του/της μαθητή/τριας που θα καταχωριστούν στην παραγγελία και θα χρησιμοποιηθούν για την απόδειξη πληρωμής.</p>
                </div>

                <div className="checkout-customer-grid">
                    <label className="checkout-field">
                        <span>Όνομα</span>
                        <input
                            type="text"
                            value={checkoutForm.customer_name}
                            onChange={(event) => updateCheckoutField('customer_name', event.target.value)}
                            placeholder="π.χ. Μαρία"
                            autoComplete="given-name"
                        />
                    </label>

                    <label className="checkout-field">
                        <span>Επώνυμο</span>
                        <input
                            type="text"
                            value={checkoutForm.customer_surname}
                            onChange={(event) => updateCheckoutField('customer_surname', event.target.value)}
                            placeholder="π.χ. Αντωνίου"
                            autoComplete="family-name"
                        />
                    </label>

                    <label className="checkout-field">
                        <span>Ονοματεπώνυμο μαθητή/τριας</span>
                        <input
                            type="text"
                            value={checkoutForm.student_name}
                            onChange={(event) => updateCheckoutField('student_name', event.target.value)}
                            placeholder="π.χ. Άννα Αντωνίου"
                            autoComplete="off"
                        />
                    </label>

                    <label className="checkout-field">
                        <span>Τμήμα</span>
                        <input
                            type="text"
                            value={checkoutForm.student_class}
                            onChange={(event) => updateCheckoutField('student_class', event.target.value)}
                            placeholder="π.χ. Α2"
                            autoComplete="off"
                        />
                    </label>

                    <label className="checkout-field">
                        <span>Email</span>
                        <input
                            type="email"
                            value={checkoutForm.customer_email}
                            onChange={(event) => updateCheckoutField('customer_email', event.target.value)}
                            placeholder="name@example.com"
                            autoComplete="email"
                        />
                    </label>

                    <label className="checkout-field">
                        <span>Τηλέφωνο</span>
                        <input
                            type="text"
                            value={checkoutForm.customer_phone}
                            onChange={(event) => updateCheckoutField('customer_phone', event.target.value)}
                            placeholder="99XXXXXX"
                            autoComplete="tel"
                        />
                    </label>
                </div>
            </section>
        );
    }

    return (
        <>
            <div className="Page">
                <div className="container md-4">
                    {paymentFeedback && (
                        <div className={`eshop-feedback-banner eshop-feedback-banner--${paymentFeedback.variant}`}>
                            <div className="eshop-feedback-copy">
                                <strong>{paymentFeedback.title}</strong>
                                <span>{paymentFeedback.message}</span>
                            </div>
                            <button
                                type="button"
                                className="eshop-feedback-dismiss"
                                onClick={dismissPaymentFeedback}
                                aria-label="Κλείσιμο μηνύματος πληρωμής"
                            >
                                <i className="fas fa-times"></i>
                            </button>
                        </div>
                    )}

                    {renderCheckoutModeCard()}

                    <div className="row">
                        {products.map((product) => (
                            <div className="col-md-4" key={product.product_id}>
                                <div className="card m-4 mb-4">
                                    <div className="card-body">
                                        <h5 className="product-title">{product.product_name}</h5>
                                        <p className="product-text">{product.product_description}</p>
                                        <p className="product-price">{product.price}€</p>

                                        <div className="carousel">
                                            {product.images && product.images.length > 0 && (
                                                <div id={`carousel-${product.product_id}`} className="carousel slide" data-bs-ride="carousel">
                                                    <div className="carousel-inner">
                                                        {product.images.map((img, idx) => (
                                                            <div key={idx} className={`carousel-item ${idx === 0 ? 'active' : ''}`}>
                                                                <img src={img} className="d-block w-100" alt={product.product_name} />
                                                            </div>
                                                        ))}
                                                    </div>

                                                    {product.images.length > 1 && (
                                                        <>
                                                            <button
                                                                className="carousel-control-prev"
                                                                type="button"
                                                                data-bs-target={`#carousel-${product.product_id}`}
                                                                data-bs-slide="prev"
                                                            >
                                                                <span className="carousel-control-prev-icon"></span>
                                                            </button>

                                                            <button
                                                                className="carousel-control-next"
                                                                type="button"
                                                                data-bs-target={`#carousel-${product.product_id}`}
                                                                data-bs-slide="next"
                                                            >
                                                                <span className="carousel-control-next-icon"></span>
                                                            </button>
                                                        </>
                                                    )}
                                                </div>
                                            )}
                                        </div>

                                        <div className="size-selector mt-3">
                                            <label className="size-label">Επιλέξτε μέγεθος:</label>
                                            <select
                                                className="size-select"
                                                value={selectedSizes[product.product_id] || ''}
                                                onChange={(event) => updateSelectedSize(product.product_id, event.target.value)}
                                            >
                                                <option value="">Επιλέξτε μέγεθος</option>
                                                <option value="x-small">X-Small</option>
                                                <option value="small">Small</option>
                                                <option value="medium">Medium</option>
                                                <option value="large">Large</option>
                                                <option value="x-large">X-Large</option>
                                            </select>

                                            {sizeErrors[product.product_id] && (
                                                <div className="size-error-text">
                                                    {sizeErrors[product.product_id]}
                                                </div>
                                            )}
                                        </div>

                                        <button
                                            className="btn btn-primary mt-3 w-100"
                                            onClick={() => addToCart(product)}
                                        >
                                            <i className="fas fa-shopping-bag mr-2"></i>
                                            Προσθήκη στο καλάθι
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="wholecart m-4 mt-3">
                        <div className="cart-header">
                            <div>
                                <span className="cart-eyebrow">Οι επιλογές σας</span>
                                <h2>Καλάθι</h2>
                            </div>

                            <div className="cart-count-badge">
                                <i className="fas fa-shopping-bag mr-2"></i>
                                {calculateItemCount()} προϊόντα
                            </div>
                        </div>

                        {cartLoading ? (
                            <div className="cart-empty-state">
                                <div className="cart-empty-icon">
                                    <i className="fas fa-spinner fa-spin"></i>
                                </div>
                                <h3>Φόρτωση καλαθιού...</h3>
                            </div>
                        ) : cart.length === 0 ? (
                            <div className="cart-empty-state">
                                <div className="cart-empty-icon">
                                    <i className="fas fa-shopping-basket"></i>
                                </div>
                                <h3>Το καλάθι σας είναι άδειο</h3>
                                <p>Διαλέξτε προϊόντα και θα εμφανιστούν εδώ έτοιμα για ολοκλήρωση αγοράς.</p>
                            </div>
                        ) : (
                            <>
                                {cart.map((item, index) => (
                                    <div className="cartitem mb-3" key={`${item.product_id}-${item.size || 'no-size'}-${index}`}>
                                        <div className="row align-items-center">
                                            <div className="col-lg-4 col-md-4 cart-pro-title">
                                                <div>{item.product_name}</div>
                                                {item.size && (
                                                    <small className="cart-size-badge">
                                                        {item.size.toUpperCase()}
                                                    </small>
                                                )}
                                            </div>

                                            <div className="col-lg-3 col-md-3 cart-pro-text">
                                                {item.product_description}
                                            </div>

                                            <div className="col-lg-3 col-md-3 cart-pro-quantity">
                                                <div className="quantity-controls">
                                                    <button
                                                        className="btn btn-sm btn-outline-light"
                                                        onClick={() => updateQuantity(item.product_id, item.size, Number(item.quantity) - 1)}
                                                    >
                                                        <i className="fas fa-minus"></i>
                                                    </button>

                                                    <input
                                                        type="number"
                                                        className="quantity-input"
                                                        value={item.quantity}
                                                        onChange={(event) => updateQuantity(item.product_id, item.size, parseInt(event.target.value, 10) || 1)}
                                                    />

                                                    <button
                                                        className="btn btn-sm btn-outline-light"
                                                        onClick={() => updateQuantity(item.product_id, item.size, Number(item.quantity) + 1)}
                                                    >
                                                        <i className="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div className="col-lg-2 col-md-2 cart-item-actions">
                                                <div className="cart-pro-price">
                                                    <strong>€{(Number(item.price_at_purchase) * Number(item.quantity)).toFixed(2)}</strong>
                                                </div>

                                                <button
                                                    className="delete btn btn-sm btn-danger"
                                                    onClick={() => removeFromCart(item.product_id, item.size)}
                                                >
                                                    <i className="fas fa-trash-alt mr-1"></i>
                                                    Αφαίρεση
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ))}

                                {renderPublicCheckoutForm()}

                                <div className="cart-footer mt-4">
                                    <div className="cart-total-card">
                                        <span className="cart-total-label">Συνολικό ποσό</span>
                                        <h4>€{calculateTotal()}</h4>
                                        <p className="cart-total-note">
                                            {isPublicCheckout
                                                ? 'Θα μεταφερθείτε στην JCC μόλις επιβεβαιωθούν τα στοιχεία παραγγελίας.'
                                                : 'Οι αλλαγές στην ποσότητα ενημερώνονται άμεσα.'}
                                        </p>
                                    </div>

                                    <button
                                        className="btn btn-success btn-lg checkout-btn"
                                        onClick={handleCheckout}
                                        disabled={checkoutLoading}
                                    >
                                        <i className="fas fa-credit-card mr-2"></i>
                                        {checkoutLoading ? 'Μετάβαση στην JCC...' : 'Ολοκλήρωση Αγοράς'}
                                    </button>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </div>

            {notice.open && (
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="payments-notice-title"
                    onClick={closeNotice}
                    className="payments-notice-overlay"
                >
                    <div
                        role="document"
                        onClick={(event) => event.stopPropagation()}
                        className={`payments-notice-card payments-notice-card--${notice.variant}`}
                    >
                        <h3 id="payments-notice-title" className="payments-notice-title">
                            {notice.title}
                        </h3>
                        <div className="payments-notice-message">
                            {notice.message}
                        </div>
                        <div className="payments-notice-actions">
                            <button
                                type="button"
                                onClick={closeNotice}
                                className={`payments-notice-button payments-notice-button--${notice.variant}`}
                            >
                                Εντάξει
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}

const root = ReactDOM.createRoot(document.getElementById('payments'));
root.render(<Payments />);
