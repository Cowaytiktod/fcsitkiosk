<?php
session_start();
require_once '../config.php'; // Defines SITE_URL, DB_HOST, etc. and connects to DB ($conn)
require_once '../includes/functions.php'; // Contains isAdmin(), redirect()

// Ensure only admins can execute this action
if (!isAdmin()) {
    $_SESSION['error_message'] = "Access denied. Admins only.";
    redirect("index.php?page=login"); // CORRECTED: Removed SITE_URL from here
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_workshop'])) {
    // Retrieve and sanitize form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $workshop_date = trim($_POST['workshop_date']); // Format: YYYY-MM-DDTHH:MM
    $duration_minutes = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $location = trim($_POST['location']) ?: null;
    $instructor_name = trim($_POST['instructor_name']) ?: null;
    $instructor_bio = trim($_POST['instructor_bio']) ?: null;
    $max_participants = filter_input(INPUT_POST, 'max_participants', FILTER_VALIDATE_INT) ?: null;
    $status = trim($_POST['status']);

    if (empty($title) || empty($description) || empty($category) || empty($workshop_date) || $duration_minutes === false || $duration_minutes <= 0 || $price === false || $price < 0 || empty($status)) {
        $_SESSION['error_message'] = "Please fill in all required fields with valid data.";
        redirect("index.php?page=admin_add_workshop"); // CORRECTED
    }

    $image_path_for_db = null;
    if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/workshop_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 2 * 1024 * 1024; 

        $file_type = $_FILES['image_path']['type'];
        $file_size = $_FILES['image_path']['size'];
        $tmp_name = $_FILES['image_path']['tmp_name'];
        
        if (in_array($file_type, $allowed_types) && $file_size <= $max_file_size) {
            $file_extension = pathinfo($_FILES['image_path']['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid('workshop_', true) . '.' . $file_extension;
            $destination = $upload_dir . $unique_filename;

            if (move_uploaded_file($tmp_name, $destination)) {
                $image_path_for_db = 'uploads/workshop_images/' . $unique_filename;
            } else {
                $_SESSION['error_message'] = "Failed to move uploaded image.";
                // Optional: Log error
                // redirect("index.php?page=admin_add_workshop"); // CORRECTED (Decide if fatal)
            }
        } else {
            $_SESSION['error_message'] = "Invalid image type or size (max 2MB, JPG/PNG/GIF only).";
            // redirect("index.php?page=admin_add_workshop"); // CORRECTED (Decide if fatal)
        }
    } elseif (isset($_FILES['image_path']) && $_FILES['image_path']['error'] != UPLOAD_ERR_NO_FILE) {
        $_SESSION['error_message'] = "Error uploading image: " . $_FILES['image_path']['error'];
        // redirect("index.php?page=admin_add_workshop"); // CORRECTED
    }

    $sql = "INSERT INTO workshops (title, description, category, workshop_date, duration_minutes, price, location, instructor_name, instructor_bio, max_participants, image_path, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "ssssidssssss",
            $title, $description, $category, $workshop_date, $duration_minutes, $price,
            $location, $instructor_name, $instructor_bio, $max_participants, $image_path_for_db, $status
        );

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "New workshop added successfully!";
            redirect("index.php?page=admin_manage_workshops"); // CORRECTED
        } else {
            $_SESSION['error_message'] = "Error adding workshop: " . $stmt->error;
            redirect("index.php?page=admin_add_workshop"); // CORRECTED
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
        redirect("index.php?page=admin_add_workshop"); // CORRECTED
    }
    $conn->close();

} else {
    $_SESSION['error_message'] = "Invalid request.";
    redirect("index.php?page=admin_dashboard"); // CORRECTED
}
?><?php
session_start();
require_once '../config.php'; // Defines SITE_URL, DB_HOST, etc. and connects to DB ($conn)
require_once '../includes/functions.php'; // Contains isAdmin(), redirect()

