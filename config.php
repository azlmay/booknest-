<?php
// =====================================================
// BookNest settings
// =====================================================

// ---------- Database: XAMPP (local testing) - ACTIVE ----------
define('DB_HOST', 'localhost');
define('DB_NAME', 'bookstore_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// ---------- Database: InfinityFree (for the live site) ----------
// When you upload to InfinityFree:
//   1. Put // in front of the 4 XAMPP lines above.
//   2. Remove // from the 4 lines below and fill in your details
//      (copy them from "MySQL Databases" in the InfinityFree control panel).
// define('DB_HOST', 'sqlXXX.infinityfree.com');
// define('DB_NAME', 'if0_XXXXXXXX_bookstore');
// define('DB_USER', 'if0_XXXXXXXX');
// define('DB_PASS', 'your_vpanel_password');

// ---------- Admin login (change the password before deploying!) ----------
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123');

// ---------- Shop settings (LKR) ----------
define('DELIVERY_FEE', 350);
define('FREE_DELIVERY_OVER', 5000);

function getConnection() {
    try {
        return new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed. Check config.php and that MySQL is running.']);
        exit;
    }
}
