<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookNest | Books Delivered Across Sri Lanka</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- ================= NAVBAR ================= -->
<nav class="navbar navbar-expand-lg sticky-top shop-nav">
    <div class="container">
        <a class="navbar-brand" href="#home"><i class="bi bi-book-half"></i> BookNest</a>
        <div class="d-flex align-items-center gap-2 order-lg-last">
            <a href="admin.php" class="admin-link" title="Admin panel"><i class="bi bi-person-lock"></i><span class="d-none d-sm-inline"> Admin</span></a>
            <button class="cart-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#cartPanel" aria-label="Open shopping bag">
                <i class="bi bi-bag"></i><span class="cart-badge" id="cartCount">0</span>
            </button>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#shop">Shop</a></li>
                <li class="nav-item"><a class="nav-link" href="#how">How to Order</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- ================= HERO (animated text) ================= -->
<header id="home" class="hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <p class="eyebrow">Online Book Store &middot; Sri Lanka</p>
                <h1 class="wave-title" id="waveTitle">Every story deserves a home.</h1>
                <p class="typing-line">
                    Find your next <span id="typed" class="typed-text"></span><span class="cursor">|</span>
                </p>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="#shop" class="btn btn-main"><i class="bi bi-bag"></i> Shop Now</a>
                    <a href="#how" class="btn btn-ghost">How to Order</a>
                </div>
                <div class="row stats mt-5 g-3">
                    <div class="col-4 stat"><h3 id="statBooks">0</h3><p>Titles</p></div>
                    <div class="col-4 stat"><h3 id="statAuthors">0</h3><p>Authors</p></div>
                    <div class="col-4 stat"><h3 id="statCats">0</h3><p>Categories</p></div>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <div class="hero-stack" id="heroStack"></div>
            </div>
        </div>
    </div>
</header>

<!-- ================= FEATURES ================= -->
<section class="features py-4">
    <div class="container">
        <div class="row g-3 text-center text-md-start">
            <div class="col-md-4 feature"><i class="bi bi-truck"></i><div><strong>Island-wide delivery</strong><small>Delivered to your doorstep</small></div></div>
            <div class="col-md-4 feature"><i class="bi bi-cash-coin"></i><div><strong>Cash on delivery</strong><small>Pay when your books arrive</small></div></div>
            <div class="col-md-4 feature"><i class="bi bi-gift"></i><div><strong>Free delivery</strong><small>On orders over LKR <?= number_format(FREE_DELIVERY_OVER) ?></small></div></div>
        </div>
    </div>
</section>

<!-- ================= SHOP ================= -->
<section id="shop" class="py-5">
    <div class="container">
        <h2 class="section-title reveal-text">Our Collection</h2>
        <p class="section-sub">Hand-picked books for every reader</p>

        <div class="shop-toolbar">
            <div class="input-group search-box">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" id="searchInput" class="form-control" placeholder="Search title, author or category" aria-label="Search books">
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small text-nowrap" id="resultCount"></span>
                <select id="sortSelect" class="form-select" aria-label="Sort books">
                    <option value="newest">Newest</option>
                    <option value="title">Title A–Z</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                </select>
            </div>
        </div>

        <div class="chips" id="categoryChips"></div>

        <div class="row g-3 g-md-4" id="bookGrid">
            <div class="col-12 text-center py-5 text-muted">Loading books...</div>
        </div>
    </div>
</section>

<!-- ================= HOW TO ORDER ================= -->
<section id="how" class="py-5 how">
    <div class="container">
        <h2 class="section-title reveal-text">How to Order</h2>
        <p class="section-sub">Three simple steps</p>
        <div class="row g-4 mt-1">
            <div class="col-md-4"><div class="step"><span>1</span><h5>Browse</h5><p>Search or filter by category and open any book to read more.</p></div></div>
            <div class="col-md-4"><div class="step"><span>2</span><h5>Add to bag</h5><p>Choose how many copies you want and add them to your shopping bag.</p></div></div>
            <div class="col-md-4"><div class="step"><span>3</span><h5>Pay on delivery</h5><p>Enter your address, place the order and pay cash when it arrives.</p></div></div>
        </div>
    </div>
