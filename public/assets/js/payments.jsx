function Payments() {
    const [products, setProducts] = React.useState([]);
    const [cart, setCart] = React.useState([]);

    // Fetch products from PHP API
    React.useEffect(() => {
        fetch("/parents-council-platform-group5/app/services/PaymentsService.php")
            .then(res => res.json())
            .then(data => setProducts(data))
            .catch(err => console.error("Fetch error:", err));
    }, []);

    // Add product to cart
    function addToCart(product) {
        const existingIndex = cart.findIndex(item => item.product_id === product.product_id);
        if (existingIndex >= 0) {
            const newCart = [...cart];
            newCart[existingIndex].quantity += 1;
            setCart(newCart);
        } else {
            setCart([...cart, {...product, quantity: 1}]);
        }
    }

    // Remove product from cart
    function removeFromCart(index) {
        const newCart = [...cart];
        newCart.splice(index, 1);
        setCart(newCart);
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
                                    <p><strong>{product.price}€</strong></p>
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
                                    <button className="btn btn-primary mt-3" onClick={() => addToCart(product)}>
                                        Προσθήκη στο καλάθι
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
                
            <div className="wholecart m-4 mt-3">
                    <h2>Καλάθι</h2>
                    {cart.length === 0 && <p>Το καλάθι είναι κενό.</p>}
                    {cart.map((item, index) => (
                        <div className="cartitem mb-3" key={index}>
                            <div className="row align-items-center">
                                
                                <div className="col-md-3 cart-pro-title">
                                    {item.product_name} x{item.quantity}
                                </div>

                                <div className="col-md-4 cart-pro-text px-3">
                                    {item.product_description}
                                </div>

                                <div className="col-md-2 cart-pro-price">
                                    <strong>{item.price * item.quantity}€</strong>
                                </div>

                                <div className="col-md-3 d-flex justify-content-center justify-content-md-start">
                                    <button className="delete btn btn-danger" onClick={() => removeFromCart(index)}>
                                        Διαγραφή
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </div>
        </>
    );
}

// Mount React component
const root = ReactDOM.createRoot(document.getElementById('payments'));
root.render(<Payments />);
