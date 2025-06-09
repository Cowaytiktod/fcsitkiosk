<?php
// Start the session if it hasn't been started already.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * =================================================================
 * DATABASE CONFIGURATION FOR YOUR LIVE BYETHOST SERVER
 * =================================================================
 * You MUST get these values from your CPanel's "MySQL Databases" area.
 */

// 1. Database Host/Server
// This is NEVER 'localhost' on ByetHost. Find it in your CPanel. It looks like 'sqlXXX.byethost.com'.
define('DB_SERVER', 'sql306.byethost13.com'); // <-- IMPORTANT: Replace with your actual SQL Hostname

// 2. Database Username
// This is your ByetHost username prefixed to your database user. Looks like 'b13_39189358_user'.
define('DB_USERNAME', 'b13_39189358'); // <-- IMPORTANT: Replace with your full DB Username

// 3. Database Password
// The password you set for the database user in CPanel.
define('DB_PASSWORD', 'Fcsitkiosk00'); // <-- IMPORTANT: Replace with your DB password

// 4. Database Name
// This is your ByetHost username prefixed to your database name. Looks like 'b13_39189358_skillshare_hub_db'.
define('DB_NAME', 'b13_39189358_skillshare_hub_db'); // <-- IMPORTANT: Replace with your full DB Name


/**
 * =================================================================
 * SITE CONFIGURATION
 * =================================================================
 */

// Your live website's URL. Do not use 'localhost'.
define('SITE_URL', 'https://fcsitkiosk.byethost13.com/');


/**
 * =================================================================
 * DATABASE CONNECTION
 * =================================================================
 * This part uses the constants defined above to connect.
 */

// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check the connection and stop the script if it fails.
if ($conn->connect_error) {
    // This message will be shown if the credentials above are wrong.
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set the character set for proper data handling.
$conn->set_charset("utf8mb4");

// The closing ?>