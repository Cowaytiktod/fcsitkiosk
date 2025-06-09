<?php
// pages/my_bookings.php
global $conn;

$page_title = "My Bookings"; // For potential use in a dynamic title tag if your header supports it

if (!isLoggedIn()) {
    redirect("index.php?page=login&message=login_required_for_bookings");
}

$user_id = $_SESSION['user_id'];
$bookings = [];
$db_error = null;

if ($conn && ($conn instanceof mysqli)) { // Check if $conn is valid before using it
    // Fetch bookings for the logged-in user
    // Joining with workshops table to get workshop details
    $sql = "SELECT 
                b.id as booking_id, 
                b.booking_date, 
                b.total_price as price_paid, 
                b.quantity, 
                b.payment_status, 
                w.title as workshop_title, 
                w.workshop_date as workshop_event_date,
                w.id as workshop_id
            FROM bookings b
            JOIN workshops w ON b.workshop_id = w.id
            WHERE b.user_id = ?
            ORDER BY b.booking_date DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $bookings[] = $row;
            }
        } else {
            $db_error = "Error executing bookings query: " . htmlspecialchars($stmt->error);
            error_log("MyBookings - Execute Error: " . $stmt->error . " for User ID: " . $user_id);
        }
        $stmt->close();
    } else {
        $db_error = "Error preparing bookings query: " . htmlspecialchars($conn->error);
        error_log("MyBookings - Prepare Error: " . $conn->error . " for User ID: " . $user_id);
    }
} else {
    $db_error = "Database connection not available. Please try again later.";
    error_log("MyBookings - DB Connection Error for User ID: " . $user_id);
}
?>

<h2>My Bookings</h2>

<?php
// Display any session messages (success/error from other actions)
if (isset($_SESSION['success_message'])) {
    echo '<p class="success-message">' . htmlspecialchars($_SESSION['success_message']) . '</p>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<p class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '</p>';
    unset($_SESSION['error_message']);
}

// Display database error if one occurred
if ($db_error) {
    echo '<p class="error-message">' . $db_error . '</p>';
}
?>

<?php if (empty($bookings) && !$db_error): ?>
    <p>You have not made any bookings yet.</p>
    <p><a href="<?php echo defined('SITE_URL') ? SITE_URL : './'; ?>index.php?page=browse_workshops" class="btn-link">Browse workshops to make a booking</a></p>
<?php elseif (!empty($bookings)): ?>
    <div class="table-responsive"> {/* Wrapper for better responsiveness on small screens */}
        <table class="bookings-table">
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Workshop Title</th>
                    <th>Workshop Date</th>
                    <th>Date Booked</th>
                    <th>Price Paid</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['booking_id']); ?></td>
                        <td>
                            <a href="<?php echo defined('SITE_URL') ? SITE_URL : './'; ?>index.php?page=workshop_detail&id=<?php echo htmlspecialchars($booking['workshop_id']); ?>">
                                <?php echo htmlspecialchars($booking['workshop_title']); ?>
                            </a>
                        </td>
                        <td>
                            <?php
                                if (!empty($booking['workshop_event_date'])) {
                                    try {
                                        $event_date = new DateTime($booking['workshop_event_date']);
                                        echo $event_date->format('D, M j, Y - g:i A');
                                    } catch (Exception $e) {
                                        error_log("Error formatting workshop_event_date '{$booking['workshop_event_date']}': " . $e->getMessage());
                                        echo htmlspecialchars($booking['workshop_event_date']); // Fallback
                                    }
                                } else { echo 'N/A'; }
                            ?>
                        </td>
                        <td>
                            <?php
                                if (!empty($booking['booking_date'])) {
                                    try {
                                        $booking_made_date = new DateTime($booking['booking_date']);
                                        echo $booking_made_date->format('M j, Y - H:i');
                                    } catch (Exception $e) {
                                        error_log("Error formatting booking_date '{$booking['booking_date']}': " . $e->getMessage());
                                        echo htmlspecialchars($booking['booking_date']); // Fallback
                                    }
                                } else { echo 'N/A'; }
                            ?>
                        </td>
                        <td>$<?php echo htmlspecialchars(number_format((float)($booking['price_paid'] ?? 0), 2)); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $booking['payment_status'] ?? 'Unknown'))); ?></td>
                        <td>
                            <a href="<?php echo defined('SITE_URL') ? SITE_URL : './'; ?>index.php?page=view_receipt&booking_id=<?php echo htmlspecialchars($booking['booking_id']); ?>" class="btn-view-receipt">
                                View Receipt
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<style>
    /* Styles for bookings table - ideally move to your main style.css */
    .table-responsive {
        overflow-x: auto; /* Adds horizontal scroll for the table on small screens */
        margin-bottom: 15px;
    }
    .bookings-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        font-size: 0.9em;
    }
    .bookings-table th, .bookings-table td {
        border: 1px solid #ddd;
        padding: 8px 10px;
        text-align: left;
        vertical-align: middle; /* Good for cells with varying content height */
        white-space: nowrap; /* Prevents text wrapping that might make rows too tall */
    }
    .bookings-table td:nth-child(2) { /* Workshop Title column */
        white-space: normal; /* Allow workshop title to wrap if long */
    }
    .bookings-table th {
        background-color: #f2f2f2;
        font-weight: bold;
        position: sticky; /* Makes header sticky if table scrolls, browser support varies */
        top: 0; /* Needed for sticky header */
        z-index: 1; /* To keep header above scrolling content */
    }
    .bookings-table tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .bookings-table tbody tr:hover {
        background-color: #f1f1f1;
    }

    /* General button link style, can be reused */
    .btn-link {
        display: inline-block;
        padding: 8px 15px;
        background-color: #007bff;
        color: white !important; /* Ensure text is white if it's an <a> tag */
        text-decoration: none;
        border-radius: 4px;
        border: 1px solid #007bff;
        transition: background-color 0.2s ease-in-out;
    }
    .btn-link:hover {
        background-color: #0056b3;
        border-color: #0056b3;
        text-decoration: none;
    }

    /* Specific style for the view receipt link */
    .btn-view-receipt {
        display: inline-block;
        padding: 5px 10px; /* Smaller padding for action buttons in table */
        font-size: 0.85em;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
        cursor: pointer;
        border: 1px solid transparent;
        border-radius: 3px;
        color: #fff !important;
        background-color: #5bc0de; /* Info blue */
        border-color: #46b8da;
        text-decoration: none;
    }
    .btn-view-receipt:hover {
        background-color: #31b0d5;
        border-color: #269abc;
        color: #fff;
    }

    /* General success/error messages */
    .success-message { color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
    .error-message { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
    .info-message { color: #0c5460; background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
</style>