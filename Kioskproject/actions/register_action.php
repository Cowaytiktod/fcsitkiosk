<?php
require_once __DIR__ . '/../db.php'; // Establishes $conn
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = sanitize_input($_POST['fullname']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password']; // Don't sanitize password before hashing, but do validate complexity.
    $confirm_password = $_POST['confirm_password'];

    // --- BEGIN SERVER-SIDE VALIDATION (CRUCIAL - DO NOT RELY ON CLIENT-SIDE ONLY) ---
    $errors = [];

    // Fullname validation
    if (empty($fullname)) {
        $errors[] = "Full name is required.";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $fullname)) { // Allow letters and spaces
        $errors[] = "Full name can only contain letters and spaces.";
    }


    // Email validation
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Check if email already exists
        $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt_check_email->bind_param("s", $email);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();
        if ($stmt_check_email->num_rows > 0) {
            $errors[] = "Email already registered.";
        }
        $stmt_check_email->close();
    }

    // Password validation (as per your requirements)
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (!preg_match("/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,8}$/", $password) || strpos($password, ' ') !== false) {
        // Added strpos($password, ' ') !== false to explicitly check for spaces as pattern might not cover it.
        // Re-check pattern logic: Your original spec was "ONE uppercase, ONE numeric, ONE special character, number and no space" -
        // "number" is redundant if "numeric" is there. "6-8 digits" is more a length than a "digits only" constraint.
        // The pattern:
        // ^                   : Start of string
        // (?=.*[A-Z])        : Positive lookahead for at least one uppercase letter
        // (?=.*[0-9])        : Positive lookahead for at least one numeric digit
        // (?=.*[!@#$%^&*]) : Positive lookahead for at least one special character (adjust as needed)
        // [A-Za-z0-9!@#$%^&*]: Allowed characters
        // {6,8}               : Length between 6 and 8 characters
        // $                   : End of string
        // No space is implied by not including \s in the character set.
        $errors[] = "Password must be 6-8 characters, include ONE uppercase, ONE number, ONE special character, and NO spaces.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    // --- END SERVER-SIDE VALIDATION ---


    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'member'; // Default role

        // Use PREPARED STATEMENTS to prevent SQL Injection
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssss", $fullname, $email, $hashed_password, $role);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Registration successful! You can now login.";
                redirect("index.php?page=login");
            } else {
                $_SESSION['error_message'] = "Error: " . $stmt->error;
                redirect("index.php?page=register");
            }
            $stmt->close();
        } else {
             $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
             redirect("index.php?page=register");
        }
    } else {
        // Store errors in session and redirect back
        $_SESSION['error_message'] = implode("<br>", $errors);
        // You might also want to repopulate form fields using session data to improve UX
        redirect("index.php?page=register");
    }
} else {
    redirect("index.php?page=register");
}
?>