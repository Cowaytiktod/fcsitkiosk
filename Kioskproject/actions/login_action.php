<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // Don't sanitize password before checking hash

    if (empty($email) || empty($password)) {
        $_SESSION['error_message'] = "Email and password are required.";
        redirect("index.php?page=login");
    }

    // Use PREPARED STATEMENTS
    $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Password is correct, start session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    redirect("index.php?page=admin_dashboard");
                } else {
                    redirect("index.php?page=member_dashboard");
                }
            } else {
                $_SESSION['error_message'] = "Invalid email or password.";
                redirect("index.php?page=login");
            }
        } else {
            $_SESSION['error_message'] = "Invalid email or password.";
            redirect("index.php?page=login");
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Login error. Please try again.";
        redirect("index.php?page=login");
    }
} else {
    redirect("index.php?page=login");
}
?>