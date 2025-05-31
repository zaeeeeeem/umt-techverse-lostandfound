<?php
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'claims' => []];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];
$type = isset($_GET['type']) ? $_GET['type'] : ''; // 'my_claims', 'claims_on_my_items', or 'all_pending_for_admin'

$sql = "";
$params = []; // Params will vary based on type
$types = "";

if ($type === 'my_claims') {
    // Claims made by the current user
    $sql = "SELECT c.*, i.name as item_name, i.image_url, i.type as item_type, finder_u.name as finder_name, finder_u.email as finder_email
            FROM claims c
            JOIN items i ON c.item_id = i.id
            JOIN users finder_u ON i.user_id = finder_u.id
            WHERE c.claimer_id = ? ORDER BY c.created_at DESC";
    $params = [$user_id];
    $types = "i";
} elseif ($type === 'claims_on_my_items') {
    // Claims on items posted by the current user
    $sql = "SELECT c.*, i.name as item_name, i.image_url, i.type as item_type, claimer_u.name as claimer_name, claimer_u.email as claimer_email
            FROM claims c
            JOIN items i ON c.item_id = i.id
            JOIN users claimer_u ON c.claimer_id = claimer_u.id
            WHERE i.user_id = ? ORDER BY c.created_at DESC";
    $params = [$user_id];
    $types = "i";
} elseif ($type === 'all_pending_for_admin') {
    // For admin dashboard: get ALL pending claims in the system
    $sql = "SELECT c.*, i.name as item_name, i.image_url, i.type as item_type,
                   finder_u.name as finder_name, finder_u.email as finder_email,
                   claimer_u.name as claimer_name, claimer_u.email as claimer_email
            FROM claims c
            JOIN items i ON c.item_id = i.id
            JOIN users finder_u ON i.user_id = finder_u.id  -- Join to get finder's details
            JOIN users claimer_u ON c.claimer_id = claimer_u.id -- Join to get claimer's details
            WHERE c.claim_status = 'pending' ORDER BY c.created_at DESC";
    // No parameters needed for this specific query as it's not user-specific
} else {
    $response['message'] = 'Invalid claim type specified.';
    echo json_encode($response);
    exit;
}

if ($stmt = mysqli_prepare($conn, $sql)) {
    if (!empty($params)) { // Only bind parameters if $params array is not empty
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $claims = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $claims[] = $row;
        }
        $response['success'] = true;
        $response['claims'] = $claims;
    } else {
        $response['message'] = 'Error fetching claims: ' . mysqli_error($conn) . ' SQL: ' . $sql . ' Types: ' . $types . ' Params: ' . json_encode($params); // Added debug info
    }
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'Database query preparation failed: ' . mysqli_error($conn) . ' SQL: ' . $sql; // Added debug info
}

echo json_encode($response);
mysqli_close($conn);
?>