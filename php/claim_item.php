<?php
// php/claim_item.php
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to claim an item.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_id = (int)$_POST['item_id'];
    $claimer_id = $_SESSION['user_id'];
    $proof_details = trim($_POST['proof_details']);

    if (empty($proof_details)) {
        $response['message'] = 'Proof details are required to submit a claim.';
        echo json_encode($response);
        exit;
    }

    // First, check if the item exists and is 'found' and 'active'
    $sql_check_item = "SELECT type, status, user_id FROM items WHERE id = ?";
    if ($stmt_check = mysqli_prepare($conn, $sql_check_item)) {
        mysqli_stmt_bind_param($stmt_check, "i", $item_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $item_data = mysqli_fetch_assoc($result_check);
        mysqli_stmt_close($stmt_check);

        if (!$item_data || $item_data['type'] !== 'found' || $item_data['status'] !== 'active') {
            $response['message'] = 'This item cannot be claimed or is not a found item.';
            echo json_encode($response);
            exit;
        }

        // Prevent claiming your own found item
        if ($item_data['user_id'] == $claimer_id) {
            $response['message'] = 'You cannot claim an item you posted as found.';
            echo json_encode($response);
            exit;
        }

        // Check if a claim already exists from this user for this item (to prevent duplicate claims)
        $sql_check_claim = "SELECT id FROM claims WHERE item_id = ? AND claimer_id = ?";
        if ($stmt_check_claim = mysqli_prepare($conn, $sql_check_claim)) {
            mysqli_stmt_bind_param($stmt_check_claim, "ii", $item_id, $claimer_id);
            mysqli_stmt_execute($stmt_check_claim);
            mysqli_stmt_store_result($stmt_check_claim);
            if (mysqli_stmt_num_rows($stmt_check_claim) > 0) {
                $response['message'] = 'You have already submitted a claim for this item.';
                echo json_encode($response);
                exit;
            }
            mysqli_stmt_close($stmt_check_claim);
        }

        // Insert the claim request
        $sql_insert_claim = "INSERT INTO claims (item_id, claimer_id, proof_details, claim_status) VALUES (?, ?, ?, 'pending')";
        if ($stmt_insert = mysqli_prepare($conn, $sql_insert_claim)) {
            mysqli_stmt_bind_param($stmt_insert, "iis", $item_id, $claimer_id, $proof_details);
            if (mysqli_stmt_execute($stmt_insert)) {
                // Update item status to 'claimed' in the items table
                $sql_update_item = "UPDATE items SET status = 'claimed' WHERE id = ?";
                if ($stmt_update = mysqli_prepare($conn, $sql_update_item)) {
                    mysqli_stmt_bind_param($stmt_update, "i", $item_id);
                    mysqli_stmt_execute($stmt_update); // Execute without checking success for speed
                    mysqli_stmt_close($stmt_update);
                }
                $response['success'] = true;
                $response['message'] = 'Claim request submitted successfully! The item owner will be notified.';
            } else {
                $response['message'] = 'Failed to submit claim: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            $response['message'] = 'Claim insertion preparation failed: ' . mysqli_error($conn);
        }
    } else {
        $response['message'] = 'Item check preparation failed: ' . mysqli_error($conn);
    }

} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
mysqli_close($conn);
?>