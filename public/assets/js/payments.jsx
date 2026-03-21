function Payments() {
    const [products, setProducts] = React.useState([]);
    const [cart, setCart] = React.useState([]);
    const [selectedSizes, setSelectedSizes] = React.useState({});

    // Fetch products from PHP API
    React.useEffect(() => {
        fetch("/parents-council-platform-group5/app/services/ProductFetch.php")
            .then(res => res.json())
            .then(data => setProducts(data))
            .catch(err => console.error("Fetch error:", err));
    }, []);

    // Update selected size for a product
    function updateSelectedSize(productId, size) {
        setSelectedSizes({...selectedSizes, [productId]: size});
    }

    // Add product to cart
    function addToCart(product) {
        const selectedSize = selectedSizes[product.product_id];
        const existingIndex = cart.findIndex(item => 
            item.product_id === product.product_id && (item.size || 'one-size') === (selectedSize || 'one-size')
        );
        
        if (existingIndex >= 0) {
            const newCart = [...cart];
            newCart[existingIndex].quantity += 1;
            setCart(newCart);
        } else {
            setCart([...cart, {...product, quantity: 1, size: selectedSize}]);
        }
    }

    // Remove product from cart
    function removeFromCart(index) {
        const newCart = [...cart];
        newCart.splice(index, 1);
        setCart(newCart);
    }

    // Update quantity
    function updateQuantity(index, newQuantity) {
        if (newQuantity < 1) return;
        const newCart = [...cart];
        newCart[index].quantity = newQuantity;
        setCart(newCart);
    }

    // Calculate total
    function calculateTotal() {
        return cart.reduce((total, item) => total + (item.price * item.quantity), 0).toFixed(2);
    }

    // Calculate total quantity in cart
    function calculateItemCount() {
        return cart.reduce((total, item) => total + item.quantity, 0);
    }

    // Handle checkout
    function handleCheckout() {
        if (cart.length === 0) {
            alert('Το καλάθι είναι κενό!');
            return;
        }
        alert('Ευχαριστούμε για την αγορά σας!\nΣύνολο: €' + calculateTotal());
        setCart([]);
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
                                                    <button className="carousel-control-prev" type="button" data-bs-target={`#carousel-${product.product_id}`} data-bs-slide="prev">
                                                    <span className="carousel-control-prev-icon"></span>
                                                    </button>
                                                    <button className="carousel-control-next" type="button" data-bs-target={`#carousel-${product.product_id}`} data-bs-slide="next">
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
                                    </div>
                                    
                                    <button className="btn btn-primary mt-3 w-100" onClick={() => addToCart(product)}>
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

                    {cart.length === 0 ? (
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
                                <div className="cartitem mb-3" key={index}>
                                    <div className="row align-items-center">
                                        <div className="col-lg-4 col-md-4 cart-pro-title">
                                            <div>{item.product_name}</div>
                                            {item.size && <small className="cart-size-badge">{item.size.toUpperCase()}</small>}
                                        </div>

                                        <div className="col-lg-3 col-md-3 cart-pro-text">
                                            {item.product_description}
                                        </div>

                                        <div className="col-lg-3 col-md-3 cart-pro-quantity">
                                            <div className="quantity-controls">
                                                <button className="btn btn-sm btn-outline-light" onClick={() => updateQuantity(index, item.quantity - 1)}>
                                                    <i className="fas fa-minus"></i>
                                                </button>
                                                <input type="number" className="quantity-input" value={item.quantity} onChange={(e) => updateQuantity(index, parseInt(e.target.value, 10) || 1)} />
                                                <button className="btn btn-sm btn-outline-light" onClick={() => updateQuantity(index, item.quantity + 1)}>
                                                    <i className="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div className="col-lg-2 col-md-2 cart-item-actions">
                                            <div className="cart-pro-price">
                                                <strong>€{(item.price * item.quantity).toFixed(2)}</strong>
                                            </div>
                                            <button className="delete btn btn-sm btn-danger" onClick={() => removeFromCart(index)}>
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
                                <button className="btn btn-success btn-lg checkout-btn" onClick={handleCheckout}>
                                    <i className="fas fa-credit-card mr-2"></i>
                                    Ολοκλήρωση Αγοράς
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

// Mount React component
const root = ReactDOM.createRoot(document.getElementById('payments'));
root.render(<Payments />);
