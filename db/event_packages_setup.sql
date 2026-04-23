-- ============================================================
-- Run this in phpMyAdmin if you haven't run events_setup.sql yet
-- ============================================================

USE sarovar_restaurant;

CREATE TABLE IF NOT EXISTS event_packages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(50)  NOT NULL UNIQUE,
    price       DECIMAL(12,2) NOT NULL,
    max_guests  INT NOT NULL,
    duration    VARCHAR(50)  NOT NULL,
    description TEXT,
    features    TEXT,
    is_active   TINYINT(1) DEFAULT 1,
    sort_order  INT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS event_bookings (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT,
    booking_ref     VARCHAR(20) UNIQUE NOT NULL,
    name            VARCHAR(150) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    phone           VARCHAR(20)  NOT NULL,
    event_type      ENUM('wedding','birthday','corporate','engagement','anniversary','other') NOT NULL,
    event_date      DATE NOT NULL,
    time_slot       ENUM('morning','evening','fullday') NOT NULL,
    guest_count     VARCHAR(50)  NOT NULL,
    package_id      INT,
    package_name    VARCHAR(100),
    package_price   DECIMAL(12,2),
    advance_amount  DECIMAL(12,2),
    special_requests TEXT,
    status          ENUM('pending','confirmed','cancelled','completed') DEFAULT 'pending',
    admin_notes     TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Clear and re-insert packages (safe to run multiple times)
DELETE FROM event_packages;

INSERT INTO event_packages (name, slug, price, max_guests, duration, description, features, sort_order) VALUES
(
    'Silver',
    'silver',
    75000.00,
    100,
    '6 hours',
    'Perfect for intimate gatherings and small celebrations.',
    'Basic hall décor & lighting|Veg buffet (5 items)|Welcome drinks|Dedicated event staff|Basic sound system|6 hours slot',
    1
),
(
    'Gold',
    'gold',
    150000.00,
    250,
    '8 hours',
    'Our most popular package for weddings and large celebrations.',
    'Premium floral décor & lighting|Veg + Non-veg buffet (10 items)|Live counters & dessert bar|Professional sound system|Dedicated event coordinator|Bridal room access|8 hours slot',
    2
),
(
    'Platinum',
    'platinum',
    300000.00,
    500,
    '12 hours',
    'The ultimate luxury experience for grand weddings and elite events.',
    'Luxury mandap / stage setup|Full veg + non-veg menu (15+ items)|DJ + professional lighting|Photography & videography|Bridal suite with green room|Valet parking|Dedicated wedding planner|Full day 12 hours slot',
    3
);
