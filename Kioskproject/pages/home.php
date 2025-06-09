<?php
// pages/home.php

// --- START: PHP code to fetch featured workshops ---
$featured_workshops = [];
// Fetch, for example, the 3 most recently created workshops that are 'active' or 'upcoming'
// and have an image (or at least an attempt at an image_path).
$sql_featured = "SELECT id, title, image_path, LEFT(description, 80) as short_desc 
                 FROM workshops 
                 WHERE (status = 'active' OR status = 'upcoming')
                 ORDER BY created_at DESC 
                 LIMIT 3"; 
// You could also order by workshop_date ASC for the soonest upcoming workshops
// Removed "AND image_path IS NOT NULL AND image_path != ''" here to let the PHP logic handle default if path is bad or empty

// $conn should be available globally if your config.php and index.php structure is correct
if (isset($conn) && $conn instanceof mysqli) {
    $stmt_featured = $conn->prepare($sql_featured);

    if ($stmt_featured) {
        $stmt_featured->execute();
        $result_featured = $stmt_featured->get_result();
        if ($result_featured && $result_featured->num_rows > 0) {
            while ($row = $result_featured->fetch_assoc()) {
                $featured_workshops[] = $row;
            }
        }
        $stmt_featured->close();
    } else {
        error_log("Error preparing statement for featured workshops on home.php: " . $conn->error);
    }
} else {
    error_log("Database connection (\$conn) not available in home.php.");
}
// --- END: PHP code to fetch featured workshops ---
?>

<h2>Welcome to SkillShare Hub!</h2>
<p>Discover amazing workshops and learn new skills.</p>
<p>
    <a href="<?php echo SITE_URL; ?>index.php?page=browse_workshops" class="btn-primary">Browse Workshops Now!</a>
</p>

<h3>Featured Workshops</h3>

<?php if (!empty($featured_workshops)): ?>
    <div class="featured-workshops-container">
        <?php foreach ($featured_workshops as $workshop): ?>
            <div class="featured-workshop-card">
                <a href="<?php echo SITE_URL . 'index.php?page=workshop_detail&id=' . htmlspecialchars($workshop['id']); ?>" class="featured-workshop-link">
                    <?php
                    // --- START: Updated Image Display Logic ---
                    $site_url_for_image = SITE_URL; // SITE_URL should be defined from config.php
                    $image_path_from_db = $workshop['image_path'] ?? null;
                    $image_display_url = null; // Initialize
                    $server_path_to_check = null;

                    if (!empty($image_path_from_db)) {
                        // Check if the path from DB already includes the 'uploads/workshop_images/' prefix
                        if (strpos($image_path_from_db, 'uploads/workshop_images/') === 0) {
                            // Path from DB is already complete relative to project root
                            $image_display_url = $site_url_for_image . htmlspecialchars($image_path_from_db);
                            $server_path_to_check = $image_path_from_db; // This path is relative to project root
                        } else {
                            // Assume image_path_from_db is just the filename, and it belongs in uploads/workshop_images/
                            $image_display_url = $site_url_for_image . 'uploads/workshop_images/' . htmlspecialchars($image_path_from_db);
                            $server_path_to_check = 'uploads/workshop_images/' . $image_path_from_db; // Path relative to project root
                        }

                        // Check if the file actually exists using the constructed server path
                        if (!file_exists($server_path_to_check)) {
                            error_log("Featured workshop image not found at server path: " . $server_path_to_check . " (from DB: '" . $image_path_from_db . "') for workshop ID: " . $workshop['id']);
                            $image_display_url = null; // Force fallback to default if specific image file not found
                        }
                    } else {
                         error_log("No image_path specified in DB for featured workshop ID: " . $workshop['id']);
                         $image_display_url = null; // Force fallback to default
                    }
                    
                    // Fallback to default image if a specific one wasn't successfully determined OR if its file doesn't exist
                    if (empty($image_display_url)) {
                        $default_image_relative_path = 'uploads/default_workshop.jpg'; // Path relative to project root
                        if (file_exists($default_image_relative_path)) { 
                            $image_display_url = $site_url_for_image . $default_image_relative_path;
                        } else {
                            // Ultimate fallback if even the default image is missing
                            error_log("CRITICAL: Default workshop image (default_workshop.jpg) not found for homepage at " . $default_image_relative_path);
                            $image_display_url = "https://via.placeholder.com/300x180.png?text=Image+Unavailable"; 
                        }
                    }
                    // --- END: Updated Image Display Logic ---
                    ?>
                    <img src="<?php echo $image_display_url; ?>" alt="<?php echo htmlspecialchars($workshop['title'] ?? 'Workshop Image'); ?>" class="featured-workshop-image">
                    <div class="featured-workshop-info">
                        <h4><?php echo htmlspecialchars($workshop['title'] ?? 'Untitled Workshop'); ?></h4>
                        <?php if (!empty($workshop['short_desc'])): ?>
                            <p class="featured-workshop-desc"><?php echo htmlspecialchars($workshop['short_desc']); ?>...</p>
                        <?php endif; ?>
                    </div>
                </a>
            </div><!-- .featured-workshop-card -->
        <?php endforeach; ?>
    </div><!-- .featured-workshops-container -->
<?php else: ?>
    <p>No featured workshops available at the moment. Why not <a href="<?php echo SITE_URL; ?>index.php?page=browse_workshops">browse all our workshops</a>?</p>
<?php endif; ?>



