<?php
if (!isLoggedIn()) {
    redirect("index.php?page=login&message=login_required");
}
?>
<h2>Member Dashboard</h2>
<p>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
<ul>
    <li><a href="#">Edit Profile (TODO)</a></li>
    <li><a href="#">View My Bookings (TODO)</a></li>
    <li><a href="<?php echo SITE_URL; ?>index.php?page=browse_workshops">Browse More Workshops</a></li>
</ul>
<!-- TODO: Implement profile editing, view transaction details, checkout with dummy transactions, payment receipt, email notification -->