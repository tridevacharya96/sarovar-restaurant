-- ============================================================
-- ADMIN PANEL — additional SQL
-- Run this AFTER your existing database.sql
-- ============================================================

USE sarovar_restaurant;

-- Admins table (separate from users)
CREATE TABLE IF NOT EXISTS admins (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    username     VARCHAR(50)  UNIQUE NOT NULL,
    email        VARCHAR(100) UNIQUE NOT NULL,
    password     VARCHAR(255) NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default admin account  (username: admin  |  password: Admin@123)
INSERT INTO admins (name, username, email, password) VALUES (
    'Super Admin',
    'admin',
    'admin@sarovar.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);

-- Extend reservations status to include 'completed'
ALTER TABLE reservations
    MODIFY status ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending';

-- Add confirmed to orders if not present
ALTER TABLE orders
    MODIFY status ENUM('pending','confirmed','preparing','out_for_delivery','delivered','cancelled') DEFAULT 'pending';

-- Index for faster admin queries
CREATE INDEX idx_orders_created   ON orders(created_at);
CREATE INDEX idx_orders_status    ON orders(status);
CREATE INDEX idx_orders_user      ON orders(user_id);
CREATE INDEX idx_res_date         ON reservations(date);
CREATE INDEX idx_res_status       ON reservations(status);
