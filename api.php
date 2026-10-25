<?php
// =====================================================
// BookNest API
//
// Public (shop):
//   GET  api.php?action=books[&search=&category=&sort=]   Read books
//   POST api.php?action=order_create                       Place an order
//
// Admin only (must be logged in via admin.php):
//   POST api.php?action=book_create                        Create a book
//   POST api.php?action=book_update                        Update a book
//   POST api.php?action=book_delete                        Delete a book
//   GET  api.php?action=orders                             List orders
//   POST api.php?action=order_status                       Change order status
//   GET  api.php?action=stats                              Dashboard numbers
// =====================================================

session_start();
header('Content-Type: application/json');
require_once 'config.php';

$pdo    = getConnection();
$action = $_GET['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];

function respond($success, $message, $data = null) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

function requireAdmin() {
    if (empty($_SESSION['admin'])) {
        http_response_code(401);
        respond(false, 'Please log in as admin.');
    }
}

function str($value) {
    return is_scalar($value) ? trim((string)$value) : '';
}

// Validates book form data and returns clean values
function validateBook($in) {
    $title       = str($in['title'] ?? '');
    $author      = str($in['author'] ?? '');
    $category    = str($in['category'] ?? '');
    $isbn        = preg_replace('/[\s-]/', '', str($in['isbn'] ?? ''));
    $description = str($in['description'] ?? '');
    $coverUrl    = str($in['cover_url'] ?? '');
    $price       = $in['price'] ?? '';
    $stock       = $in['stock'] ?? '';

    if ($title === '' || $author === '' || $category === '') respond(false, 'Title, author and category are required.');
    if (strlen($title) > 150)  respond(false, 'Title is too long (max 150 characters).');
    if (strlen($author) > 100) respond(false, 'Author is too long (max 100 characters).');
    if (strlen($category) > 50) respond(false, 'Category is too long (max 50 characters).');
    if ($isbn !== '' && !preg_match('/^(\d{9}[\dXx]|\d{13})$/', $isbn)) respond(false, 'ISBN must be 10 or 13 digits.');
    if (strlen($description) > 2000) respond(false, 'Description is too long (max 2000 characters).');
    if ($coverUrl !== '' && (!filter_var($coverUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $coverUrl) || strlen($coverUrl) > 500)) {
        respond(false, 'Cover image must be a valid link starting with http:// or https://');
    }
    if (!is_numeric($price) || $price < 0 || $price > 9999999) respond(false, 'Price must be a positive number.');
    if (!is_numeric($stock) || $stock < 0 || floor($stock) != $stock || $stock > 100000) respond(false, 'Stock must be a whole number (0 or more).');

    return [
        $title, $author, $category,
        $isbn === '' ? null : strtoupper($isbn),
        $description === '' ? null : $description,
        round((float)$price, 2), (int)$stock,
        $coverUrl === '' ? null : $coverUrl,
    ];
}

try {
    switch ($action) {

        // ================= READ BOOKS =================
        case 'books':
            $where = [];
            $params = [];
            $search = str($_GET['search'] ?? '');
            $category = str($_GET['category'] ?? '');
            if ($search !== '') {
                $where[] = '(title LIKE ? OR author LIKE ? OR category LIKE ?)';
                $like = "%$search%";
                array_push($params, $like, $like, $like);
            }
            if ($category !== '' && $category !== 'All') {
                $where[] = 'category = ?';
                $params[] = $category;
            }
            $sorts = [
                'newest'     => 'id DESC',
                'price_asc'  => 'price ASC',
                'price_desc' => 'price DESC',
                'title'      => 'title ASC',
            ];
            $order = $sorts[$_GET['sort'] ?? 'newest'] ?? 'id DESC';
            $sql = 'SELECT * FROM books' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY $order";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            respond(true, 'Books loaded.', $stmt->fetchAll());

        // ================= CREATE BOOK =================
        case 'book_create':
            requireAdmin();
            $values = validateBook($input);
            $stmt = $pdo->prepare(
                'INSERT INTO books (title, author, category, isbn, description, price, stock, cover_url)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute($values);
            respond(true, 'Book added successfully.', ['id' => (int)$pdo->lastInsertId()]);

        // ================= UPDATE BOOK =================
        case 'book_update':
            requireAdmin();
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) respond(false, 'Invalid book ID.');
            $values = validateBook($input);
            $values[] = $id;
            $stmt = $pdo->prepare(
                'UPDATE books SET title = ?, author = ?, category = ?, isbn = ?, description = ?,
                 price = ?, stock = ?, cover_url = ? WHERE id = ?'
            );
            $stmt->execute($values);
            respond(true, 'Book updated successfully.');

        // ================= DELETE BOOK =================
        case 'book_delete':
            requireAdmin();
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) respond(false, 'Invalid book ID.');
            $stmt = $pdo->prepare('DELETE FROM books WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) respond(false, 'Book not found.');
            respond(true, 'Book deleted successfully.');

        // ================= PLACE ORDER =================
        case 'order_create':
            $name    = str($input['name'] ?? '');
            $phone   = preg_replace('/[\s-]/', '', str($input['phone'] ?? ''));
            $address = str($input['address'] ?? '');
            $city    = str($input['city'] ?? '');

            if ($name === '' || strlen($name) > 100) respond(false, 'Please enter your full name.');
            if (!preg_match('/^(\+94|0)\d{9}$/', $phone)) respond(false, 'Please enter a valid phone number, e.g. 0771234567.');
            if ($address === '' || strlen($address) > 255) respond(false, 'Please enter your delivery address.');
            if ($city === '' || strlen($city) > 60) respond(false, 'Please enter your city.');

            $items = $input['items'] ?? [];
            if (!is_array($items) || count($items) === 0) respond(false, 'Your bag is empty.');

            // Merge quantities per book
            $qty = [];
            foreach ($items as $item) {
                $bookId = (int)($item['book_id'] ?? 0);
                $q      = (int)($item['quantity'] ?? 0);
                if ($bookId <= 0 || $q <= 0 || $q > 99) respond(false, 'Your bag has an invalid item.');
                $qty[$bookId] = ($qty[$bookId] ?? 0) + $q;
            }
            if (count($qty) > 50) respond(false, 'Too many different books in one order.');

            $pdo->beginTransaction();

            // Lock each book row, check stock and use the real price from the database
            $find = $pdo->prepare('SELECT id, title, price, stock FROM books WHERE id = ? FOR UPDATE');
            $lines = [];
            $subtotal = 0;
            foreach ($qty as $bookId => $q) {
                $find->execute([$bookId]);
                $book = $find->fetch();
                if (!$book) {
                    $pdo->rollBack();
                    respond(false, 'A book in your bag is no longer available. Please refresh the page.');
                }
                if ((int)$book['stock'] < $q) {
                    $pdo->rollBack();
                    $left = (int)$book['stock'];
                    respond(false, "Sorry, only $left " . ($left === 1 ? 'copy' : 'copies') . " of \"{$book['title']}\" left.");
                }
                $lines[] = [$book, $q];
                $subtotal += (float)$book['price'] * $q;
            }

            $subtotal = round($subtotal, 2);
            $delivery = $subtotal >= FREE_DELIVERY_OVER ? 0 : DELIVERY_FEE;
            $total    = $subtotal + $delivery;

            $pdo->prepare(
                'INSERT INTO orders (customer_name, phone, address, city, subtotal, delivery_fee, total)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([$name, $phone, $address, $city, $subtotal, $delivery, $total]);
            $orderId = (int)$pdo->lastInsertId();

            $addItem = $pdo->prepare('INSERT INTO order_items (order_id, book_id, title, price, quantity) VALUES (?, ?, ?, ?, ?)');
            $reduce  = $pdo->prepare('UPDATE books SET stock = stock - ? WHERE id = ?');
            foreach ($lines as [$book, $q]) {
                $addItem->execute([$orderId, $book['id'], $book['title'], $book['price'], $q]);
                $reduce->execute([$q, $book['id']]);
            }

            $pdo->commit();
            respond(true, 'Order placed successfully.', ['order_id' => $orderId, 'total' => $total]);

        // ================= LIST ORDERS =================
        case 'orders':
            requireAdmin();
            $orders = $pdo->query('SELECT * FROM orders ORDER BY id DESC')->fetchAll();
            $items  = $pdo->query('SELECT order_id, book_id, title, price, quantity FROM order_items ORDER BY id')->fetchAll();
            $byOrder = [];
            foreach ($items as $item) $byOrder[$item['order_id']][] = $item;
            foreach ($orders as &$order) $order['items'] = $byOrder[$order['id']] ?? [];
            unset($order);
            respond(true, 'Orders loaded.', $orders);

        // ================= UPDATE ORDER STATUS =================
        case 'order_status':
            requireAdmin();
            $id     = (int)($input['id'] ?? 0);
            $status = str($input['status'] ?? '');
            if (!in_array($status, ['Pending', 'Shipped', 'Delivered', 'Cancelled'], true)) respond(false, 'Invalid status.');

            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT status FROM orders WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $current = $stmt->fetchColumn();
            if ($current === false) { $pdo->rollBack(); respond(false, 'Order not found.'); }
            if ($current === 'Cancelled' && $status !== 'Cancelled') {
                $pdo->rollBack();
                respond(false, 'A cancelled order cannot be reopened.');
            }
            // Cancelling puts the books back into stock
            if ($status === 'Cancelled' && $current !== 'Cancelled') {
                $pdo->prepare(
                    'UPDATE books b JOIN order_items i ON i.book_id = b.id
                     SET b.stock = b.stock + i.quantity WHERE i.order_id = ?'
                )->execute([$id]);
            }
            $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $id]);
            $pdo->commit();
            respond(true, "Order marked as $status.");

        // ================= DASHBOARD STATS =================
        case 'stats':
            requireAdmin();
            respond(true, 'Stats loaded.', [
                'books'     => (int)$pdo->query('SELECT COUNT(*) FROM books')->fetchColumn(),
                'stock'     => (int)$pdo->query('SELECT COALESCE(SUM(stock),0) FROM books')->fetchColumn(),
                'low_stock' => (int)$pdo->query('SELECT COUNT(*) FROM books WHERE stock < 5')->fetchColumn(),
                'orders'    => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
                'pending'   => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn(),
                'revenue'   => (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> 'Cancelled'")->fetchColumn(),
            ]);

        default:
            http_response_code(400);
            respond(false, 'Unknown action.');
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    respond(false, 'Database error: ' . $e->getMessage());
}