</section>

<!-- ================= ABOUT ================= -->
<section id="about" class="py-5">
    <div class="container text-center">
        <h2 class="section-title reveal-text">About BookNest</h2>
        <p class="mx-auto about-text">
            BookNest is an online book store where readers can browse books, add them to a shopping bag and
            order with cash on delivery. Store staff manage books and orders from a separate admin panel.
            Built with HTML5, CSS3, Bootstrap 5 and JavaScript on the frontend, PHP on the backend and a MySQL database.
        </p>
    </div>
</section>

<footer class="site-footer">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-book-half"></i> BookNest &copy; 2026</span>
        <a href="admin.php"><i class="bi bi-lock"></i> Admin login</a>
    </div>
</footer>

<!-- ================= BOOK DETAIL MODAL ================= -->
<div class="modal fade" id="bookModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <button type="button" class="btn-close modal-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-4" id="bookDetail"></div>
        </div>
    </div>
</div>

<!-- ================= SHOPPING BAG ================= -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="cartPanel" aria-labelledby="cartTitle">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="cartTitle"><i class="bi bi-bag"></i> Your Bag</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div id="cartItems" class="flex-grow-1"></div>
        <div id="cartFooter" class="cart-footer">
            <div id="freeNote" class="free-note"></div>
            <div class="d-flex justify-content-between"><span>Subtotal</span><span id="cartSubtotal"></span></div>
            <div class="d-flex justify-content-between"><span>Delivery</span><span id="cartDelivery"></span></div>
            <div class="d-flex justify-content-between total-line"><span>Total</span><span id="cartTotal"></span></div>
            <button class="btn btn-main w-100 mt-3" id="checkoutBtn">Checkout</button>
        </div>
    </div>
</div>

<!-- ================= CHECKOUT MODAL ================= -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="checkoutForm" novalidate>
            <div class="modal-header">
                <h5 class="modal-title">Delivery Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="cName">Full name</label>
                    <input type="text" id="cName" class="form-control" required maxlength="100" autocomplete="name">
                    <div class="invalid-feedback">Please enter your name.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="cPhone">Phone number</label>
                    <input type="tel" id="cPhone" class="form-control" required placeholder="07XXXXXXXX" pattern="^(\+94|0)[0-9]{9}$" autocomplete="tel">
                    <div class="invalid-feedback">Enter a valid number, e.g. 0771234567.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="cAddress">Delivery address</label>
                    <textarea id="cAddress" class="form-control" rows="2" required maxlength="255" autocomplete="street-address"></textarea>
                    <div class="invalid-feedback">Please enter your address.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="cCity">City</label>
                    <input type="text" id="cCity" class="form-control" required maxlength="60" autocomplete="address-level2">
                    <div class="invalid-feedback">Please enter your city.</div>
                </div>
                <div class="pay-box">
                    <i class="bi bi-cash-coin"></i>
                    <div><strong>Cash on delivery</strong><br><small>Total to pay: <span id="checkoutTotal"></span></small></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button>
                <button type="submit" class="btn btn-main" id="placeOrderBtn">Place Order</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= ORDER SUCCESS MODAL ================= -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center p-4">
            <div class="success-icon"><i class="bi bi-check-lg"></i></div>
            <h4 class="mt-3">Thank you!</h4>
            <p class="mb-1">Your order <strong id="orderNo"></strong> has been placed.</p>
            <p class="text-muted small">Please keep <strong id="orderTotal"></strong> ready to pay on delivery.</p>
            <button class="btn btn-main" data-bs-dismiss="modal">Continue Shopping</button>
        </div>
    </div>
</div>

<!-- ================= TOAST ================= -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="toast" class="toast align-items-center border-0" role="status" aria-live="polite">
        <div class="d-flex">
            <div class="toast-body" id="toastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
    const SHOP = <?= json_encode(['deliveryFee' => DELIVERY_FEE, 'freeOver' => FREE_DELIVERY_OVER]) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="common.js"></script>
<script src="shop.js"></script>
</body>
</html>