// Ensure only admins can execute this action
if (!isAdmin()) {
    $_SESSION['error_message'] = "Access denied. Admins only.";
    redirect("index.php?page=login"); // CORRECTED: Removed SITE_URL from here
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_workshop'])) {
    // Retrieve and sanitize form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $workshop_date = trim($_POST['workshop_date']); // Format: YYYY-MM-DDTHH:MM
    $duration_minutes = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $location = trim($_POST['location']) ?: null;
    $instructor_name = trim($_POST['instructor_name']) ?: null;
    $instructor_bio = trim($_POST['instructor_bio']) ?: null;
    $max_participants = filter_input(INPUT_POST, 'max_participants', FILTER_VALIDATE_INT) ?: null;
    $status = trim($_POST['status']);

    if (empty($title) || empty($description) || empty($category) || empty($workshop_date) || $duration_minutes === false || $duration_minutes <= 0 || $price === false || $price < 0 || empty($status)) {
        $_SESSION['error_message'] = "Please fill in all required fields with valid data.";
        redirect("index.php?page=admin_add_workshop"); // CORRECTED
    }

    $image_path_for_db = null;
    if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/workshop_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 2 * 1024 * 1024; 

        $file_type = $_FILES['image_path']['type'];
        $file_size = $_FILES['image_path']['size'];
        $tmp_name = $_FILES['image_path']['tmp_name'];
        
        if (in_array($file_type, $allowed_types) && $file_size <= $max_file_size) {
            $file_extension = pathinfo($_FILES['image_path']['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid('workshop_', true) . '.' . $file_extension;
            $destination = $upload_dir . $unique_filename;

            if (move_uploaded_file($tmp_name, $destination)) {
                $image_path_for_db = 'uploads/workshop_images/' . $unique_filename;
            } else {
                $_SESSION['error_message'] = "Failed to move uploaded image.";
                // Optional: Log error
                // redirect("index.php?page=admin_add_workshop"); // CORRECTED (Decide if fatal)
            }
        } else {
            $_SESSION['error_message'] = "Invalid image type or size (max 2MB, JPG/PNG/GIF only).";
            // redirect("index.php?page=admin_add_workshop"); // CORRECTED (Decide if fatal)
        }
    } elseif (isset($_FILES['image_path']) && $_FILES['image_path']['error'] != UPLOAD_ERR_NO_FILE) {
        $_SESSION['error_message'] = "Error uploading image: " . $_FILES['image_path']['error'];
        // redirect("index.php?page=admin_add_workshop"); // CORRECTED
    }

    $sql = "INSERT INTO workshops (title, description, category, workshop_date, duration_minutes, price, location, instructor_name, instructor_bio, max_participants, image_path, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "ssssidssssss",
            $title, $description, $category, $workshop_date, $duration_minutes, $price,
            $location, $instructor_name, $instructor_bio, $max_participants, $image_path_for_db, $status
        );

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "New workshop added successfully!";
            redirect("index.php?page=admin_manage_workshops"); // CORRECTED
        } else {
            $_SESSION['error_message'] = "Error adding workshop: " . $stmt->error;
            redirect("index.php?page=admin_add_workshop"); // CORRECTED
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
        redirect("index.php?page=admin_add_workshop"); // CORRECTED
    }
    $conn->close();

} else {
    $_SESSION['error_message'] = "Invalid request.";
    redirect("index.php?page=admin_dashboard"); // CORRECTED
}
?><?php
session_start();
require_once '../config.php'; // Defines SITE_URL, DB_HOST, etc. and connects to DB ($conn)
require_once '../includes/functions.php'; // Contains isAdmin(), redirect()

// Ensure only admins can execute this action
if (!isAdmin()) {
    $_SESSION['error_message'] = "Access denied. Admins only.";
    redirect("index.php?page=login"); // CORRECTED: Removed SITE_URL from here
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_workshop'])) {
    // Retrieve and sanitize form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $workshop_date = trim($_POST['workshop_date']); // Format: YYYY-MM-DDTHH:MM
    $duration_minutes = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $location = trim($_POST['location']) ?: null;
    $instructor_name = trim($_POST['instructor_name']) ?: null;
    $instructor_bio = trim($_POST['instructor_bio']) ?: null;
    $max_participants = filter_input(INPUT_POST, 'max_participants', FILTER_VALIDATE_INT) ?: null;
    $status = trim($_POST['status']);

    if (empty($title) || empty($description) || empty($category) || empty($workshop_date) || $duration_minutes === false || $duration_minutes <= 0 || $price === false || $price < 0 || empty($status)) {
        $_SESSION['error_message'] = "Please fill in all required fields with valid data.";
        redirect("index.php?page=admin_add_workshop"); // CORRECTED
    }

    $image_path_for_db = null;
    if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/workshop_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 2 * 1024 * 1024; 

        $file_type = $_FILES['image_path']['type'];
        $file_size = $_FILES['image_path']['size'];
        $tmp_name = $_FILES['image_path']['tmp_name'];
        
        if (in_array($file_type, $allowed_types) && $file_size <= $max_file_size) {
            $file_extension = pathinfo($_FILES['image_path']['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid('workshop_', true) . '.' . $file_extension;
            $destination = $upload_dir . $unique_filename;

            if (move_uploaded_file($tmp_name, $destination)) {
                $image_path_for_db = 'uploads/workshop_images/' . $unique_filename;
            } else {
                $_SESSION['error_message'] = "Failed to move uploaded image.";
                // Optional: Log error
                // redirect("index.php?page=admin_add_workshop"); // CORRECTED (Decide if fatal)
            }
        } else {
            $_SESSION['error_message'] = "Invalid image type or size (max 2MB, JPG/PNG/GIF only).";
            // redirect("index.php?page=admin_add_workshop"); // CORRECTED (Decide if fatal)
        }
    } elseif (isset($_FILES['image_path']) && $_FILES['image_path']['error'] != UPLOAD_ERR_NO_FILE) {
        $_SESSION['error_message'] = "Error uploading image: " . $_FILES['image_path']['error'];
        // redirect("index.php?page=admin_add_workshop"); // CORRECTED
    }

    $sql = "INSERT INTO workshops (title, description, category, workshop_date, duration_minutes, price, location, instructor_name, instructor_bio, max_participants, image_path, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "ssssidssssss",
            $title, $description, $category, $workshop_date, $duration_minutes, $price,
            $location, $instructor_name, $instructor_bio, $max_participants, $image_path_for_db, $status
        );

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "New workshop added successfully!";
            redirect("index.php?page=admin_manage_workshops"); // CORRECTED
        } else {
            $_SESSION['error_message'] = "Error adding workshop: " . $stmt->error;
            redirect("index.php?page=admin_add_workshop"); // CORRECTED
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
        redirect("index.php?page=admin_add_workshop"); // CORRECTED
    }
    $conn->close();

} else {
    $_SESSION['error_message'] = "Invalid request.";
    redirect("index.php?page=admin_dashboard"); // CORRECTED
}
?><?php
session_start();
require_once '../config.php'; // Defines SITE_URL, DB_HOST, etc. and connects to DB ($conn)
require_once '../includes/functions.php'; // Contains isAdmin(), redirect()

