<?php
if (!isAdmin()) {
    $_SESSION['error_message'] = "Access denied. Admins only.";
    redirect("index.php?page=home"); // or login
}
?>
<h2>Admin Dashboard</h2>
<p>Welcome, Administrator <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
<ul>
    <li><a href="<?php echo SITE_URL; ?>index.php?page=admin_manage_workshops">Manage Workshops (CRUD)</a></li>
    <li><a href="<?php echo SITE_URL; ?>index.php?page=admin_manage_users">Manage Users (Read Accounts)</a></li>
    <li><a href="<?php echo SITE_URL; ?>index.php?page=admin_transactions">View Transaction Summary & Reports</a></li>
</ul>
<!-- TODO: Implement full admin features, data visualization -->