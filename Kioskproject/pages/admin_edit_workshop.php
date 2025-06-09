<?php
// Ensure user is an admin, otherwise redirect
if (!isAdmin()) {
    $_SESSION['error_message'] = "Access denied. You must be an administrator to view this page.";
    redirect("index.php?page=login");
}

// Check if ID is provided and is valid
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $_SESSION['error_message'] = "Invalid or missing workshop ID.";
    redirect("index.php?page=admin_manage_workshops");
}

$workshop_id = (int)$_GET['id'];
$workshop = null; // Initialize workshop variable

// Fetch existing workshop data from the database
$sql = "SELECT * FROM workshops WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $workshop_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $workshop = $result->fetch_assoc();
    } else {
        $_SESSION['error_message'] = "Workshop not found with ID: {$workshop_id}.";
        redirect("index.php?page=admin_manage_workshops");
    }
    $stmt->close();
} else {
    $_SESSION['error_message'] = "Error preparing statement to fetch workshop details: " . $conn->error;
    redirect("index.php?page=admin_manage_workshops");
}

if (!$workshop) {
    // Should have been caught by the num_rows check, but as a fallback.
    $_SESSION['error_message'] = "Failed to load workshop data.";
    redirect("index.php?page=admin_manage_workshops");
}

$page_title = "Edit Workshop - " . htmlspecialchars($workshop['title']);

// Format workshop_date for datetime-local input (YYYY-MM-DDTHH:MM)
$formatted_workshop_date = '';
if (!empty($workshop['workshop_date'])) {
    try {
        $date_obj = new DateTime($workshop['workshop_date']);
        $formatted_workshop_date = $date_obj->format('Y-m-d\TH:i');
    } catch (Exception $e) {
        error_log("Error formatting workshop_date for edit form: " . $e->getMessage());
        // Use a sensible default or leave empty if formatting fails
    }
}

?>

<h2>Edit Workshop: <?php echo htmlspecialchars($workshop['title']); ?></h2>

<form action="actions/admin_update_workshop_action.php" method="POST" enctype="multipart/form-data" class="workshop-form">
    <input type="hidden" name="workshop_id" value="<?php echo htmlspecialchars($workshop['id']); ?>">

    <div class="form-group">
        <label for="title">Title:</label>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($workshop['title']); ?>" required>
    </div>

    <div class="form-group">
        <label for="description">Description:</label>
        <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($workshop['description']); ?></textarea>
    </div>

    <div class="form-group">
        <label for="category">Category:</label>
        <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($workshop['category']); ?>" required>
        <!-- Consider using a <select> if you have predefined categories -->
    </div>

    <div class="form-group">
        <label for="workshop_date">Workshop Date & Time:</label>
        <input type="datetime-local" id="workshop_date" name="workshop_date" value="<?php echo $formatted_workshop_date; ?>" required>
    </div>

    <div class="form-group">
        <label for="duration_minutes">Duration (minutes):</label>
        <input type="number" id="duration_minutes" name="duration_minutes" value="<?php echo htmlspecialchars($workshop['duration_minutes']); ?>" min="30" step="15" required>
    </div>

    <div class="form-group">
        <label for="price">Price:</label>
        <input type="number" id="price" name="price" value="<?php echo htmlspecialchars(number_format((float)$workshop['price'], 2, '.', '')); ?>" min="0" step="0.01" required>
    </div>

    <div class="form-group">
        <label for="location">Location (Optional):</label>
        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($workshop['location'] ?? ''); ?>">
    </div>
    
    <div class="form-group">
        <label for="instructor_name">Instructor Name (Optional):</label>
        <input type="text" id="instructor_name" name="instructor_name" value="<?php echo htmlspecialchars($workshop['instructor_name'] ?? ''); ?>">
    </div>

    <div class="form-group">
        <label for="instructor_bio">Instructor Bio (Optional):</label>
        <textarea id="instructor_bio" name="instructor_bio" rows="3"><?php echo htmlspecialchars($workshop['instructor_bio'] ?? ''); ?></textarea>
    </div>

    <div class="form-group">
        <label for="max_participants">Max Participants (Optional):</label>
        <input type="number" id="max_participants" name="max_participants" value="<?php echo htmlspecialchars($workshop['max_participants'] ?? ''); ?>" min="1">
    </div>

    <div class="form-group">
        <label for="current_image">Current Image:</label>
        <?php if (!empty($workshop['image_path']) && file_exists($workshop['image_path'])): ?>
            <img src="<?php echo SITE_URL . htmlspecialchars($workshop['image_path']); ?>" alt="Current workshop image" style="max-width: 200px; max-height: 150px; display:block; margin-bottom:10px;">
            <input type="checkbox" name="remove_current_image" id="remove_current_image" value="1">
            <label for="remove_current_image" style="font-weight:normal; display:inline;">Remove current image?</label>
        <?php else: ?>
            <p>No current image or image not found.</p>
        <?php endif; ?>
        <input type="hidden" name="existing_image_path" value="<?php echo htmlspecialchars($workshop['image_path'] ?? ''); ?>">
    </div>

    <div class="form-group">
        <label for="image_path">Upload New Image (Optional - leave blank to keep current):</label>
        <input type="file" id="image_path" name="new_image_path" accept="image/jpeg, image/png, image/gif">
        <small>Upload a JPG, PNG, or GIF image. Max 2MB.</small>
    </div>
    
    <div class="form-group">
        <label for="status">Status:</label>
        <select id="status" name="status" required>
            <option value="active" <?php echo ($workshop['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
            <option value="upcoming" <?php echo ($workshop['status'] === 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
            <option value="cancelled" <?php echo ($workshop['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
            <option value="past" <?php echo ($workshop['status'] === 'past') ? 'selected' : ''; ?>>Past</option>
        </select>
    </div>

    <div class="form-group">
        <button type="submit" name="update_workshop">Update Workshop</button>
    </div>
</form>

<!-- Include the same CSS as the add workshop form for consistency -->
<style>
    .workshop-form .form-group { margin-bottom: 15px; }
    .workshop-form label { display: block; margin-bottom: 5px; font-weight: bold; }
    .workshop-form input[type="text"],
    .workshop-form input[type="number"],
    .workshop-form input[type="datetime-local"],
    .workshop-form input[type="file"],
    .workshop-form select,
    .workshop-form textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    .workshop-form textarea { resize: vertical; }
    .workshop-form button { background-color: #5cb85c; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
    .workshop-form button:hover { background-color: #4cae4c; }
</style>