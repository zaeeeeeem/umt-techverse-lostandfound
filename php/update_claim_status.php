<?php
// php/update_claim_status.php
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Authentication required.';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action_type = $_POST['action_type'] ?? ''; // 'claim_status' or 'item_status'
    $status = $_POST['status'] ?? ''; // 'approved', 'rejected', 'resolved', 'archived'

    if ($action_type === 'claim_status') {
        $claim_id = (int)$_POST['claim_id'];
        
        // Fetch claim details to verify ownership/permission
        $sql_claim_check = "SELECT c.item_id, i.user_id as finder_id FROM claims c JOIN items i ON c.item_id = i.id WHERE c.id = ?";
        if ($stmt_claim_check = mysqli_prepare($conn, $sql_claim_check)) {
            mysqli_stmt_bind_param($stmt_claim_check, "i", $claim_id);
            mysqli_stmt_execute($stmt_claim_check);
            $result_claim_check = mysqli_stmt_get_result($stmt_claim_check);
            $claim_data = mysqli_fetch_assoc($result_claim_check);
            mysqli_stmt_close($stmt_claim_check);

            if (!$claim_data) {
                $response['message'] = 'Claim not found.';
                echo json_encode($response);
                exit;
            }

            // Only the finder of the item or an admin can update claim status
            if ($claim_data['finder_id'] != $user_id && $user_role !== 'admin') {
                $response['message'] = 'You are not authorized to update this claim.';
                echo json_encode($response);
                exit;
            }

            // Update claim status
            $sql_update_claim = "UPDATE claims SET claim_status = ? WHERE id = ?";
            if ($stmt_update = mysqli_prepare($conn, $sql_update_claim)) {
                mysqli_stmt_bind_param($stmt_update, "si", $status, $claim_id);
                if (mysqli_stmt_execute($stmt_update)) {
                    $response['success'] = true;
                    $response['message'] = 'Claim status updated to ' . $status . '.';

                    // If claim is approved, mark the item as 'resolved'
                    $sql_update_item_status = null;
                    if ($status === 'approved') {
                        $sql_update_item_status = "UPDATE items SET status = 'resolved' WHERE id = ?";
                    } elseif ($status === 'rejected') {
                        // If claim is rejected, set the item status back to 'active'
                        $sql_update_item_status = "UPDATE items SET status = 'active' WHERE id = ?";
                    }

                    if ($sql_update_item_status) { // Only execute if an update query is set
                        if ($stmt_item_status = mysqli_prepare($conn, $sql_update_item_status)) {
                            mysqli_stmt_bind_param($stmt_item_status, "i", $claim_data['item_id']);
                            mysqli_stmt_execute($stmt_item_status);
                            mysqli_stmt_close($stmt_item_status);
                        }
                    }
                } else {
                    $response['message'] = 'Failed to update claim status: ' . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt_update);
            } else {
                $response['message'] = 'Claim status update preparation failed: ' . mysqli_error($conn);
            }

    }
    } elseif ($action_type === 'item_status') {
        // Only admin can update item status directly (e.g., mark as archived/resolved)
        if ($user_role !== 'admin') {
            $response['message'] = 'You are not authorized to update item status directly.';
            echo json_encode($response);
            exit;
        }

        $item_id = (int)$_POST['item_id'];
        
        $sql_update_item = "UPDATE items SET status = ? WHERE id = ?";
        if ($stmt_update = mysqli_prepare($conn, $sql_update_item)) {
            mysqli_stmt_bind_param($stmt_update, "si", $status, $item_id);
            if (mysqli_stmt_execute($stmt_update)) {
                $response['success'] = true;
                $response['message'] = 'Item status updated to ' . $status . '.';
            } else {
                $response['message'] = 'Failed to update item status: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $response['message'] = 'Item status update preparation failed: ' . mysqli_error($conn);
        }

    } else {
        $response['message'] = 'Invalid action type.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
mysqli_close($conn);
?>