<?php
// includes/header.php

// These paths assume config.php and db.php are in the parent directory (project root)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';   // This is what makes $conn available
require_once __DIR__ . '/functions.php'; // This should define isLoggedIn() and isAdmin()

// MODIFICATION 1: Define $body_class for basic logged-in state hint for JS
$body_class = '';
if (function_exists('isLoggedIn') && isLoggedIn()) { // Check if function exists before calling
    $body_class = 'logged-in';
}
// END OF MODIFICATION 1
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FCSIT KIOSK</title>
    <link rel="stylesheet" href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>css/style.css">
</head>
<body class="<?php echo $body_class; ?>">
    <header>
        <h1>FCSIT Kiosk</h1>
        <nav>
            <ul>
                <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>index.php?page=home">Home</a></li>
                <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>index.php?page=browse_workshops">Browse Workshops</a></li>
                <li><a href="#" id="viewCartBtn">View Cart (<span id="cartItemCount">0</span>)</a></li>
                

                <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                    <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>index.php?page=member_dashboard">Dashboard</a></li>
                    <?php if (function_exists('isAdmin') && isAdmin()): ?>
                        <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>index.php?page=admin_dashboard">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>actions/logout_action.php">Logout (<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>)</a></li>
                <?php else: ?>
                    <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>index.php?page=login">Login</a></li>
                    <li><a href="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>index.php?page=register">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <div class="container">
        <div class="main-content">