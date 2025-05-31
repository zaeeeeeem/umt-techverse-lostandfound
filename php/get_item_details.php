<?php
// php/get_item_details.php
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'item' => null];

if (!isset($_GET['id'])) {
    $response['message'] = 'Item ID is required.';
    echo json_encode($response);
    exit;
}

$item_id = (int)$_GET['id'];

$sql = "SELECT i.*, u.name as user_name, u.email as user_email FROM items i JOIN users u ON i.user_id = u.id WHERE i.id = ?";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $param_id);
    $param_id = $item_id;

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $response['success'] = true;
            $response['item'] = mysqli_fetch_assoc($result);
        } else {
            $response['message'] = 'Item not found.';
        }
    } else {
        $response['message'] = 'Error fetching item details: ' . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'Database query preparation failed: ' . mysqli_error($conn);
}

echo json_encode($response);
mysqli_close($conn);
?>