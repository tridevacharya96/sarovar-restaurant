-- ============================================================
-- PAYMENT GATEWAY SETTINGS — run in phpMyAdmin
-- Run AFTER settings_setup.sql
-- ============================================================

USE sarovar_restaurant;

-- Add payment gateway settings to site_settings table
INSERT INTO site_settings (setting_key, setting_val, label, group_name) VALUES
('payment_gateway',         'none',     'Active Gateway (none / razorpay / cashfree / payu)', 'payment'),
('payment_cod_enabled',     '1',        'Cash on Delivery Enabled (1 = yes, 0 = no)',          'payment'),
('payment_online_enabled',  '0',        'Online Payment Enabled (1 = yes, 0 = no)',             'payment'),
('razorpay_key_id',         '',         'Razorpay Key ID (rzp_live_xxx or rzp_test_xxx)',        'payment'),
('razorpay_key_secret',     '',         'Razorpay Key Secret (keep private — server only)',      'payment'),
('razorpay_mode',           'test',     'Razorpay Mode (test / live)',                           'payment'),
('payment_currency',        'INR',      'Currency Code',                                         'payment'),
('payment_company_name',    'The Sarovar Court', 'Company Name shown on payment popup',          'payment'),
('payment_company_logo',    '',         'Logo URL shown on payment popup (optional)',             'payment'),
('payment_company_color',   '#D85A30',  'Brand color on payment popup',                          'payment')
ON DUPLICATE KEY UPDATE setting_val = VALUES(setting_val);

-- Add payment fields to orders table if not already present
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS payment_status   ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS payment_id       VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS payment_gateway  VARCHAR(50)  DEFAULT NULL;
