<?php
// api/shipping.php
// Shipping / Delivery Abstraction Layer
// ──────────────────────────────────────
// Single place to manage all delivery logic.
// To add distance-based or zone-based pricing later,
// just update the calculate() function — nothing else changes.

require_once '../config/database.php';
require_once '../config/settings.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'config':    getShippingConfig();    break;  // Frontend loads this on init
    case 'calculate': calculateDelivery();   break;  // Called on cart change
    default:
        echo json_encode(['error' => 'Invalid action']);
}

/* ─────────────────────────────────────────────────────────────
   GET SHIPPING CONFIG
   Returns all shipping settings to the frontend (safe — no secrets)
──────────────────────────────────────────────────────────── */
function getShippingConfig() {
    $s = getSettings();
    echo json_encode([
        'method'            => $s['shipping_method']       ?? 'delivery',
        'delivery_enabled'  => ($s['delivery_enabled']     ?? '1') === '1',
        'pickup_enabled'    => ($s['pickup_enabled']       ?? '0') === '1',
        'charge_type'       => $s['delivery_charge_type']  ?? 'flat',
        'flat_rate'         => floatval($s['delivery_flat_rate']   ?? 40),
        'free_above'        => floatval($s['delivery_free_above']  ?? 500),
        'per_km_rate'       => floatval($s['delivery_per_km_rate'] ?? 10),
        'min_order'         => floatval($s['delivery_min_order']   ?? 100),
        'time_min'          => intval($s['delivery_time_min']      ?? 30),
        'time_max'          => intval($s['delivery_time_max']      ?? 60),
        'pickup_time'       => intval($s['pickup_time']            ?? 15),
        'delivery_message'  => $s['delivery_message']              ?? '',
    ]);
}

/* ─────────────────────────────────────────────────────────────
   CALCULATE DELIVERY CHARGE
   Called from checkout with the current cart subtotal
   Returns: charge amount + label + estimated time
──────────────────────────────────────────────────────────── */
function calculateDelivery() {
    $s           = getSettings();
    $subtotal    = floatval($_POST['subtotal']      ?? 0);
    $method      = trim($_POST['method']            ?? 'delivery'); // delivery or pickup
    $chargeType  = $s['delivery_charge_type']       ?? 'flat';
    $flatRate    = floatval($s['delivery_flat_rate']   ?? 40);
    $freeAbove   = floatval($s['delivery_free_above']  ?? 500);
    $minOrder    = floatval($s['delivery_min_order']   ?? 0);
    $timeMin     = intval($s['delivery_time_min']      ?? 30);
    $timeMax     = intval($s['delivery_time_max']      ?? 60);
    $pickupTime  = intval($s['pickup_time']            ?? 15);

    // ── Pickup: always free ──────────────────────────────────
    if ($method === 'pickup') {
        echo json_encode([
            'success'        => true,
            'method'         => 'pickup',
            'charge'         => 0,
            'charge_label'   => 'Free (Self Pickup)',
            'estimated_time' => "{$pickupTime} mins",
            'message'        => 'Ready for pickup in ' . $pickupTime . ' minutes',
        ]);
        return;
    }

    // ── Minimum order check ──────────────────────────────────
    if ($minOrder > 0 && $subtotal < $minOrder) {
        echo json_encode([
            'success'      => false,
            'error'        => 'Minimum order of ₹' . number_format($minOrder, 0) . ' required for delivery',
            'min_order'    => $minOrder,
            'shortage'     => $minOrder - $subtotal,
        ]);
        return;
    }

    // ── Calculate charge based on type ──────────────────────
    $charge      = 0;
    $chargeLabel = '';
    $promoMsg    = '';

    switch ($chargeType) {

        case 'free_above':
            if ($subtotal >= $freeAbove) {
                $charge      = 0;
                $chargeLabel = 'Free Delivery 🎉';
                $promoMsg    = 'You got free delivery!';
            } else {
                $charge      = $flatRate;
                $chargeLabel = '₹' . number_format($flatRate, 0);
                $remaining   = $freeAbove - $subtotal;
                $promoMsg    = 'Add ₹' . number_format($remaining, 0) . ' more for FREE delivery!';
            }
            break;

        case 'per_km':
            // Placeholder — distance-based can be wired in later
            // For now uses flat rate until GPS/distance integration is added
            $charge      = $flatRate;
            $chargeLabel = '₹' . number_format($flatRate, 0);
            $promoMsg    = 'Distance-based pricing coming soon';
            break;

        case 'flat':
        default:
            $charge      = $flatRate;
            $chargeLabel = $charge > 0 ? '₹' . number_format($charge, 0) : 'Free';
            break;
    }

    echo json_encode([
        'success'        => true,
        'method'         => 'delivery',
        'charge'         => $charge,
        'charge_label'   => $chargeLabel,
        'estimated_time' => "{$timeMin}–{$timeMax} mins",
        'promo_message'  => $promoMsg,
        'free_above'     => $freeAbove,
        'subtotal'       => $subtotal,
    ]);
}
?>
