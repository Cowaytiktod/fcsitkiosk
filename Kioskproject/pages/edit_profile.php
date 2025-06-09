<?php
// pages/edit_profile.php
global $conn; // Make database connection available

if (!isLoggedIn()) {
    redirect("index.php?page=login&message=login_required_for_profile");
}

$user_id = $_SESSION['user_id']; // Get user ID from session

// Fetch current user data to pre-fill the form
$current_user_data = null;
$stmt_fetch = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
if ($stmt_fetch) {
    $stmt_fetch->bind_param("i", $user_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    if ($result->num_rows === 1) {
        $current_user_data = $result->fetch_assoc();
    } else {
        // Should not happen for a logged-in user if session ID is valid
        $_SESSION['error_message'] = "Could not retrieve your profile data.";
        redirect("index.php?page=member_dashboard");
    }
    $stmt_fetch->close();
} else {
    $_SESSION['error_message'] = "Database error fetching profile.";
    redirect("index.php?page=member_dashboard");
}

if (!$current_user_data) {
    // Failsafe if data somehow isn't fetched
    echo "<p>Error loading profile. Please try again.</p>";
    return; // Stop further execution of this page
}
?>

<h2>Edit Your Profile</h2>

<?php
// Display any success or error messages from profile update attempt
if (isset($_SESSION['success_message'])) {
    echo '<p class="success-message">' . htmlspecialchars($_SESSION['success_message']) . '</p>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<p class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '</p>';
    unset($_SESSION['error_message']);
}
?>

<form action="<?php echo SITE_URL; ?>actions/update_profile_action.php" method="POST" class="profile-form">
    <div class="form-group">
        <label for="fullname">Full Name:</label>
        <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($current_user_data['full_name']); ?>" required>
    </div>
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_user_data['email']); ?>" required>
        <small>Changing your email will require re-verification in a real system.</small>
    </div>
    <hr>
    <p><strong>Change Password (leave blank if you don't want to change):</strong></p>
    <div class="form-group">
        <label for="current_password">Current Password (required to change password):</label>
        <input type="password" id="current_password" name="current_password">
    </div>
    <div class="form-group">
        <label for="new_password">New Password (6-8 chars, 1 Upper, 1 Num, 1 Special, no space):</label>
        <input type="password" id="new_password" name="new_password"
               pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,8}$"
               title="Password must be 6-8 characters, include ONE uppercase, ONE number, ONE special character (!@#$%^&*) and no spaces.">
    </div>
    <div class="form-group">
        <label for="confirm_new_password">Confirm New Password:</label>
        <input type="password" id="confirm_new_password" name="confirm_new_password">
    </div>

    <div class="form-group">
        <input type="submit" value="Update Profile" class="btn-submit">
        <a href="<?php echo SITE_URL; ?>index.php?page=member_dashboard" class="btn-cancel">Cancel</a>
    </div>
</form>

<style>
    /* Quick inline styles for this form - move to style.css for better organization */
    .profile-form .form-group { margin-bottom: 15px; }
    .profile-form label { display: block; margin-bottom: 5px; font-weight: bold; }
    .profile-form input[type="text"],
    .profile-form input[type="email"],
    .profile-form input[type="password"] {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }
    .profile-form input[type="text"]:focus,
    .profile-form input[type="email"]:focus,
    .profile-form input[type="password"]:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
    .profile-form .btn-submit {
        background-color: #28a745; /* Green */
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .profile-form .btn-submit:hover { background-color: #218838; }
    .profile-form .btn-cancel {
        display: inline-block;
        margin-left: 10px;
        padding: 10px 15px;
        background-color: #6c757d; /* Gray */
        color: white;
        text-decoration: none;
        border-radius: 4px;
    }
    .profile-form .btn-cancel:hover { background-color: #5a6268; }
    .success-message { color: green; border: 1px solid green; padding: 10px; margin-bottom:15px; background-color: #e6ffe6;}
    .error-message { color: red; border: 1px solid red; padding: 10px; margin-bottom:15px; background-color: #ffe6e6;}
</style>