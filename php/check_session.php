<?php
// php/check_session.php
session_start();
header('Content-Type: application/json');

$response = [
    'loggedIn' => isset($_SESSION['user_id']),
    'userId' => $_SESSION['user_id'] ?? null,
    'userName' => $_SESSION['user_name'] ?? null,
    'userRole' => $_SESSION['user_role'] ?? null
];

echo json_encode($response);
?>