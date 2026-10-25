<?php
session_start();
require_once 'config.php';

// ---------- Logout ----------
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ---------- Login ----------
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = is_string($_POST['username'] ?? null) ? $_POST['username'] : '';
    $pass = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (hash_equals(ADMIN_USER, $user) && hash_equals(ADMIN_PASS, $pass)) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit;
    }
    $error = 'Wrong username or password.';
}

$loggedIn = !empty($_SESSION['admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookNest Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">

<?php if (!$loggedIn): ?>
<!-- ================= LOGIN ================= -->
<main class="login-wrap">
    <form method="post" class="login-card">
        <a href="index.php" class="navbar-brand d-block text-center mb-1"><i class="bi bi-book-half"></i> BookNest</a>
        <p class="text-center text-muted mb-4">Admin panel</p>
        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <div class="mb-3">
            <label class="form-label" for="username">Username</label>
            <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
        </div>
        <div class="mb-4">
            <label class="form-label" for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
        </div>
        <button class="btn btn-main w-100">Log in</button>
        <a href="index.php" class="d-block text-center mt-3 small">&larr; Back to shop</a>
    </form>
</main>

<?php else: ?>
<!-- ================= DASHBOARD ================= -->
<nav class="navbar admin-nav">
    <div class="container">
        <span class="navbar-brand"><i class="bi bi-book-half"></i> BookNest <small>Admin</small></span>
        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-sm btn-light" target="_blank"><i class="bi bi-shop"></i> <span class="d-none d-sm-inline">View shop</span></a>
            <a href="admin.php?logout=1" class="btn btn-sm btn-outline-light"><i class="bi bi-box-arrow-right"></i> <span class="d-none d-sm-inline">Logout</span></a>
        </div>
    </div>
</nav>

<main class="container py-4">
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2"><div class="kpi"><span>Books</span><strong id="sBooks">–</strong></div></div>
        <div class="col-6 col-lg-2"><div class="kpi"><span>Copies in stock</span><strong id="sStock">–</strong></div></div>
        <div class="col-6 col-lg-2"><div class="kpi warn"><span>Low stock (&lt;5)</span><strong id="sLow">–</strong></div></div>
        <div class="col-6 col-lg-2"><div class="kpi"><span>Orders</span><strong id="sOrders">–</strong></div></div>
        <div class="col-6 col-lg-2"><div class="kpi warn"><span>Pending</span><strong id="sPending">–</strong></div></div>
        <div class="col-6 col-lg-2"><div class="kpi"><span>Revenue</span><strong id="sRevenue">–</strong></div></div>
    </div>

    <ul class="nav nav-tabs admin-tabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabBooks" type="button" role="tab"><i class="bi bi-journal-bookmark"></i> Books</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabOrders" type="button" role="tab"><i class="bi bi-receipt"></i> Orders</button></li>
    </ul>

    <div class="tab-content admin-panel">
        <!-- ---------- BOOKS (CRUD) ---------- -->
        <div class="tab-pane fade show active" id="tabBooks" role="tabpanel">
            <div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
                <div class="input-group search-box">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" id="bookSearch" class="form-control" placeholder="Search books" aria-label="Search books">
                </div>
                <button class="btn btn-main" id="addBtn"><i class="bi bi-plus-circle"></i> Add New Book</button>
            </div>
            <div class="table-responsive">
                <table class="table align-middle admin-table">
                    <thead><tr><th>Book</th><th>Category</th><th class="text-end">Price</th><th class="text-center">Stock</th><th class="text-center">Actions</th></tr></thead>
                    <tbody id="bookTable"><tr><td colspan="5" class="text-center py-4">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>

        <!-- ---------- ORDERS ---------- -->
        <div class="tab-pane fade" id="tabOrders" role="tabpanel">
            <div class="table-responsive">
                <table class="table align-middle admin-table">
                    <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th class="text-end">Total</th><th>Status</th></tr></thead>
                    <tbody id="orderTable"><tr><td colspan="5" class="text-center py-4">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- ---------- Add / Edit book modal ---------- -->
<div class="modal fade" id="bookModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form class="modal-content" id="bookForm" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add New Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="bookId">
                <div class="row g-4">
                    <div class="col-md-3 d-none d-md-block">
                        <label class="form-label">Preview</label>
                        <div id="coverPreview"></div>
                    </div>
                    <div class="col-md-9">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="title">Title *</label>
                                <input type="text" id="title" class="form-control" required maxlength="150">
                            </div>
                            <div class="col-sm-7">
                                <label class="form-label" for="author">Author *</label>
                                <input type="text" id="author" class="form-control" required maxlength="100">
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label" for="category">Category *</label>
                                <select id="category" class="form-select" required>
                                    <option value="">Select</option>
                                    <option>Novel</option><option>Fiction</option><option>Technology</option>
                                    <option>Self-Help</option><option>History</option><option>Science</option>
                                    <option>Children</option><option>Education</option><option>Religion</option>
                                </select>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label" for="price">Price (LKR) *</label>
                                <input type="number" id="price" class="form-control" min="0" step="0.01" required>
                            </div>
                            <div class="col-sm-3">
                                <label class="form-label" for="stock">Stock *</label>
                                <input type="number" id="stock" class="form-control" min="0" step="1" required>
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label" for="isbn">ISBN</label>
                                <input type="text" id="isbn" class="form-control" maxlength="20" placeholder="Loads cover automatically">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="cover_url">Cover image link (optional)</label>
                                <input type="url" id="cover_url" class="form-control" maxlength="500" placeholder="https://...">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="description">Description</label>
                                <textarea id="description" class="form-control" rows="3" maxlength="2000"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-main" id="saveBtn">Save Book</button>
            </div>
        </form>
    </div>
</div>

<!-- ---------- Delete confirm modal ---------- -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
                <p class="mt-2 mb-0">Delete <strong id="deleteName"></strong>?</p>
                <small class="text-muted">This cannot be undone. Past orders keep their records.</small>
            </div>
            <div class="modal-footer justify-content-center">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="toast" class="toast align-items-center border-0" role="status" aria-live="polite">
        <div class="d-flex">
            <div class="toast-body" id="toastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="common.js"></script>
<script src="admin.js"></script>
<?php endif; ?>
</body>
</html>
