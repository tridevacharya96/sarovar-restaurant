<?php
// config/settings.php
// Loads all site settings from DB into a global array.
// Include this at the top of any page that needs dynamic content.

require_once __DIR__ . '/database.php';

function getSettings(): array {
    static $settings = null;
    if ($settings !== null) return $settings;

    $settings = [];
    try {
        $conn   = getConnection();
        $result = $conn->query("SELECT setting_key, setting_val FROM site_settings");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_val'];
            }
        }
        $conn->close();
    } catch (Exception $e) {
        // Return defaults if table doesn't exist yet
    }

    // Fallback defaults so page never breaks
    $defaults = [
        'restaurant_name'    => 'The Sarovar Court',
        'tagline'            => 'Experience the finest flavors of India in the heart of Rourkela',
        'about_text_1'       => 'Nestled in the vibrant city of Rourkela, Sarovar Restaurant has been a beacon of authentic Indian cuisine for over 15 years.',
        'about_text_2'       => 'Our master chefs bring decades of experience, crafting each dish with the finest locally sourced ingredients.',
        'established_year'   => '2009',
        'years_experience'   => '15+',
        'phone_primary'      => '+91 98765 43210',
        'phone_secondary'    => '+91 06612 123456',
        'email_primary'      => 'info@sarovarrourkela.com',
        'email_reservations' => 'reservations@sarovarrourkela.com',
        'address_line1'      => 'Ispat Market, Ambagan Circle',
        'address_line2'      => 'Bank Street, Sector 19',
        'address_city'       => 'Rourkela',
        'address_state'      => 'Odisha',
        'address_pincode'    => '769005',
        'hours_weekday'      => 'Monday – Sunday',
        'hours_open'         => '11:00 AM',
        'hours_close'        => '11:00 PM',
        'hours_note'         => 'Open all days including public holidays',
        'social_facebook'    => '#',
        'social_instagram'   => '#',
        'social_twitter'     => '#',
        'social_whatsapp'    => '#',
        'social_youtube'     => '#',
        'meta_description'   => 'The Sarovar Court – Authentic Multicuisine Restaurant in Rourkela.',
        'google_maps_embed'  => '',
    ];

    foreach ($defaults as $key => $val) {
        if (!isset($settings[$key]) || $settings[$key] === '') {
            $settings[$key] = $val;
        }
    }

    return $settings;
}

function setting(string $key, string $default = ''): string {
    $s = getSettings();
    return htmlspecialchars($s[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}

function settingRaw(string $key, string $default = ''): string {
    $s = getSettings();
    return $s[$key] ?? $default;
}
?>
