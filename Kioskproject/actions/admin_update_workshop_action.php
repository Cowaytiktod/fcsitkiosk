<?php
session_start(); // Ensure session is started
require_once '../config.php';        // For DB connection ($conn) and SITE_URL
require_once '../includes/functions.php'; // For isAdmin() and redirect()

// Ensure only admins can perform this action
if (!isAdmin()) {
    $_SESSION['error_message'] = "Access denied. Admins only.";
    redirect("index.php?page=login");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_workshop'])) {
    // Retrieve workshop ID (crucial for update)
    if (!isset($_POST['workshop_id']) || !filter_var($_POST['workshop_id'], FILTER_VALIDATE_INT)) {
        $_SESSION['error_message'] = "Invalid or missing workshop ID for update.";
        redirect("index.php?page=admin_manage_workshops");
    }
    $workshop_id = (int)$_POST['workshop_id'];

    // Retrieve and sanitize other form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $workshop_date = trim($_POST['workshop_date']);
    $duration_minutes = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT); // Use float for price
    $location = trim($_POST['location']) ?: null;
    $instructor_name = trim($_POST['instructor_name']) ?: null;
    $instructor_bio = trim($_POST['instructor_bio']) ?: null;
    $max_participants = filter_input(INPUT_POST, 'max_participants', FILTER_VALIDATE_INT) ?: null;
    $status = trim($_POST['status']);
    
    $existing_image_path = trim($_POST['existing_image_path']) ?: null;
    $remove_current_image = isset($_POST['remove_current_image']) && $_POST['remove_current_image'] == '1';

    // Basic Server-Side Validation
    if (empty($title) || empty($description) || empty($category) || empty($workshop_date) || $duration_minutes === false || $duration_minutes <= 0 || $price === false || $price < 0 || empty($status)) {
        $_SESSION['error_message'] = "Please fill in all required fields with valid data.";
        redirect("index.php?page=admin_edit_workshop&id=" . $workshop_id); // Redirect back to edit form
    }

    $image_path_for_db = $existing_image_path; // Start with the existing image path

    // 1. Handle "Remove current image" checkbox
    if ($remove_current_image && $existing_image_path) {
        $server_file_path = '../' . $existing_image_path; // Path relative to actions folder
        if (file_exists($server_file_path)) {
            unlink($server_file_path); // Delete the old image file
        }
        $image_path_for_db = null; // Set to null as image is removed
    }

    // 2. Handle new image upload (this overrides removal if a new image is also uploaded)
    if (isset($_FILES['new_image_path']) && $_FILES['new_image_path']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/workshop_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 2 * 1024 * 1024; // 2MB

        $file_type = $_FILES['new_image_path']['type'];
        $file_size = $_FILES['new_image_path']['size'];
        $tmp_name = $_FILES['new_image_path']['tmp_name'];
        
        if (in_array($file_type, $allowed_types) && $file_size <= $max_file_size) {
            // If there was an old image, delete it first
            if ($existing_image_path && !$remove_current_image) { // Don't try to delete if already marked for removal
                $old_server_file_path = '../' . $existing_image_path;
                if (file_exists($old_server_file_path)) {
                    unlink($old_server_file_path);
                }
            }

            $file_extension = pathinfo($_FILES['new_image_path']['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid('workshop_', true) . '.' . $file_extension;
            $destination = $upload_dir . $unique_filename;

            if (move_uploaded_file($tmp_name, $destination)) {
                $image_path_for_db = 'uploads/workshop_images/' . $unique_filename; // New image path
            } else {
                $_SESSION['error_message'] = "Failed to move newly uploaded image.";
                // Optionally log error, decide if this halts the update process
            }
        } else {
            $_SESSION['error_message'] = "Invalid new image type or size (max 2MB, JPG/PNG/GIF only).";
            // Decide if this halts the update process
        }
    } elseif (isset($_FILES['new_image_path']) && $_FILES['new_image_path']['error'] != UPLOAD_ERR_NO_FILE) {
        $_SESSION['error_message'] = "Error uploading new image: " . $_FILES['new_image_path']['error'];
        // Decide if this halts the update process
    }

    // Prepare SQL UPDATE statement
    $sql = "UPDATE workshops SET 
                title = ?, 
                description = ?, 
                category = ?, 
                workshop_date = ?, 
                duration_minutes = ?, 
                price = ?, 
                location = ?, 
                instructor_name = ?, 
                instructor_bio = ?, 
                max_participants = ?, 
                image_path = ?, 
                status = ?,
                updated_at = NOW() 
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "ssssidssssssi", // s: string, i: integer, d: double (for price)
            $title,
            $description,
            $category,
            $workshop_date,
            $duration_minutes,
            $price,
            $location,
            $instructor_name,
            $instructor_bio,
            $max_participants,
            $image_path_for_db, // This will be null if removed, new path if uploaded, or existing if unchanged
            $status,
            $workshop_id         // For the WHERE clause
        );

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Workshop (ID: {$workshop_id}) updated successfully!";
            redirect("index.php?page=admin_manage_workshops");
        } else {
            $_SESSION['error_message'] = "Error updating workshop: " . $stmt->error;
            error_log("SQL Error updating workshop ID {$workshop_id}: " . $stmt->error);
            redirect("index.php?page=admin_edit_workshop&id=" . $workshop_id);
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Error preparing update statement: " . $conn->error;
        error_log("SQL Prepare Error for update: " . $conn->error);
        redirect("index.php?page=admin_edit_workshop&id=" . $workshop_id);
    }
    $conn->close();

} else {
    // Not a POST request or form not submitted correctly
    $_SESSION['error_message'] = "Invalid request method.";
    redirect("index.php?page=admin_dashboard");
}
?>