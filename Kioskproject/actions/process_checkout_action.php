<?php
// actions/process_checkout_action.php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isLoggedIn()) {
    redirect("index.php?page=login&message=auth_required_for_process");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_booking'])) {
    $user_id = $_SESSION['user_id'];

    // Retrieve cart from session (set by checkout.php)
    if (!isset($_SESSION['checkout_cart']) || empty($_SESSION['checkout_cart'])) {
        $_SESSION['error_message'] = "Your cart was empty or a session error occurred. Please try again.";
        redirect("index.php?page=browse_workshops");
    }

    $cart_items = $_SESSION['checkout_cart'];
    // $cart_total = $_SESSION['checkout_total']; // Available if needed

    $all_bookings_successful = true;
    $successful_booking_ids = []; // To pass to success page for receipt

    // Start a transaction if your DB engine supports it (e.g., InnoDB for MySQL)
    // This ensures all bookings are made or none if an error occurs.
    $conn->begin_transaction();

    try {
        // Prepare statement for inserting into bookings table
        // You should verify that workshop IDs are valid and workshops exist before inserting.
        // For this example, we assume cart items from client are somewhat trustworthy for structure.
        $stmt_insert_booking = $conn->prepare(
            "INSERT INTO bookings (user_id, workshop_id, booking_date, quantity, total_price, payment_status, transaction_id)
             VALUES (?, ?, NOW(), ?, ?, 'dummy_paid', ?)"
        );

        if (!$stmt_insert_booking) {
            throw new Exception("Failed to prepare booking statement: " . $conn->error);
        }

        foreach ($cart_items as $item) {
            $workshop_id = isset($item['id']) ? intval($item['id']) : 0;
            $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1; // Default to 1
            $price_per_item = isset($item['price']) ? floatval($item['price']) : 0; // This is price per single item
            $total_item_price = $price_per_item * $quantity; // This is the subtotal for this item

            // Ideally, re-fetch workshop price from DB here to ensure integrity,
            // instead of solely relying on price from JS cart.
            // For this example, we'll use the price passed from the cart.

            if ($workshop_id <= 0 || $quantity <= 0) {
                // Skip invalid item
                error_log("Invalid item in cart during checkout: User ID $user_id, Workshop ID $workshop_id, Qty $quantity");
                continue;
            }
            
            $dummy_transaction_id = 'DUMMY-' . strtoupper(uniqid());

            $stmt_insert_booking->bind_param("iiids",
                $user_id,
                $workshop_id,
                $quantity,
                $total_item_price, // Price for these items (qty * price_per_item)
                $dummy_transaction_id
            );

            if (!$stmt_insert_booking->execute()) {
                throw new Exception("Failed to create booking for workshop ID $workshop_id: " . $stmt_insert_booking->error);
            }
            $successful_booking_ids[] = $conn->insert_id; // Get ID of this booking
        }

        $conn->commit(); // All bookings successful, commit transaction

        // Clear the session cart and potentially signal JS on success page to clear localStorage cart
        unset($_SESSION['checkout_cart']);
        unset($_SESSION['checkout_total']);
        $_SESSION['booking_ids_for_receipt'] = $successful_booking_ids; // Pass IDs to success page

        $_SESSION['success_message'] = "Your booking is confirmed (dummy payment successful)!";
        redirect("index.php?page=booking_success");

    } catch (Exception $e) {
        $conn->rollback(); // Something went wrong, roll back any DB changes
        error_log("Booking processing error for User ID $user_id: " . $e->getMessage());
        $_SESSION['error_message'] = "An error occurred while processing your booking: " . $e->getMessage() . " Please try again or contact support.";
        redirect("index.php?page=checkout"); // Redirect back to checkout page with error
    } finally {
        if (isset($stmt_insert_booking) && $stmt_insert_booking) {
            $stmt_insert_booking->close();
        }
    }

} else {
    redirect("index.php?page=member_dashboard"); // Or some other appropriate page
}
?>