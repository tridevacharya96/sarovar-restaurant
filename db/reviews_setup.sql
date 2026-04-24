-- ============================================================
-- REVIEWS & RATINGS — run in phpMyAdmin
-- ============================================================

USE sarovar_restaurant;

CREATE TABLE IF NOT EXISTS reviews (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT DEFAULT NULL,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) DEFAULT NULL,
    rating          TINYINT(1) NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review_text     TEXT,
    source          ENUM('website','google') DEFAULT 'website',
    status          ENUM('pending','approved','rejected') DEFAULT 'pending',
    is_featured     TINYINT(1) DEFAULT 0,
    admin_reply     TEXT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Add Google settings to site_settings
INSERT INTO site_settings (setting_key, setting_val, label, group_name) VALUES
('google_place_id',       '',      'Google Place ID (from Google Maps Business)', 'reviews'),
('google_api_key',        '',      'Google Places API Key (for fetching reviews)', 'reviews'),
('reviews_show_google',   '1',     'Show Google Reviews on website (1=yes, 0=no)', 'reviews'),
('reviews_show_website',  '1',     'Show Website Reviews on website (1=yes, 0=no)', 'reviews'),
('reviews_per_page',      '6',     'Number of reviews to show per page',           'reviews'),
('review_redirect_google','1',     'Show "Also review on Google" prompt (1=yes)',  'reviews'),
('google_review_url',     '',      'Your Google Review URL (from Google Maps)',     'reviews')
ON DUPLICATE KEY UPDATE setting_val = VALUES(setting_val);

-- Seed 4 sample approved reviews
INSERT INTO reviews (name, email, rating, review_text, source, status, is_featured) VALUES
('Rajesh Kumar',  'rajesh@example.com',  5, 'Sarovar is hands down the best restaurant in Rourkela! The Chicken Biryani is absolutely divine and the service is impeccable. A must-visit!', 'website', 'approved', 1),
('Priya Mishra',  'priya@example.com',   5, 'We celebrated our anniversary here and it was magical. The ambiance, the food, the staff — everything was perfect. Highly recommended!', 'website', 'approved', 1),
('Amit Sharma',   'amit@example.com',    4, 'The Dal Makhani and Butter Naan combo is out of this world! Fast delivery and food arrives hot. Sarovar never disappoints!', 'website', 'approved', 1),
('Sunita Patel',  'sunita@example.com',  5, 'Best vegetarian options in Rourkela! The Paneer Tikka and Veg Biryani are absolutely delicious. Great value for money!', 'website', 'approved', 1);

-- Index for fast queries
CREATE INDEX idx_reviews_status  ON reviews(status);
CREATE INDEX idx_reviews_rating  ON reviews(rating);
CREATE INDEX idx_reviews_source  ON reviews(source);
