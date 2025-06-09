<?php
// db.php - Should be in the root of your project (e.g., C:\xampp\htdocs\ProjectPHP\db.php)

require_once 'config.php'; // Loads DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME

// Create connection
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // This die() will stop all script execution if the connection fails.
    // You will see this message directly on the page.
    die("Connection failed: " . $conn->connect_error . "<br>Please check your database credentials in config.php and ensure your MySQL server is running.");
}

// Start sessions if not already started.
// It's often good practice to centralize session_start().
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Optional: You can set character set for the connection if needed, e.g., for UTF-8
// if (!$conn->set_charset("utf8mb4")) {
//     error_log("Error loading character set utf8mb4: " . $conn->error);
// }
?>