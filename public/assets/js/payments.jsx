function Payments() {
    const [products, setProducts] = React.useState([]);
    const [cart, setCart] = React.useState([]);
    const [selectedSizes, setSelectedSizes] = React.useState({});
    const [sizeErrors, setSizeErrors] = React.useState({});
    const [cartLoading, setCartLoading] = React.useState(true);
    const [checkoutLoading, setCheckoutLoading] = React.useState(false);

    const productsUrl = "/parents-council-platform-group5/app/services/ProductFetch.php";
    const cartUrl = "/parents-council-platform-group5/public/cart.php";
    const checkoutUrl = "/parents-council-platform-group5/app/services/EshopJCC.php";

    React.useEffect(() => {
        fetch(productsUrl)
            .then(res => res.json())
            .then(data => setProducts(data))
            .catch(err => console.error("Fetch products error:", err));
    }, []);

    React.useEffect(() => {
        loadCart();
    }, []);

    React.useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const paymentStatus = params.get("payment_status");
        const paymentMessage = params.get("payment_message");

        if (!paymentStatus || !paymentMessage) {
            return;
        }

        alert(paymentMessage);
        window.history.replaceState({}, document.title, window.location.pathname);
    }, []);

    function loadCart() {
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
            .catch(err => {
                console.error("Fetch cart error:", err);
                setCart([]);
            })
            .finally(() => {
                setCartLoading(false);
            });
    }

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
        setSelectedSizes(prev => ({
            ...prev,
            [productId]: size
        }));

        if (size) {
            setSizeErrors(prev => ({
                ...prev,
                [productId]: ''
            }));
        }
    }

    function addToCart(product) {
        const selectedSize = selectedSizes[product.product_id];

        if (!selectedSize || selectedSize.trim() === '') {
            setSizeErrors(prev => ({
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
            setSizeErrors(prev => ({
                ...prev,
                [product.product_id]: ''
            }));
        })
        .catch(err => {
            console.error("Add to cart error:", err);
            alert(err.message || 'Σφάλμα κατά την προσθήκη στο καλάθι.');
        });
    }

    function removeFromCart(productId, size) {
        postCartAction({
            action: 'remove',
            product_id: productId,
            size: size || ''
        })
        .catch(err => {
            console.error("Remove from cart error:", err);
            alert(err.message || 'Σφάλμα κατά την αφαίρεση από το καλάθι.');
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
        .catch(err => {
            console.error("Update cart error:", err);
            alert(err.message || 'Σφάλμα κατά την ενημέρωση ποσότητας.');
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

    function handleCheckout() {
        if (cart.length === 0) {
            alert('Το καλάθι είναι κενό!');
            return;
        }

        setCheckoutLoading(true);
        window.location.href = `${checkoutUrl}?action=checkout`;
    }

    return (
        <>
            <div className="Page">
                <div className="container md-4">
                    <div className="row">
                        {products.map(product => (
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
                                                onChange={(e) => updateSelectedSize(product.product_id, e.target.value)}
                                            >
                                                <option value="">Επιλέξτε μέγεθος</option>
                                                <option value="x-small">X-Small</option>
                                                <option value="small">Small</option>
                                                <option value="medium">Medium</option>
                                                <option value="large">Large</option>
                                                <option value="x-large">X-Large</option>
                                            </select>

                                            {sizeErrors[product.product_id] && (
                                                <div style={{ color: 'red', marginTop: '8px', fontSize: '14px', fontWeight: '600' }}>
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
                                                        onChange={(e) => updateQuantity(item.product_id, item.size, parseInt(e.target.value, 10) || 1)}
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

                                <div className="cart-footer mt-4">
                                    <div className="cart-total-card">
                                        <span className="cart-total-label">Συνολικό ποσό</span>
                                        <h4>€{calculateTotal()}</h4>
                                        <p className="cart-total-note">Οι αλλαγές στην ποσότητα ενημερώνονται άμεσα.</p>
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
        </>
    );
}

const root = ReactDOM.createRoot(document.getElementById('payments'));
root.render(<Payments />);
