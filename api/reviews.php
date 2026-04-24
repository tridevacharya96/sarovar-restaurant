<?php
// api/reviews.php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/settings.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'submit':     submitReview();    break;
    case 'get':        getReviews();      break;
    case 'summary':    getSummary();      break;
    case 'google':     getGoogleReviews(); break;
    default: echo json_encode(['error' => 'Invalid action']);
}

/* ============================================================ SUBMIT REVIEW */
function submitReview() {
    $conn   = getConnection();
    $name   = trim($_POST['name']        ?? '');
    $email  = trim($_POST['email']       ?? '');
    $rating = intval($_POST['rating']    ?? 0);
    $text   = trim($_POST['review_text'] ?? '');
    $userId = $_SESSION['user_id']       ?? null;

    if (empty($name))              { echo json_encode(['error' => 'Name is required']);         return; }
    if ($rating < 1 || $rating > 5){ echo json_encode(['error' => 'Please select a rating']);   return; }
    if (empty($text))              { echo json_encode(['error' => 'Please write a review']);     return; }
    if (strlen($text) < 10)        { echo json_encode(['error' => 'Review is too short (min 10 characters)']); return; }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid email address']); return;
    }

    // Rate limit: same email can't review more than once per 24 hrs
    if (!empty($email)) {
        $chk = $conn->prepare("SELECT id FROM reviews WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $chk->bind_param("s", $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            echo json_encode(['error' => 'You have already submitted a review recently. Thank you!']);
            return;
        }
    }

    $stmt = $conn->prepare("INSERT INTO reviews (user_id, name, email, rating, review_text, source, status) VALUES (?, ?, ?, ?, ?, 'website', 'pending')");
    $stmt->bind_param("issis", $userId, $name, $email, $rating, $text);

    if ($stmt->execute()) {
        $s = getSettings();
        $googleUrl    = $s['google_review_url']    ?? '';
        $showRedirect = ($s['review_redirect_google'] ?? '1') === '1';
        echo json_encode([
            'success'          => true,
            'message'          => 'Thank you for your review! It will appear after approval.',
            'google_url'       => $showRedirect ? $googleUrl : '',
            'show_google_prompt' => $showRedirect && !empty($googleUrl),
        ]);
    } else {
        echo json_encode(['error' => 'Failed to submit review. Please try again.']);
    }
    $conn->close();
}

/* ============================================================ GET REVIEWS */
function getReviews() {
    $conn   = getConnection();
    $s      = getSettings();
    $page   = max(1, intval($_GET['page'] ?? 1));
    $limit  = intval($s['reviews_per_page'] ?? 6);
    $offset = ($page - 1) * $limit;
    $source = $_GET['source'] ?? 'all'; // all / website / google

    $where = "WHERE r.status = 'approved'";
    if ($source === 'website') $where .= " AND r.source = 'website'";
    if ($source === 'google')  $where .= " AND r.source = 'google'";

    // Total count
    $total = $conn->query("SELECT COUNT(*) c FROM reviews r $where")->fetch_assoc()['c'];

    $q = $conn->query("
        SELECT r.id, r.name, r.rating, r.review_text, r.source,
               r.is_featured, r.admin_reply, r.created_at
        FROM reviews r
        $where
        ORDER BY r.is_featured DESC, r.created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    $reviews = [];
    while ($row = $q->fetch_assoc()) $reviews[] = $row;

    echo json_encode([
        'reviews'   => $reviews,
        'total'     => intval($total),
        'page'      => $page,
        'per_page'  => $limit,
        'has_more'  => ($offset + $limit) < $total,
    ]);
    $conn->close();
}

/* ============================================================ SUMMARY */
function getSummary() {
    $conn = getConnection();
    $q    = $conn->query("
        SELECT
            COUNT(*)                                    AS total,
            ROUND(AVG(rating), 1)                       AS avg_rating,
            SUM(rating = 5)                             AS five_star,
            SUM(rating = 4)                             AS four_star,
            SUM(rating = 3)                             AS three_star,
            SUM(rating = 2)                             AS two_star,
            SUM(rating = 1)                             AS one_star
        FROM reviews
        WHERE status = 'approved'
    ");
    $summary = $q->fetch_assoc();
    echo json_encode($summary);
    $conn->close();
}

/* ============================================================ GOOGLE REVIEWS
   Uses Google Places API to fetch your latest Google reviews.
   Requires:
   1. A Google Cloud project with Places API enabled
   2. API key added in Admin → Site Settings → Reviews
   3. Your Google Place ID added in Admin → Site Settings → Reviews

   HOW TO GET YOUR PLACE ID:
   Go to: https://developers.google.com/maps/documentation/places/web-service/place-id
   Search for "The Sarovar Court Rourkela" and copy the Place ID

   HOW TO GET AN API KEY:
   Go to: https://console.cloud.google.com
   Create project → Enable Places API → Create credentials → API Key
   Restrict it to your domain for security
*/
function getGoogleReviews() {
    $s        = getSettings();
    $placeId  = trim($s['google_place_id'] ?? '');
    $apiKey   = trim($s['google_api_key']  ?? '');

    if (empty($placeId) || empty($apiKey)) {
        echo json_encode(['reviews' => [], 'configured' => false,
            'message' => 'Add your Google Place ID and API key in Admin → Site Settings → Reviews']);
        return;
    }

    $url = "https://maps.googleapis.com/maps/api/place/details/json"
         . "?place_id=" . urlencode($placeId)
         . "&fields=rating,user_ratings_total,reviews"
         . "&reviews_sort=newest"
         . "&key=" . urlencode($apiKey);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response || $httpCode !== 200) {
        echo json_encode(['reviews' => [], 'error' => 'Could not fetch Google reviews']);
        return;
    }

    $data = json_decode($response, true);
    if (($data['status'] ?? '') !== 'OK') {
        echo json_encode(['reviews' => [], 'error' => 'Google API error: ' . ($data['status'] ?? 'Unknown')]);
        return;
    }

    $result  = $data['result'] ?? [];
    $reviews = [];

    foreach (($result['reviews'] ?? []) as $r) {
        // Only show 4 & 5 star Google reviews on website
        if (($r['rating'] ?? 0) >= 4) {
            $reviews[] = [
                'name'        => $r['author_name']          ?? 'Google User',
                'rating'      => $r['rating']               ?? 5,
                'review_text' => $r['text']                  ?? '',
                'source'      => 'google',
                'created_at'  => date('Y-m-d H:i:s', $r['time'] ?? time()),
                'photo_url'   => $r['profile_photo_url']    ?? '',
            ];
        }
    }

    echo json_encode([
        'reviews'            => $reviews,
        'configured'         => true,
        'place_rating'       => $result['rating']              ?? null,
        'total_ratings'      => $result['user_ratings_total']  ?? null,
        'google_review_url'  => $s['google_review_url']        ?? '',
    ]);
}
?>
