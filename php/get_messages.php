<?php
error_reporting(E_ALL); // TEMPORARY: Ensure this line is present
ini_set('display_errors', 1); // TEMPORARY: Ensure this line is present
// php/get_messages.php
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'messages' => []];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Authentication required.';
    echo json_encode($response);
    exit;
}

$claim_id = (int)$_GET['claim_id'];
$current_user_id = $_SESSION['user_id'];

if (empty($claim_id)) {
    $response['message'] = 'Claim ID is required.';
    echo json_encode($response);
    exit;
}

// IMPORTANT: Verify user is part of this claim to access messages
// This is a crucial security step.
$sql_verify_claim_access = "SELECT c.id FROM claims c 
                            JOIN items i ON c.item_id = i.id
                            WHERE c.id = ? 
                              AND (c.claimer_id = ? OR i.user_id = ?) 
                              AND c.claim_status = 'approved'";
if ($stmt_verify = mysqli_prepare($conn, $sql_verify_claim_access)) {
    mysqli_stmt_bind_param($stmt_verify, "iii", $claim_id, $current_user_id, $current_user_id);
    mysqli_stmt_execute($stmt_verify);
    mysqli_stmt_store_result($stmt_verify);
    if (mysqli_stmt_num_rows($stmt_verify) == 0) {
        $response['message'] = 'Access denied to this chat.';
        echo json_encode($response);
        exit;
    }
    mysqli_stmt_close($stmt_verify);
} else {
    $response['message'] = 'Database verification failed.';
    echo json_encode($response);
    exit;
}

// Fetch messages
$sql = "SELECT m.*, u.name as sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.claim_id = ? ORDER BY m.created_at ASC";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $claim_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $messages = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $messages[] = $row;
        }
        $response['success'] = true;
        $response['messages'] = $messages;
    } else {
        $response['message'] = 'Error fetching messages: ' . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'Database query preparation failed: ' . mysqli_error($conn);
}

echo json_encode($response);
mysqli_close($conn);
?>