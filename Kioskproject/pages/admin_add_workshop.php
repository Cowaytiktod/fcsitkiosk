<?php
// Ensure user is an admin, otherwise redirect
if (!isAdmin()) {
    $_SESSION['error_message'] = "Access denied. You must be an administrator to view this page.";
    redirect("index.php?page=login");
}

$page_title = "Add New Workshop";
?>

<h2>Add New Workshop</h2>

<form action="actions/add_workshop_action.php" method="POST" enctype="multipart/form-data" class="workshop-form">
    <div class="form-group">
        <label for="title">Title:</label>
        <input type="text" id="title" name="title" required>
    </div>

    <div class="form-group">
        <label for="description">Description:</label>
        <textarea id="description" name="description" rows="5" required></textarea>
    </div>

    <div class="form-group">
        <label for="category">Category:</label>
        <input type="text" id="category" name="category" required>
        <!-- Or, for a more controlled input, use a <select> dropdown if you have predefined categories -->
        <!-- <select id="category" name="category" required>
            <option value="">Select Category</option>
            <option value="Tech">Tech</option>
            <option value="Arts & Crafts">Arts & Crafts</option>
            <option value="Cooking">Cooking</option>
            <option value="Music">Music</option>
            <option value="Wellness">Wellness</option>
            <option value="Business">Business</option>
        </select> -->
    </div>

    <div class="form-group">
        <label for="workshop_date">Workshop Date & Time:</label>
        <input type="datetime-local" id="workshop_date" name="workshop_date" required>
    </div>

    <div class="form-group">
        <label for="duration_minutes">Duration (minutes):</label>
        <input type="number" id="duration_minutes" name="duration_minutes" min="30" step="15" required>
    </div>

    <div class="form-group">
        <label for="price">Price (e.g., 49.99):</label>
        <input type="number" id="price" name="price" min="0" step="0.01" required>
    </div>

    <div class="form-group">
        <label for="location">Location (Optional):</label>
        <input type="text" id="location" name="location">
    </div>
    
    <div class="form-group">
        <label for="instructor_name">Instructor Name (Optional):</label>
        <input type="text" id="instructor_name" name="instructor_name">
    </div>

    <div class="form-group">
        <label for="instructor_bio">Instructor Bio (Optional):</label>
        <textarea id="instructor_bio" name="instructor_bio" rows="3"></textarea>
    </div>

    <div class="form-group">
        <label for="max_participants">Max Participants (Optional):</label>
        <input type="number" id="max_participants" name="max_participants" min="1">
    </div>

    <div class="form-group">
        <label for="image_path">Workshop Image (Optional):</label>
        <input type="file" id="image_path" name="image_path" accept="image/jpeg, image/png, image/gif">
        <small>Upload a JPG, PNG, or GIF image. Max 2MB.</small>
    </div>
    
    <div class="form-group">
        <label for="status">Status:</label>
        <select id="status" name="status" required>
            <option value="active" selected>Active</option>
            <option value="upcoming">Upcoming</option>
            <option value="cancelled">Cancelled</option>
            <option value="past">Past</option>
        </select>
    </div>

    <div class="form-group">
        <button type="submit" name="add_workshop">Add Workshop</button>
    </div>
</form>

<style>
    .workshop-form .form-group {
        margin-bottom: 15px;
    }
    .workshop-form label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .workshop-form input[type="text"],
    .workshop-form input[type="number"],
    .workshop-form input[type="datetime-local"],
    .workshop-form input[type="file"],
    .workshop-form select,
    .workshop-form textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box; /* Important for full width */
    }
    .workshop-form textarea {
        resize: vertical;
    }
    .workshop-form button {
        background-color: #5cb85c;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .workshop-form button:hover {
        background-color: #4cae4c;
    }
</style>