<h2>Register New Account</h2>
<?php
if (isset($_SESSION['error_message'])) {
    echo '<p class="error">' . $_SESSION['error_message'] . '</p>';
    unset($_SESSION['error_message']);
}
if (isset($_SESSION['success_message'])) {
    echo '<p class="success">' . $_SESSION['success_message'] . '</p>';
    unset($_SESSION['success_message']);
}
?>
<form id="registrationForm" action="<?php echo SITE_URL; ?>actions/register_action.php" method="POST">
    <div class="form-group">
        <label for="fullname">Full Name:</label>
        <input type="text" id="fullname" name="fullname" required>
    </div>
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
    </div>
    <div class="form-group">
        <label for="password">Password (6-8 chars, 1 Upper, 1 Num, 1 Special, no space):</label>
        <input type="password" id="password" name="password" required
               pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,8}$"
               title="Password must be 6-8 characters, include ONE uppercase, ONE number, ONE special character (!@#$%^&*) and no spaces.">
        <!-- Hidden password is a usability concern; generally show/hide toggle is better.
             But for your req "Hidden password", this input type handles it by default.
             For strict character count (6-8 digits total), this pattern enforces it too.
        -->
    </div>
    <div class="form-group">
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
    </div>
    <!-- TODO: Add any other necessary fields -->
    <div class="form-group">
        <input type="submit" value="Register">
    </div>
</form>