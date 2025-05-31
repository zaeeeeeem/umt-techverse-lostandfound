<?php
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'items' => []];

$item_type = isset($_GET['type']) ? $_GET['type'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$location_filter = isset($_GET['location']) ? $_GET['location'] : '';
$user_id_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$show_all = isset($_GET['show_all']) && $_GET['show_all'] === 'true'; // Parameter for admin/full listings

// Status filter for admin dashboard
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

$where_clauses = ["1=1"]; // Start with a true condition
$params = [];
$types = "";

if (!empty($item_type)) {
    $where_clauses[] = "i.type = ?";
    $params[] = $item_type;
    $types .= "s";
}
if (!empty($category_filter)) {
    $where_clauses[] = "i.category LIKE ?";
    $params[] = "%" . $category_filter . "%";
    $types .= "s";
}
if (!empty($location_filter)) {
    $where_clauses[] = "i.location LIKE ?";
    $params[] = "%" . $location_filter . "%";
    $types .= "s";
}

// Logic for user-specific dashboards vs. public listings vs. admin 'show all'
if ($user_id_filter > 0) {
    // If filtering by a specific user (e.g., user dashboard)
    $where_clauses[] = "i.user_id = ?";
    $params[] = $user_id_filter;
    $types .= "i";
} elseif (!$show_all) {
    // Public listings (not admin, not specific user): only show active or claimed
    $where_clauses[] = "(i.status = 'active' OR i.status = 'claimed')";
}

// Apply status filter specifically for admin dashboard (when show_all is true)
if ($show_all && !empty($status_filter) && $status_filter !== 'all') {
    $where_clauses[] = "i.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql = "SELECT i.*, u.name as user_name FROM items i JOIN users u ON i.user_id = u.id WHERE " . implode(" AND ", $where_clauses);
$sql .= " ORDER BY i.created_at DESC"; // Order by most recent

if ($stmt = mysqli_prepare($conn, $sql)) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $items = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
        $response['success'] = true;
        $response['items'] = $items;
    } else {
        $response['message'] = 'Error fetching items: ' . mysqli_error($conn) . ' SQL: ' . $sql . ' Types: ' . $types . ' Params: ' . json_encode($params); // Added debug info
    }
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'Database query preparation failed: ' . mysqli_error($conn) . ' SQL: ' . $sql; // Added debug info
}

echo json_encode($response);
mysqli_close($conn);
?>