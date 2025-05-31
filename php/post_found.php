<?php
// php/post_found.php
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $item_type = 'found'; // Key difference from post_lost.php
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $date_lost_found = trim($_POST['date_lost_found']);

    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_name = uniqid() . '_' . basename($_FILES['image']['name']);
        $upload_dir = '../uploads/';
        $target_file = $upload_dir . $file_name;

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($file_tmp, $target_file)) {
            $image_url = 'uploads/' . $file_name;
        } else {
            $response['message'] = 'Failed to upload image.';
            echo json_encode($response);
            exit;
        }
    }

    // Simple validation
    if (empty($name) || empty($category) || empty($description) || empty($location) || empty($date_lost_found)) {
        $response['message'] = 'All fields are required.';
    } else {
        $sql = "INSERT INTO items (user_id, type, name, category, description, location, image_url, date_lost_found) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "isssssss", $param_user_id, $param_type, $param_name, $param_category, $param_description, $param_location, $param_image_url, $param_date_lost_found);

            $param_user_id = $user_id;
            $param_type = $item_type;
            $param_name = $name;
            $param_category = $category;
            $param_description = $description;
            $param_location = $location;
            $param_image_url = $image_url;
            $param_date_lost_found = $date_lost_found;

            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Found item reported successfully!';
            } else {
                $response['message'] = 'Error: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Database error: ' . mysqli_error($conn);
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
mysqli_close($conn);
?>