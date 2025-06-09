<?php
// pages/browse_workshops.php
global $conn;

// Ensure $conn is actually set and a mysqli object
if (!$conn || !($conn instanceof mysqli)) {
    error_log("Database connection (\$conn) not available or not a mysqli object in browse_workshops.php. Check includes and db.php.");
    echo "<p>Error: A critical site component (database connection) is not available. Please try again later or contact the site administrator.</p>";
    return; // or exit;
}

$workshops = [];
$search_term = $_GET['search_term'] ?? ''; // Get search term, default to empty string

// Base SQL query - Includes full 'description' for better "..." logic
$sql = "SELECT id, title, category, workshop_date, price, description, LEFT(description, 100) as short_desc, image_path
        FROM workshops";

$params = []; // For prepared statement parameters
$types = "";  // For prepared statement types

if (!empty($search_term)) {
    $sql .= " WHERE (title LIKE ? OR description LIKE ?)";
    $like_search_term = "%" . $search_term . "%";
    $params[] = $like_search_term;
    $params[] = $like_search_term;
    $types .= "ss";
}

$sql .= " ORDER BY workshop_date DESC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $workshops[] = $row;
        }
    }
    $stmt->close();
} else {
    error_log("Error preparing SQL statement in browse_workshops.php: " . $conn->error);
}

?>
<h2>Browse Workshops</h2>

<div class="search-filter-area">
    <form method="GET" action="<?php echo defined('SITE_URL') ? SITE_URL : './'; ?>index.php">
        <input type="hidden" name="page" value="browse_workshops">
        <input type="text" name="search_term" placeholder="Search workshops..." value="<?php echo htmlspecialchars($search_term); ?>">
        <input type="submit" value="Search">
        <?php if (!empty($search_term)): ?>
            <a href="<?php echo defined('SITE_URL') ? SITE_URL : './'; ?>index.php?page=browse_workshops" class="clear-search-btn">Clear Search</a>
        <?php endif; ?>
    </form>
    <!-- TODO: <select name="category_filter">...</select> -->
</div>

