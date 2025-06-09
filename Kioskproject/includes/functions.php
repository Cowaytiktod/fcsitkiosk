<?php
// Basic input sanitizer (YOU NEED MORE ROBUST SANITIZATION)
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data); // Basic XSS protection
    return $data;
}

// Redirect function
function redirect($url_path) { // Path relative to SITE_URL
    header("Location: " . SITE_URL . $url_path);
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return (isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}
?>