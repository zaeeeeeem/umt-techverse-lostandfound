<?php
// php/get_all_active_chats.php
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'chats' => []];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Authentication required.';
    echo json_encode($response);
    exit;
}

$current_user_id = $_SESSION['user_id'];

// SQL to fetch all approved claims where the current user is either the claimant or the finder
$sql = "SELECT 
            c.id AS claim_id,
            i.name AS item_name,
            i.image_url AS item_image_url,
            i.type AS item_type,
            c.claim_status,
            claimer_u.id AS claimer_id,
            claimer_u.name AS claimer_name,
            finder_u.id AS finder_id,
            finder_u.name AS finder_name,
            c.created_at AS claim_created_at
        FROM 
            claims c
        JOIN 
            items i ON c.item_id = i.id
        JOIN 
            users claimer_u ON c.claimer_id = claimer_u.id
        JOIN 
            users finder_u ON i.user_id = finder_u.id
        WHERE 
            c.claim_status = 'approved' 
            AND (c.claimer_id = ? OR i.user_id = ?)
        ORDER BY 
            c.created_at DESC";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $current_user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $chats = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Determine the other participant's name for display
            $other_participant_name = ($row['claimer_id'] == $current_user_id) ? $row['finder_name'] : $row['claimer_name'];
            $row['other_participant_name'] = $other_participant_name;

            $chats[] = $row;
        }
        $response['success'] = true;
        $response['chats'] = $chats;
    } else {
        $response['message'] = 'Error fetching active chats: ' . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'Database query preparation failed: ' . mysqli_error($conn);
}

echo json_encode($response);
mysqli_close($conn);
?>