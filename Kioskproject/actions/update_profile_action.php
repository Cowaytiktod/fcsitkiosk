<?php
// actions/update_profile_action.php
require_once __DIR__ . '/../db.php'; // For $conn and session_start()
require_once __DIR__ . '/../includes/functions.php'; // For redirect()

if (!isLoggedIn()) {
    redirect("index.php?page=login&message=auth_required");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $fullname = sanitize_input($_POST['fullname']); // sanitize_input from functions.php
    $email = sanitize_input($_POST['email']);

    // Fields for password change
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    $errors = [];

    // --- Validate Full Name and Email ---
    if (empty($fullname)) {
        $errors[] = "Full name is required.";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $fullname)) {
        $errors[] = "Full name can only contain letters and spaces.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Check if email (if changed) already exists for another user
        $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt_check_email->bind_param("si", $email, $user_id);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();
        if ($stmt_check_email->num_rows > 0) {
            $errors[] = "This email address is already in use by another account.";
        }
        $stmt_check_email->close();
    }

    // --- Password Change Logic ---
    $password_updated = false;
    $update_password_sql_part = "";
    $password_params = [];
    $password_types = "";

    if (!empty($new_password)) { // User wants to change password
        if (empty($current_password)) {
            $errors[] = "Current password is required to set a new password.";
        } else {
            // Verify current password
            $stmt_curr_pass = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt_curr_pass->bind_param("i", $user_id);
            $stmt_curr_pass->execute();
            $result_curr_pass = $stmt_curr_pass->get_result();
            $user_row = $result_curr_pass->fetch_assoc();
            $stmt_curr_pass->close();

            if (!$user_row || !password_verify($current_password, $user_row['password'])) {
                $errors[] = "Incorrect current password.";
            }
        }

        // Validate new password
        if (!preg_match("/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,8}$/", $new_password) || strpos($new_password, ' ') !== false) {
            $errors[] = "New password must be 6-8 characters, include ONE uppercase, ONE number, ONE special character, and NO spaces.";
        } elseif ($new_password !== $confirm_new_password) {
            $errors[] = "New passwords do not match.";
        }

        if (empty($errors)) { // Only hash and prepare for update if no password validation errors so far
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_password_sql_part = ", password = ?";
            $password_params[] = $hashed_new_password;
            $password_types .= "s";
            $password_updated = true;
        }
    }
    // --- End Password Change Logic ---

    if (!empty($errors)) {
        $_SESSION['error_message'] = implode("<br>", $errors);
        redirect("index.php?page=edit_profile");
    }

    // --- Prepare to Update User Data ---
    $sql_update = "UPDATE users SET full_name = ?, email = ?" . $update_password_sql_part . " WHERE id = ?";
    
    $all_params = array_merge([$fullname, $email], $password_params, [$user_id]);
    $all_types = "ss" . $password_types . "i";

    $stmt_update = $conn->prepare($sql_update);
    if ($stmt_update) {
        // The "..." unpacks the $all_params array into individual arguments
        $stmt_update->bind_param($all_types, ...$all_params);

        if ($stmt_update->execute()) {
            $_SESSION['success_message'] = "Profile updated successfully!";
            // Update session variables if they changed
            $_SESSION['user_name'] = $fullname;
            // In a real system, if email changes, you might trigger re-verification and not update session email immediately
            $_SESSION['user_email'] = $email;
            if ($password_updated) {
                 $_SESSION['success_message'] .= " Your password has been changed.";
            }
        } else {
            $_SESSION['error_message'] = "Error updating profile: " . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        $_SESSION['error_message'] = "Database error preparing profile update: " . $conn->error;
    }
    redirect("index.php?page=edit_profile"); // Redirect back to edit profile page to show messages

} else {
    // Not a POST request, redirect away
    redirect("index.php?page=member_dashboard");
}
?>