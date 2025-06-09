<?php
// pages/booking_success.php
global $conn;

if (!isLoggedIn()) {
    redirect("index.php?page=login");
}

// Get the booking IDs passed from process_checkout_action.php
$booking_ids_for_receipt = $_SESSION['booking_ids_for_receipt'] ?? [];
$booked_items_details = [];

if (!empty($booking_ids_for_receipt)) {
    // Create placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($booking_ids_for_receipt), '?'));
    $types = str_repeat('i', count($booking_ids_for_receipt));

    // Fetch details of the booked items for the receipt
    $sql_receipt = "SELECT b.id as booking_id, b.booking_date, b.total_price as price_paid, b.quantity, 
                           w.title as workshop_title, w.workshop_date as workshop_event_date
                    FROM bookings b
                    JOIN workshops w ON b.workshop_id = w.id
                    WHERE b.id IN ($placeholders) AND b.user_id = ?";
    
    $stmt_receipt = $conn->prepare($sql_receipt);
    if ($stmt_receipt) {
        $params = array_merge($booking_ids_for_receipt, [$_SESSION['user_id']]);
        $stmt_receipt->bind_param($types . 'i', ...$params);
        $stmt_receipt->execute();
        $result_receipt = $stmt_receipt->get_result();
        while($row = $result_receipt->fetch_assoc()){
            $booked_items_details[] = $row;
        }
        $stmt_receipt->close();
    } else {
        $_SESSION['error_message'] = "Error preparing receipt data: " . $conn->error;
    }
}

// Clear the session variable after fetching details
unset($_SESSION['booking_ids_for_receipt']);

?>

<h2>Booking Confirmed! (Receipt - Dummy)</h2>

<?php
if (isset($_SESSION['success_message'])) {
    echo '<p class="success-message">' . htmlspecialchars($_SESSION['success_message']) . '</p>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) { // Show any errors fetching receipt data
    echo '<p class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '</p>';
    unset($_SESSION['error_message']);
}
?>

<?php if (!empty($booked_items_details)): ?>
    <p>Thank you for your booking, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Member'); ?>. Here are your booking details:</p>
    <div class="receipt-details">
        <?php 
        $overall_total = 0;
        foreach ($booked_items_details as $item): 
            $overall_total += floatval($item['price_paid']);
        ?>
            <div class="receipt-item">
                <h4><?php echo htmlspecialchars($item['workshop_title']); ?></h4>
                <p>Booking ID: <?php echo htmlspecialchars($item['booking_id']); ?></p>
                <p>Booked On: <?php echo htmlspecialchars( (new DateTime($item['booking_date']))->format('D, j M Y, H:i') ); ?></p>
                <p>Workshop Date: <?php echo htmlspecialchars( !empty($item['workshop_event_date']) ? (new DateTime($item['workshop_event_date']))->format('D, j M Y, g:i A') : 'N/A'); ?></p>
                <p>Quantity: <?php echo htmlspecialchars($item['quantity']); ?></p>
                <p>Price Paid: $<?php echo htmlspecialchars(number_format(floatval($item['price_paid']), 2)); ?></p>
            </div>
            <hr>
        <?php endforeach; ?>
        <p style="text-align:right; font-size: 1.2em;"><strong>Overall Total Paid: $<?php echo htmlspecialchars(number_format($overall_total, 2)); ?></strong></p>
    </div>
    <p><a href="<?php echo SITE_URL; ?>index.php?page=my_bookings">View All My Bookings</a></p>
<?php else: ?>
    <p>Your booking was processed, but we couldn't retrieve the details for this receipt. Please check "My Bookings".</p>
<?php endif; ?>

{/* JavaScript to clear the localStorage cart on this success page */}
<script>
    if (typeof clearCart === 'function') { // Check if clearCart from script.js is available
        console.log("Booking successful, clearing client-side cart...");
        clearCart(); // This will also call saveCart with empty, updating display & total if cart modal was open
        // We don't need the alert from clearCart() here, as the success message is already on the page.
        // Modify clearCart in script.js to accept an optional 'silent' param if you want to suppress its alert.
        // For now, an alert might fire, which is okay for confirmation.
    } else {
        console.warn("clearCart function not found. Client-side cart might not be cleared automatically.");
        // You might fallback to directly clearing localStorage here if clearCart is not defined
        // localStorage.removeItem('skillshareCart');
        // if(typeof updateCartDisplayCount === 'function') updateCartDisplayCount(); // Manually update count if needed
    }
</script>
<style>
/* Quick styles for receipt - move to style.css */
.receipt-details .receipt-item { margin-bottom: 15px; padding-bottom: 10px; }
.receipt-details .receipt-item h4 { margin-top:0; color: #337ab7; }
.success-message { color: green; border: 1px solid green; padding: 10px; margin-bottom:15px; background-color: #e6ffe6;}
.error-message { color: red; border: 1px solid red; padding: 10px; margin-bottom:15px; background-color: #ffe6e6;}
</style>