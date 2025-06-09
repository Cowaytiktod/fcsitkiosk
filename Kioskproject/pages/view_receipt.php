<?php
// pages/view_receipt.php
global $conn; // Make $conn available

if (!isLoggedIn()) {
    redirect("index.php?page=login&message=login_required_for_receipt");
}

$user_id = $_SESSION['user_id'];
$booking_id_from_url = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
$booking_details = null;

if ($booking_id_from_url <= 0) {
    $_SESSION['error_message'] = "Invalid booking ID specified.";
    redirect("index.php?page=my_bookings");
}

// Fetch the specific booking details, ensuring it belongs to the logged-in user
$sql = "SELECT 
            b.id as booking_id, 
            b.booking_date, 
            b.total_price as price_paid, 
            b.quantity, 
            b.payment_status,
            b.transaction_id,
            w.title as workshop_title, 
            w.description as workshop_description,
            w.workshop_date as workshop_event_date,
            w.location as workshop_location,
            w.instructor_name as workshop_instructor,
            u.full_name as booked_by_name,
            u.email as booked_by_email
        FROM bookings b
        JOIN workshops w ON b.workshop_id = w.id
        JOIN users u ON b.user_id = u.id 
        WHERE b.id = ? AND b.user_id = ?";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $booking_id_from_url, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $booking_details = $result->fetch_assoc();
    } else {
        // Booking not found or doesn't belong to the user
        $_SESSION['error_message'] = "Booking not found or you do not have permission to view this receipt.";
        redirect("index.php?page=my_bookings");
    }
    $stmt->close();
} else {
    $_SESSION['error_message'] = "Error fetching booking details: " . htmlspecialchars($conn->error);
    redirect("index.php?page=my_bookings");
}

if (!$booking_details) {
    // Failsafe if somehow $booking_details is still null after checks
    echo "<p>Could not load receipt details.</p>";
    return;
}
?>

<h2>Booking Receipt - #<?php echo htmlspecialchars($booking_details['booking_id']); ?></h2>

<div class="receipt-container">
    <div class="receipt-header">
        <h3>Receipt / Booking Confirmation</h3>
        <p><strong>Booking ID:</strong> <?php echo htmlspecialchars($booking_details['booking_id']); ?></p>
        <p><strong>Transaction ID (Dummy):</strong> <?php echo htmlspecialchars($booking_details['transaction_id'] ?? 'N/A'); ?></p>
        <p><strong>Date Booked:</strong> <?php echo htmlspecialchars((new DateTime($booking_details['booking_date']))->format('F j, Y, g:i a')); ?></p>
    </div>

    <hr>

    <div class="receipt-section booked-by">
        <h4>Booked By:</h4>
        <p><?php echo htmlspecialchars($booking_details['booked_by_name']); ?></p>
        <p><?php echo htmlspecialchars($booking_details['booked_by_email']); ?></p>
    </div>

    <hr>

    <div class="receipt-section workshop-details">
        <h4>Workshop Details:</h4>
        <p><strong>Title:</strong> <?php echo htmlspecialchars($booking_details['workshop_title']); ?></p>
        <p><strong>Date & Time:</strong> <?php echo htmlspecialchars(!empty($booking_details['workshop_event_date']) ? (new DateTime($booking_details['workshop_event_date']))->format('F j, Y, g:i a') : 'To be confirmed'); ?></p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($booking_details['workshop_location'] ?? 'N/A'); ?></p>
        <p><strong>Instructor:</strong> <?php echo htmlspecialchars($booking_details['workshop_instructor'] ?? 'N/A'); ?></p>
        <p><em><?php // echo nl2br(htmlspecialchars($booking_details['workshop_description'] ?? '')); // If you want full desc ?></em></p>
    </div>

    <hr>

    <div class="receipt-section payment-details">
        <h4>Payment Summary:</h4>
        <p><strong>Quantity:</strong> <?php echo htmlspecialchars($booking_details['quantity']); ?></p>
        <p><strong>Price Paid:</strong> RM<?php echo htmlspecialchars(number_format((float)$booking_details['price_paid'], 2)); ?></p>
        <p><strong>Payment Status:</strong> <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $booking_details['payment_status']))); ?></p>
    </div>
    
    <hr>
    <div class="receipt-footer">
        <p>Thank you for your booking with SkillShare Hub!</p>
        <button onclick="window.print()" class="btn-print">Print Receipt</button>
        <a href="<?php echo SITE_URL; ?>index.php?page=my_bookings" class="btn-back">Back to My Bookings</a>
    </div>
</div>

<style>
/* Quick inline styles for receipt page - move to style.css */
.receipt-container {
    border: 1px solid #ccc;
    padding: 20px;
    margin: 20px auto;
    max-width: 1000px;
    background-color: #fff;
    font-family: Arial, sans-serif;
    line-height: 1.6;
}
.receipt-header h3, .receipt-section h4 {
    color: #337ab7; /* Theme blue */
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
    margin-bottom: 10px;
}
.receipt-section { margin-bottom: 15px; }
.receipt-container hr {
    border: 0;
    height: 1px;
    background-color: #eee;
    margin: 20px 0;
}
.receipt-footer { margin-top: 30px; text-align: center; }
.btn-print, .btn-back {
    padding: 10px 15px;
    margin: 5px;
    border-radius: 4px;
    text-decoration: none;
    cursor: pointer;
    border: 1px solid transparent;
}
.btn-print { background-color: #5cb85c; color: white; border-color: #4cae4c;}
.btn-back { background-color: #6c757d; color: white; border-color: #545b62;}

/* Styles for printing - hide non-essential elements */
@media print {
    body * { visibility: hidden; } /* Hide everything by default */
    .receipt-container, .receipt-container * { visibility: visible; } /* Show receipt and its children */
    .receipt-container {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
        border: none;
        box-shadow: none;
    }
    .receipt-footer .btn-print, .receipt-footer .btn-back, header, nav, footer, .search-filter-area /* hide specific non-receipt elements */ {
        display: none !important;
    }
     h2 { /* If the main H2 "Booking Receipt - #ID" is outside .receipt-container */
        visibility: visible;
        text-align: center;
    }
}
</style>