-- ============================================================
-- SITE SETTINGS — run this in phpMyAdmin
-- ============================================================

USE sarovar_restaurant;

CREATE TABLE IF NOT EXISTS site_settings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_val TEXT,
    label       VARCHAR(150),
    group_name  VARCHAR(50),
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO site_settings (setting_key, setting_val, label, group_name) VALUES
-- General
('restaurant_name',     'The Sarovar Court',                                   'Restaurant Name',       'general'),
('tagline',             'Experience the finest flavors of India in the heart of Rourkela', 'Hero Tagline', 'general'),
('about_text_1',        'Nestled in the vibrant city of Rourkela, The Sarovar Court has been a beacon of authentic Indian cuisine for over 15 years. Founded with a passion for preserving traditional recipes while embracing modern culinary techniques, we have become the go-to destination for food lovers across Odisha.', 'About Paragraph 1', 'general'),
('about_text_2',        'Our master chefs bring decades of experience, crafting each dish with the finest locally sourced ingredients. From aromatic biryanis to rich curries and delectable desserts, every meal at The Sarovar Court is a celebration of flavors.', 'About Paragraph 2', 'general'),
('established_year',    '2009',                                                  'Established Year',      'general'),
('years_experience',    '15+',                                                   'Years Experience',      'general'),

-- Contact
('phone_primary',       '+91 98765 43210',                                       'Primary Phone',         'contact'),
('phone_secondary',     '+91 06612 123456',                                      'Secondary Phone',       'contact'),
('email_primary',       'info@sarovarrourkela.com',                              'Primary Email',         'contact'),
('email_reservations',  'reservations@sarovarrourkela.com',                      'Reservations Email',    'contact'),
('address_line1',       'Ispat Market, Ambagan Circle',                          'Address Line 1',        'contact'),
('address_line2',       'Bank Street, Sector 19',                                'Address Line 2',        'contact'),
('address_city',        'Rourkela',                                              'City',                  'contact'),
('address_state',       'Odisha',                                                'State',                 'contact'),
('address_pincode',     '769005',                                                'PIN Code',              'contact'),

-- Hours
('hours_weekday',       'Monday – Sunday',                                       'Days Open',             'hours'),
('hours_open',          '11:00 AM',                                              'Opening Time',          'hours'),
('hours_close',         '11:00 PM',                                              'Closing Time',          'hours'),
('hours_note',          'Open all days including public holidays',               'Hours Note',            'hours'),

-- Social Media
('social_facebook',     '#',                                                     'Facebook URL',          'social'),
('social_instagram',    '#',                                                     'Instagram URL',         'social'),
('social_twitter',      '#',                                                     'Twitter/X URL',         'social'),
('social_whatsapp',     '#',                                                     'WhatsApp URL',          'social'),
('social_youtube',      '#',                                                     'YouTube URL',           'social'),

-- SEO
('meta_description',    'The Sarovar Court – Authentic Multicuisine Indian cuisine in Rourkela. Order online, book a table or hall for your events.', 'Meta Description', 'seo'),
('google_maps_embed',   'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3673.5!2d84.8630!3d22.2556!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3a201a95ac8e4cbb%3A0x1!2sIspat+Market%2C+Sector+19%2C+Rourkela!5e0!3m2!1sen!2sin!4v1700000000', 'Google Maps Embed URL', 'contact')

ON DUPLICATE KEY UPDATE setting_val = VALUES(setting_val);

-- Update name if already installed
UPDATE site_settings SET setting_val = 'The Sarovar Court' WHERE setting_key = 'restaurant_name';
UPDATE site_settings SET setting_val = 'The Sarovar Court – Authentic Multicuisine Restaurant in Rourkela. Order online, book a table or hall for your events.' WHERE setting_key = 'meta_description';
