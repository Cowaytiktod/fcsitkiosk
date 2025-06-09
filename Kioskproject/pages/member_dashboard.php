<?php
// pages/member_dashboard.php

// This check should be at the top of any member-only page
if (!isLoggedIn()) { // isLoggedIn() should be from functions.php
    redirect("index.php?page=login&message=login_required"); // redirect() from functions.php
}
?>
<h2>Member Dashboard</h2>
<p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Member'); ?>!</p>

<?php
// Display any success or error messages passed via session
if (isset($_SESSION['success_message'])) {
    echo '<p class="success-message">' . htmlspecialchars($_SESSION['success_message']) . '</p>';
    unset($_SESSION['success_message']); // Clear message after displaying
}
if (isset($_SESSION['error_message'])) {
    echo '<p class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '</p>';
    unset($_SESSION['error_message']); // Clear message after displaying
}
?>

<ul>
    <li><a href="<?php echo SITE_URL; ?>index.php?page=edit_profile">Edit Profile</a></li>
    <li><a href="<?php echo SITE_URL; ?>index.php?page=my_bookings">View My Bookings</a></li>
    <li><a href="<?php echo SITE_URL; ?>index.php?page=browse_workshops">Browse More Workshops</a></li>
    <li><a href="<?php echo SITE_URL; ?>actions/logout_action.php">Logout</a></li>
</ul>

<p><em>Further features like dummy transaction checkout, receipts, and email notifications would be built out from here or integrated with the workshop booking flow.</em></p>