-- ============================================================
-- SHIPPING SETTINGS — run in phpMyAdmin
-- Run AFTER settings_setup.sql
-- ============================================================

USE sarovar_restaurant;

INSERT INTO site_settings (setting_key, setting_val, label, group_name) VALUES
('shipping_method',          'delivery',  'Shipping Method (delivery / pickup / both)',         'shipping'),
('delivery_enabled',         '1',         'Delivery Enabled (1 = yes, 0 = no)',                 'shipping'),
('pickup_enabled',           '0',         'Pickup Enabled (1 = yes, 0 = no)',                   'shipping'),
('delivery_charge_type',     'flat',      'Charge Type (flat / free_above / per_km)',            'shipping'),
('delivery_flat_rate',       '40',        'Flat Delivery Charge (₹)',                           'shipping'),
('delivery_free_above',      '500',       'Free Delivery Above This Order Value (₹)',            'shipping'),
('delivery_per_km_rate',     '10',        'Charge Per KM (₹) — for future distance-based',      'shipping'),
('delivery_min_order',       '100',       'Minimum Order Value to Allow Delivery (₹)',           'shipping'),
('delivery_max_distance',    '10',        'Maximum Delivery Distance (km)',                      'shipping'),
('delivery_time_min',        '30',        'Minimum Delivery Time (minutes)',                     'shipping'),
('delivery_time_max',        '60',        'Maximum Delivery Time (minutes)',                     'shipping'),
('pickup_time',              '15',        'Pickup Ready Time (minutes)',                         'shipping'),
('delivery_message',         'Free delivery on orders above ₹500!', 'Delivery Promo Message',   'shipping')
ON DUPLICATE KEY UPDATE setting_val = VALUES(setting_val);