// Ensure only admins can execute this action
if (!isAdmin()) {
    $_SESSION['error_message'] = "Access denied. Admins only.";
    redirect("index.php?page=login"); // CORRECTED: Removed SITE_URL from here
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_workshop'])) {
    // Retrieve and sanitize form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $workshop_date = trim($_POST['workshop_date']); // Format: YYYY-MM-DDTHH:MM
    $duration_minutes = filter_input(INPUT_POST, 'duration_minutes', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $location = trim($_POST['location']) ?: null;
    $instructor_name = trim($_POST['instructor_name']) ?: null;
    $instructor_bio = trim($_POST['instructor_bio']) ?: null;
    $max_participants = filter_input(INPUT_POST, 'max_participants', FILTER_VALIDATE_INT) ?: null;
    $status = trim($_POST['status']);

    if (empty($title) || empty($description) || empty($category) || empty($workshop_date) || $duration_minutes === false || $duration_minutes <= 0 || $price === false || $price < 0 || empty($status)) {
        $_SESSION['error_message'] = "Please fill in all required fields with valid data.";
        redirect("index.php?page=admin_add_workshop"); // CORRECTED
    }

    $image_path_for_db = null;
    if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/workshop_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_file_size = 2 * 1024 * 1024; 

        $file_type = $_FILES['image_path']['type'];
        $file_size = $_FILES['image_path']['size'];
        $tmp_name = $_FILES['image_path']['tmp_name'];
        
        if (in_array($file_type, $allowed_types) && $file_size <= $max_file_size) {
            $file_extension = pathinfo($_FILES['image_path']['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid('workshop_', true) . '.' . $file_extension;
            $destination = $upload_dir . $unique_filename;

            if (move_uploaded_file($tmp_name, $destination)) {
                $image_path_for_db = 'uploads/workshop_images/' . $unique_filename;
            } else {
                $_SESSION['error_message'] = "Failed to move uploaded image.";
                // Optional: Log error
                // redirect("index.php?page=admin_add_workshop"); // CORRECTED (Decide if fatal)
            }
        } else {
            $_SESSION['error_message'] = "Invalid image type or size (max 2MB, JPG/PNG/GIF only).";
            // redirect("index.php?page=admin_add_workshop"); // CORRECTED (Decide if fatal)
        }
    } elseif (isset($_FILES['image_path']) && $_FILES['image_path']['error'] != UPLOAD_ERR_NO_FILE) {
        $_SESSION['error_message'] = "Error uploading image: " . $_FILES['image_path']['error'];
        // redirect("index.php?page=admin_add_workshop"); // CORRECTED
    }

    $sql = "INSERT INTO workshops (title, description, category, workshop_date, duration_minutes, price, location, instructor_name, instructor_bio, max_participants, image_path, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "ssssidssssss",
            $title, $description, $category, $workshop_date, $duration_minutes, $price,
            $location, $instructor_name, $instructor_bio, $max_participants, $image_path_for_db, $status
        );

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "New workshop added successfully!";
            redirect("index.php?page=admin_manage_workshops"); // CORRECTED
        } else {
            $_SESSION['error_message'] = "Error adding workshop: " . $stmt->error;
            redirect("index.php?page=admin_add_workshop"); // CORRECTED
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
        redirect("index.php?page=admin_add_workshop"); // CORRECTED
    }
    $conn->close();

} else {
    $_SESSION['error_message'] = "Invalid request.";
    redirect("index.php?page=admin_dashboard"); // CORRECTED
}
?>