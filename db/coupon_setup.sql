-- ============================================================
-- COUPON SYSTEM — run this in phpMyAdmin
-- ============================================================

USE sarovar_restaurant;

CREATE TABLE IF NOT EXISTS coupons (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(30) UNIQUE NOT NULL,
    description     VARCHAR(200),
    discount_type   ENUM('percentage','flat') NOT NULL DEFAULT 'percentage',
    discount_value  DECIMAL(10,2) NOT NULL,
    min_order_value DECIMAL(10,2) DEFAULT 0,
    max_discount    DECIMAL(10,2) DEFAULT NULL,
    usage_limit     INT DEFAULT NULL,
    used_count      INT DEFAULT 0,
    valid_from      DATE DEFAULT NULL,
    valid_until     DATE DEFAULT NULL,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add coupon fields to orders table
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS coupon_code    VARCHAR(30)   DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0;

-- Seed with sample coupons
INSERT INTO coupons (code, description, discount_type, discount_value, min_order_value, max_discount, usage_limit, valid_until, is_active) VALUES
('SAROVAR20',  'Get 20% off on your first order',     'percentage', 20.00, 200.00, 150.00, 100,  DATE_ADD(CURDATE(), INTERVAL 90 DAY),  1),
('WELCOME10',  'Welcome! Flat ₹10 off',               'flat',       10.00, 100.00, NULL,   NULL, DATE_ADD(CURDATE(), INTERVAL 365 DAY), 1),
('FEAST50',    'Flat ₹50 off on orders above ₹500',   'flat',       50.00, 500.00, NULL,   50,   DATE_ADD(CURDATE(), INTERVAL 30 DAY),  1),
('DIWALI25',   '25% off — Festive special',           'percentage', 25.00, 300.00, 200.00, 200, DATE_ADD(CURDATE(), INTERVAL 60 DAY),  1);
