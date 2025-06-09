<?php
// pages/workshop_detail.php

// The $conn variable should be globally available if config.php is included by index.php
if (!isset($conn) || !($conn instanceof mysqli)) {
    error_log("Database connection (\$conn) not available in workshop_detail.php.");
    echo "<p class='error-message'>A critical database error occurred. Please try again later.</p>";
    return; // Exit this script if no DB connection
}

$workshop_id = isset($_GET['id']) ? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) : 0;
$workshop = null; // Initialize workshop variable

if ($workshop_id && $workshop_id > 0) {
    // Fetch all details for the workshop
    $sql = "SELECT id, title, description, category, workshop_date, duration_minutes, 
                   price, location, instructor_name, instructor_bio, 
                   max_participants, current_participants, image_path, status 
            FROM workshops 
            WHERE id = ? AND (status = 'active' OR status = 'upcoming')";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $workshop_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $workshop = $result->fetch_assoc();
        }
        $stmt->close();
    } else {
        error_log("Error preparing statement for workshop detail (ID: {$workshop_id}): " . $conn->error);
    }
}

if (!$workshop) {
    echo "<h2>Workshop Not Found</h2>";
    echo "<p>Sorry, the workshop you are looking for could not be found or is no longer available.</p>";
    echo "<p><a href='" . SITE_URL . "index.php?page=browse_workshops'>Return to Browse Workshops</a></p>";
} else {
    $page_title = htmlspecialchars($workshop['title']);
    $remaining_spots = ($workshop['max_participants'] > 0) ? ($workshop['max_participants'] - $workshop['current_participants']) : 'N/A';
    if (is_numeric($remaining_spots) && $remaining_spots < 0) $remaining_spots = 0;

    $formatted_date = 'N/A';
    $formatted_time = 'N/A';
    if (!empty($workshop['workshop_date'])) {
        try {
            $workshop_datetime_obj = new DateTime($workshop['workshop_date']);
            $formatted_date = $workshop_datetime_obj->format('l, F j, Y');
            $formatted_time = $workshop_datetime_obj->format('g:i A');
        } catch (Exception $e) {
            error_log("Error formatting workshop_date '{$workshop['workshop_date']}' in detail: " . $e->getMessage());
        }
    }
?>

<div class="workshop-detail-container">
    <h2><?php echo htmlspecialchars($workshop['title']); ?></h2>

    <div class="workshop-detail-main-content">
        <div class="workshop-detail-image-area">
            <?php
            $site_url_for_image = SITE_URL;
            $image_path_from_db = $workshop['image_path'] ?? null;
            $image_display_url = null;
            $server_path_to_check = null;

            if (!empty($image_path_from_db)) {
                if (strpos($image_path_from_db, 'uploads/workshop_images/') === 0) {
                    $image_display_url = $site_url_for_image . htmlspecialchars($image_path_from_db);
                    $server_path_to_check = $image_path_from_db;
                } else {
                    $image_display_url = $site_url_for_image . 'uploads/workshop_images/' . htmlspecialchars($image_path_from_db);
                    $server_path_to_check = 'uploads/workshop_images/' . $image_path_from_db;
                }
                if (!file_exists($server_path_to_check)) {
                    error_log("Workshop detail image not found at server path: " . $server_path_to_check . " (from DB: '" . $image_path_from_db . "') for workshop ID: " . $workshop['id']);
                    $image_display_url = null; 
                }
            }
            
            if (empty($image_display_url)) {
                $default_image_relative_path = 'uploads/default_workshop.jpg';
                if (file_exists($default_image_relative_path)) { 
                    $image_display_url = $site_url_for_image . $default_image_relative_path;
                } else {
                    $image_display_url = "https://via.placeholder.com/400x250.png?text=" . urlencode($workshop['title']); 
                }
            }
            ?>
            <img src="<?php echo $image_display_url; ?>" alt="<?php echo htmlspecialchars($workshop['title']); ?>" class="workshop-detail-image">
        </div>

        <div class="workshop-detail-info-area">
            <p><strong>Category:</strong> <?php echo htmlspecialchars($workshop['category']); ?></p>
            <p><strong>Date:</strong> <?php echo $formatted_date; ?></p>
            <p><strong>Time:</strong> <?php echo $formatted_time; ?></p>
            <?php if ($workshop['duration_minutes']): ?>
                <p><strong>Duration:</strong> <?php echo htmlspecialchars($workshop['duration_minutes']); ?> minutes</p>
            <?php endif; ?>
            <?php if ($workshop['location']): ?>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($workshop['location']); ?></p>
            <?php endif; ?>
            <p><strong>Price:</strong> MYR <?php echo htmlspecialchars(number_format((float)$workshop['price'], 2)); ?></p>
            
            <?php if ($workshop['max_participants'] > 0): ?>
                <p><strong>Spots Available:</strong> <?php echo htmlspecialchars($remaining_spots); ?> / <?php echo htmlspecialchars($workshop['max_participants']); ?></p>
            <?php endif; ?>
            
            <!-- Add to Cart / Book Now Button (Modified for JavaScript localStorage cart) -->
            <form style="margin-top: 20px;"> 
                <input type="hidden" name="workshop_id_form_value_for_reference_only" value="<?php echo htmlspecialchars($workshop['id']); ?>"> 
                
                <label for="quantity_<?php echo $workshop['id']; ?>">Quantity:</label>
                <input type="number" id="quantity_<?php echo $workshop['id']; ?>" name="quantity" value="1" min="1" 
                       max="<?php echo (is_numeric($remaining_spots) && $remaining_spots > 0) ? $remaining_spots : 1; ?>" 
                       style="width: 60px; margin-right: 10px;" 
                       <?php echo (is_numeric($remaining_spots) && $remaining_spots == 0) ? 'disabled' : ''; ?>>
                
                <!-- This button is now primarily handled by JavaScript -->
                <button type="button" 
                        class="btn-add-to-cart-detail btn-add-to-cart" 
                        data-workshop-id="<?php echo htmlspecialchars($workshop['id']); ?>" 
                        data-workshop-title="<?php echo htmlspecialchars($workshop['title']); ?>" 
                        data-workshop-price="<?php echo htmlspecialchars($workshop['price']); ?>" 
                        <?php echo (is_numeric($remaining_spots) && $remaining_spots == 0) ? 'disabled' : ''; ?>>
                    <?php echo (is_numeric($remaining_spots) && $remaining_spots == 0) ? 'Fully Booked' : 'Add to Cart / Book Now'; ?>
                </button>
                <span class="cart-feedback" id="cart-feedback-<?php echo $workshop['id']; ?>"></span>
            </form>
            <!-- The "todo-note" was here, you can remove it or update as functionality is complete -->
        </div>
    </div>

    <div class="workshop-detail-description">
        <h3>Workshop Description</h3>
        <p><?php echo nl2br(htmlspecialchars($workshop['description'])); ?></p>
    </div>

    <?php if ($workshop['instructor_name'] || $workshop['instructor_bio']): ?>
    <div class="workshop-detail-instructor">
        <h3>About the Instructor</h3>
        <?php if ($workshop['instructor_name']): ?>
            <p><strong><?php echo htmlspecialchars($workshop['instructor_name']); ?></strong></p>
        <?php endif; ?>
        <?php if ($workshop['instructor_bio']): ?>
            <p><?php echo nl2br(htmlspecialchars($workshop['instructor_bio'])); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div><!-- .workshop-detail-container -->

<?php
} // End of the main if ($workshop) block
?>