<div class="workshop-list-container">
    <?php if (!empty($workshops)): ?>
        <?php foreach ($workshops as $workshop): ?>
            <div class="workshop-card">
                <?php
                  $site_url_for_image = defined('SITE_URL') ? SITE_URL : './';
                if ($site_url_for_image === './' && !defined('SITE_URL')) {
                    error_log("CRITICAL: SITE_URL is not defined in browse_workshops.php. Image paths will likely be incorrect.");
                }

                $image_path_from_db = $workshop['image_path'] ?? null; // Get path from DB, or null if not set
                $image_display_url = null;
                $server_actual_image_path = null;

                if (!empty($image_path_from_db)) {
                    // The image_path from DB should be like 'uploads/workshop_images/filename.jpg'
                    // So we just need to prepend SITE_URL.
                    if (strpos($image_path_from_db, 'uploads/workshop_images/') === 0) { // Check if it already has the full sub-path
                         $image_display_url = $site_url_for_image . htmlspecialchars($image_path_from_db);
                         $server_actual_image_path = __DIR__ . '/../' . $image_path_from_db; 
                    } else {
                        // This case would be if image_path_from_db only contains the filename, which is not what add_workshop_action.php does.
                        // However, adding a fallback just in case:
                        $image_display_url = $site_url_for_image . 'uploads/workshop_images/' . htmlspecialchars($image_path_from_db);
                        $server_actual_image_path = __DIR__ . '/../uploads/workshop_images/' . $image_path_from_db;
                        error_log("Image path from DB for workshop ID {$workshop['id']} ('{$image_path_from_db}') seemed to be just a filename. Adjusted path.");
                    }
                }
                
                // Fallback to default image if the specific workshop image is not set OR if the file doesn't exist on server
                if (empty($image_display_url) || ($server_actual_image_path && !file_exists($server_actual_image_path))) {
                    if ($server_actual_image_path && !file_exists($server_actual_image_path) && !empty($image_path_from_db)) {
                        error_log("Workshop image not found for workshop ID {$workshop['id']} at: " . $server_actual_image_path . ". Path from DB: '{$image_path_from_db}'");
                    }
                    
                    $default_image_relative_path = 'uploads/default_workshop.jpg'; // Path relative to project root
                    $default_image_server_path = __DIR__ . '/../' . $default_image_relative_path;

                    if (file_exists($default_image_server_path)) {
                        $image_display_url = $site_url_for_image . $default_image_relative_path;
                    } else {
                        error_log("CRITICAL: Default workshop image (default_workshop.jpg) not found at: " . $default_image_server_path);
                        $image_display_url = "https://via.placeholder.com/300x180.png?text=Image+Not+Found";
                    }
                }
                ?>
                <img src="<?php echo $image_display_url; ?>" alt="<?php echo htmlspecialchars($workshop['title'] ?? 'Workshop Image'); ?>" class="workshop-image">

                <div class="workshop-info">
                    <h3><?php echo htmlspecialchars($workshop['title'] ?? 'Untitled Workshop'); ?></h3>
                    <p class="workshop-category">
                        <strong>Category:</strong> <?php echo htmlspecialchars($workshop['category'] ?? 'N/A'); ?>
                    </p>
                    <p class="workshop-date">
                        <strong>Date:</strong>
                        <?php
                            if (!empty($workshop['workshop_date'])) {
                                try {
                                    $date = new DateTime($workshop['workshop_date']);
                                    echo $date->format('D, j M Y, g:i A');
                                } catch (Exception $e) {
                                    error_log("Error formatting date '{$workshop['workshop_date']}': " . $e->getMessage());
                                    echo htmlspecialchars($workshop['workshop_date']);
                                }
                            } else {
                                echo 'N/A';
                            }
                        ?>
                    </p>
                    <p class="workshop-price">
                        <strong>Price:</strong> RM<?php echo htmlspecialchars(number_format(is_numeric($workshop['price']) ? (float)$workshop['price'] : 0, 2)); ?>
                    </p>
                    <p class="workshop-description">
                        <?php
                            $short_desc_text = $workshop['short_desc'] ?? '';
                            $full_desc_text = $workshop['description'] ?? '';
                            echo htmlspecialchars($short_desc_text);
                            if (strlen($full_desc_text) > strlen($short_desc_text) && strlen($short_desc_text) > 0) {
                                echo "...";
                            }
                        ?>
                    </p>
                    <?php
                        $detail_page_url = (defined('SITE_URL') ? SITE_URL : './') . 'index.php?page=workshop_detail&id=' . ($workshop['id'] ?? ''); // Use '' instead of 0 for ID if not set
                    ?>
                    <a href="<?php echo $detail_page_url; ?>" class="btn-details">View Details</a>

                    <button class="btn-add-to-cart"
                            data-workshop-id="<?php echo htmlspecialchars($workshop['id'] ?? ''); ?>"
                            data-workshop-title="<?php echo htmlspecialchars($workshop['title'] ?? 'Untitled Workshop'); ?>"
                            data-workshop-price="<?php echo htmlspecialchars(is_numeric($workshop['price']) ? $workshop['price'] : 0); ?>"> Add to Cart
                    </button>
                </div>
            </div><!-- .workshop-card -->
        <?php endforeach; ?>
    <?php else: ?>
        <?php if (!empty($search_term)): ?>
            <p>No workshops found matching "<?php echo htmlspecialchars($search_term); ?>". <a href="<?php echo defined('SITE_URL') ? SITE_URL : './'; ?>index.php?page=browse_workshops">View all workshops</a>.</p>
        <?php else: ?>
            <p>No workshops currently available. Please check back later!</p>
        <?php endif; ?>
    <?php endif; ?>
</div><!-- .workshop-list-container -->