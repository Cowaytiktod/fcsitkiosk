<?php
// session_start(); // REMOVE THIS LINE - Session should be started by index.php or config.php

// Correct paths relative to index.php (which includes this file)
require_once 'config.php';        // config.php is in the same directory as index.php
require_once 'includes/functions.php'; // functions.php is in includes/ under the same dir as index.php

// Ensure only admins can perform this action
if (!isAdmin()) {
    $_SESSION['error_message'] = "Access denied. Admins only.";
    redirect("index.php?page=login"); // This redirect is fine as it will use SITE_URL from functions.php
}

// Check if workshop ID is provided
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message'] = "Invalid or missing workshop ID.";
    redirect("index.php?page=admin_manage_workshops");
}

$workshop_id = (int)$_GET['id'];

// Before deleting from DB, get the image_path to delete the file from server
$image_path_to_delete = null;
$sql_select_image = "SELECT image_path FROM workshops WHERE id = ?";
$stmt_select = $conn->prepare($sql_select_image); // $conn comes from config.php

if ($stmt_select) {
    $stmt_select->bind_param("i", $workshop_id);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    if ($result_select->num_rows === 1) {
        $row = $result_select->fetch_assoc();
        if (!empty($row['image_path'])) {
            $image_path_to_delete = $row['image_path'];
        }
    }
    $stmt_select->close();
} else {
    error_log("Error preparing statement to select image path for workshop ID {$workshop_id}: " . $conn->error);
}

// Prepare SQL statement to delete the workshop
$sql_delete = "DELETE FROM workshops WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);

if ($stmt_delete) {
    $stmt_delete->bind_param("i", $workshop_id);

    if ($stmt_delete->execute()) {
        if ($stmt_delete->affected_rows > 0) {
            $_SESSION['success_message'] = "Workshop (ID: {$workshop_id}) deleted successfully.";

            if ($image_path_to_delete) {
                // $image_path_to_delete is like 'uploads/workshop_images/image.jpg'
                // This path is relative to the project root.
                $server_file_path = $image_path_to_delete; 
                if (file_exists($server_file_path)) {
                    if (unlink($server_file_path)) {
                        // Image file deleted successfully
                    } else {
                        $_SESSION['warning_message'] = "Workshop data deleted, but failed to delete associated image file: " . $image_path_to_delete;
                        error_log("Failed to delete image file: " . $server_file_path . " for workshop ID: " . $workshop_id);
                    }
                }
            }
        } else {
            $_SESSION['error_message'] = "No workshop found with ID {$workshop_id}, or it was already deleted.";
        }
    } else {
        $_SESSION['error_message'] = "Error deleting workshop: " . $stmt_delete->error;
        error_log("SQL Error deleting workshop ID {$workshop_id}: " . $stmt_delete->error);
    }
    $stmt_delete->close();
} else {
    $_SESSION['error_message'] = "Error preparing delete statement: " . $conn->error;
    error_log("SQL Prepare Error for delete: " . $conn->error);
}

$conn->close();
redirect("index.php?page=admin_manage_workshops");
?>