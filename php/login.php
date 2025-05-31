<?php
// php/login.php
require_once 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $response['message'] = 'Please enter email and password.';
    } else {
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $id, $name, $email, $hashed_password, $role);
                    if (mysqli_stmt_fetch($stmt)) {
                        // For 5-hour demo, direct password comparison
                        if ($password === $hashed_password) {
                            $_SESSION['user_id'] = $id;
                            $_SESSION['user_name'] = $name;
                            $_SESSION['user_role'] = $role;
                            $response['success'] = true;
                            $response['message'] = 'Login successful!';
                            $response['redirect'] = ($role === 'admin') ? 'admin_dashboard.html' : 'user_dashboard.html'; // Redirect based on role
                        } else {
                            $response['message'] = 'The password you entered was not valid.';
                        }
                    }
                } else {
                    $response['message'] = 'No account found with that email.';
                }
            } else {
                $response['message'] = 'Oops! Something went wrong with login.';
            }
            mysqli_stmt_close($stmt);
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
mysqli_close($conn);
?>