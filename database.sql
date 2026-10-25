-- =====================================================
-- BookNest - Online Book Store - Database Script
-- Run this in phpMyAdmin -> SQL tab -> Go
--
-- XAMPP: run the whole file.
-- InfinityFree: you cannot run CREATE DATABASE / USE.
--   Create the database in the control panel, open it in
--   phpMyAdmin, and run from "DROP TABLE" downwards.
--
-- WARNING: this script deletes and recreates the tables,
-- so any existing BookNest data is reset to the sample data.
-- =====================================================

CREATE DATABASE IF NOT EXISTS bookstore_db;
USE bookstore_db;

DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS books;

-- ---------- Books ----------
CREATE TABLE books (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(150)  NOT NULL,
    author       VARCHAR(100)  NOT NULL,
    category     VARCHAR(50)   NOT NULL,
    isbn         VARCHAR(20)   NULL,
    description  TEXT          NULL,
    price        DECIMAL(10,2) NOT NULL,
    stock        INT           NOT NULL DEFAULT 0,
    cover_url    VARCHAR(500)  NULL,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Orders ----------
CREATE TABLE orders (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    customer_name  VARCHAR(100)  NOT NULL,
    phone          VARCHAR(20)   NOT NULL,
    address        VARCHAR(255)  NOT NULL,
    city           VARCHAR(60)   NOT NULL,
    subtotal       DECIMAL(10,2) NOT NULL,
    delivery_fee   DECIMAL(10,2) NOT NULL,
    total          DECIMAL(10,2) NOT NULL,
    status         ENUM('Pending','Shipped','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
    created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- Order items (one row per book in an order) ----------
CREATE TABLE order_items (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    order_id  INT           NOT NULL,
    book_id   INT           NULL,
    title     VARCHAR(150)  NOT NULL,
    price     DECIMAL(10,2) NOT NULL,
    quantity  INT           NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id)  REFERENCES books(id)  ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------- Sample books ----------
-- Covers load automatically from Open Library using the ISBN.
-- Books without an ISBN get a designed cover with the title.
INSERT INTO books (title, author, category, isbn, description, price, stock) VALUES
('Atomic Habits', 'James Clear', 'Self-Help', '9780735211292',
 'A practical guide to building good habits and breaking bad ones through small, consistent changes that add up over time.', 3900.00, 20),
('Clean Code', 'Robert C. Martin', 'Technology', '9780132350884',
 'A classic for software developers on writing code that is readable, maintainable and easy to change.', 6800.00, 5),
('The Alchemist', 'Paulo Coelho', 'Fiction', '9780062315007',
 'A shepherd boy travels from Spain to the Egyptian desert in search of treasure and discovers what truly matters.', 2400.00, 12),
('Sapiens', 'Yuval Noah Harari', 'History', '9780062316097',
 'A sweeping look at how Homo sapiens came to dominate the planet, from the first humans to the modern age.', 4500.00, 7),
('Eloquent JavaScript', 'Marijn Haverbeke', 'Technology', '9781593279509',
 'A friendly, hands-on introduction to programming with JavaScript, from the basics to building real projects.', 5200.00, 8),
('Rich Dad Poor Dad', 'Robert T. Kiyosaki', 'Self-Help', '9781612680194',
 'Lessons about money, investing and financial independence, told through the story of two very different fathers.', 2800.00, 15),
('To Kill a Mockingbird', 'Harper Lee', 'Fiction', '9780061120084',
 'A young girl in a small Southern town watches her father defend an innocent man in a deeply unfair trial.', 2600.00, 10),
('1984', 'George Orwell', 'Fiction', '9780451524935',
 'A chilling story of a society under total surveillance, where even thoughts are controlled.', 2200.00, 14),
('The Psychology of Money', 'Morgan Housel', 'Self-Help', '9780857197689',
 'Short stories about how people think about money, and why behaviour matters more than knowledge.', 3500.00, 9),
('A Brief History of Time', 'Stephen Hawking', 'Science', '9780553380163',
 'An accessible journey through black holes, the Big Bang and the nature of time itself.', 3200.00, 4),
('Charlotte''s Web', 'E. B. White', 'Children', '9780064400558',
 'The much-loved tale of a pig named Wilbur and the clever spider who saves his life.', 1900.00, 6),
('Madol Doova', 'Martin Wickramasinghe', 'Novel', NULL,
 'A beloved Sri Lankan coming-of-age story about a mischievous boy and his adventures on a small island.', 1250.00, 15),
('Gamperaliya', 'Martin Wickramasinghe', 'Novel', NULL,
 'A landmark Sinhala novel tracing how a traditional village family changes as modern life arrives.', 1500.00, 10);

-- =====================================================
-- Useful queries for report screenshots
-- =====================================================
-- DESCRIBE books;
-- DESCRIBE orders;
-- DESCRIBE order_items;
-- SELECT * FROM books;
-- SELECT * FROM orders;
-- SELECT * FROM order_items;
