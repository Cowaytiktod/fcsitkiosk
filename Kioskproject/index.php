<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// index.php - Should be in the root of your project (e.g., C:\xampp\htdocs\ProjectPHP\index.php)

// STEP 1: Include the header.
// The header.php file will itself include config.php and db.php (which creates $conn).
require_once 'includes/header.php';

// STEP 2: Routing Logic - Determine which page content to include.
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Whitelist allowed pages to prevent Local File Inclusion (LFI) vulnerabilities.
$allowed_pages = [
    'home',
    'browse_workshops',
    'workshop_detail',
    'register',
    'login',
    'member_dashboard',
    'admin_dashboard',
    'admin_manage_workshops',
    'admin_manage_users',
    'admin_transactions',
    'admin_edit_workshop',
    'admin_add_workshop',
    'edit_profile',
    'my_bookings',
    'admin_delete_workshop', // cuba isi
    'admin_update_workshop', // cuba isi
    'cart',
    'checkout', 
    'booking_success',
    'view_receipt'    
    // Add any other page slugs here as you create them
];

if ($page === 'admin_delete_workshop') {
    $action_file = 'actions/admin_delete_workshop_action.php';
    if (file_exists($action_file)) {
        require_once $action_file;
        exit(); 
    } else {
        error_log("CRITICAL: Action file missing: " . $action_file);
        $_SESSION['error_message'] = "A required action file was not found. Please contact support.";
        redirect("index.php?page=admin_dashboard");
    }
    } elseif ($page === 'admin_update_workshop') { // <-- ADD THIS ELSEIF BLOCK
    $action_file = 'actions/admin_update_workshop_action.php';
    if (file_exists($action_file)) {
        require_once $action_file;
        exit(); 
    } else {
        error_log("CRITICAL: Action file missing: " . $action_file);
        $_SESSION['error_message'] = "A required action file was not found. Please contact support.";
        // Redirect to the manage page, or an edit page if an ID was somehow passed
        redirect("index.php?page=admin_manage_workshops"); 
    }
}

// STEP 3: Include the specific page content.
// The $conn variable established in db.php (via header.php) should be available
// to the included page file IF the page file uses `global $conn;`.
if (in_array($page, $allowed_pages) && file_exists("pages/{$page}.php")) {
    include "pages/{$page}.php";
} else {
    // If page is not allowed or doesn't exist, show home page or a 404 page.
    // For now, let's default to home if the specific page logic is not found.
    // Consider creating a specific pages/404.php for better user experience.
    if (file_exists("pages/home.php")) {
        include "pages/home.php";
    } else {
        echo "<p>Error: Page content not found and home page is missing.</p>";
    }
}

// STEP 4: Include the footer.
require_once 'includes/footer.php';
?>