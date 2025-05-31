<?php
error_reporting(E_ALL); // TEMPORARY: Ensure this line is present
ini_set('display_errors', 1); // TEMPORARY: Ensure this line is present
// php/send_message.php
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Authentication required.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $claim_id = (int)$_POST['claim_id'];
    $sender_id = $_SESSION['user_id'];
    $message_text = trim($_POST['message_text']);

    if (empty($message_text)) {
        $response['message'] = 'Message cannot be empty.';
        echo json_encode($response);
        exit;
    }

    // Optional: Verify that the claim exists and is approved, and that sender_id is part of this claim
    // For speed, we'll assume valid claim_id and sender_id for now, but in production, this is critical.
    $sql_check_claim = "SELECT id FROM claims WHERE id = ? AND claim_status = 'approved'";
    if($stmt_check = mysqli_prepare($conn, $sql_check_claim)){
        mysqli_stmt_bind_param($stmt_check, "i", $claim_id);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        if(mysqli_stmt_num_rows($stmt_check) == 0){
            $response['message'] = 'Invalid or unapproved claim.';
            echo json_encode($response);
            exit;
        }
        mysqli_stmt_close($stmt_check);
    } else {
        $response['message'] = 'Database check failed.';
        echo json_encode($response);
        exit;
    }


    $sql = "INSERT INTO messages (claim_id, sender_id, message_text) VALUES (?, ?, ?)";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "iis", $claim_id, $sender_id, $message_text);
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'Message sent.';
        } else {
            $response['message'] = 'Failed to send message: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['message'] = 'Database query preparation failed: ' . mysqli_error($conn);
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
mysqli_close($conn);
?>