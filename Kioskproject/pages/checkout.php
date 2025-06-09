<?php
// pages/checkout.php
global $conn; // Make $conn available

if (!isLoggedIn()) {
    // Should not be reached if JS redirect works, but as a server-side fallback
    redirect("index.php?page=login&message=login_required_for_checkout");
}

$cart_items = [];
$cart_total = 0.00;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_data'])) {
    $cart_data_json = $_POST['cart_data'];
    $decoded_cart = json_decode($cart_data_json, true); // true for associative array

    if (is_array($decoded_cart)) {
        $cart_items = $decoded_cart;
        // Recalculate total server-side for security, or trust JS total for this dummy app.
        // For a real app, ALWAYS recalculate from DB prices.
        // For now, let's use the prices from the submitted cart data (assuming they are reliable enough for dummy).
        foreach ($cart_items as $item) {
            $price = isset($item['price']) ? floatval($item['price']) : 0;
            $quantity = isset($item['quantity']) ? intval($item['quantity']) : 0;
            $cart_total += $price * $quantity;
        }
    } else {
        // Handle invalid cart data
        $_SESSION['error_message'] = "There was an issue with your cart data.";
        redirect("index.php?page=browse_workshops"); // Or back to cart modal somehow if it were a page
    }
} else {
    // If accessed directly via GET without cart_data POSTed, or cart_data is missing
    // This means the user navigated here without coming from the JS cart checkout button.
    // They should have an active cart from somewhere else (e.g. server-side session) or be redirected.
    // For this specific localStorage flow, it implies an issue.
    $_SESSION['info_message'] = "Your cart is empty or not properly submitted. Please try adding items again.";
    redirect("index.php?page=browse_workshops");
}

if (empty($cart_items)) {
    $_SESSION['info_message'] = "Your cart is empty. Please add items to proceed.";
    redirect("index.php?page=browse_workshops");
}

// Store cart in session so process_checkout_action.php can access it
// without needing to pass it again in a hidden form.
$_SESSION['checkout_cart'] = $cart_items;
$_SESSION['checkout_total'] = $cart_total;

?>

<h2>Checkout - Order Summary</h2>

<?php
if (isset($_SESSION['error_message'])) {
    echo '<p class="error-message">' . htmlspecialchars($_SESSION['error_message']) . '</p>';
    unset($_SESSION['error_message']);
}
?>

<div class="order-summary">
    <h3>Your Items:</h3>
    <table class="checkout-table">
        <thead>
            <tr>
                <th>Workshop</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cart_items as $item): ?>
                <?php
                    $price = isset($item['price']) ? floatval($item['price']) : 0;
                    $quantity = isset($item['quantity']) ? intval($item['quantity']) : 0;
                    $subtotal = $price * $quantity;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['title'] ?? 'Unknown Item'); ?> (ID: <?php echo htmlspecialchars($item['id'] ?? 'N/A'); ?>)</td>
                    <td><?php echo htmlspecialchars($quantity); ?></td>
                    <td>RM<?php echo htmlspecialchars(number_format($price, 2)); ?></td>
                    <td>RM<?php echo htmlspecialchars(number_format($subtotal, 2)); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right;"><strong>Total:</strong></td>
                <td><strong>RM<?php echo htmlspecialchars(number_format($cart_total, 2)); ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <hr>
    <h3>Dummy Payment</h3>
    <p>This is a simulated checkout. No real payment will be processed.</p>
    <form action="<?php echo SITE_URL; ?>actions/process_checkout_action.php" method="POST">
        {/* You could add dummy payment fields here if desired for UI simulation, but not strictly needed for the logic */}
        {/* e.g., <input type="text" name="dummy_card_name" placeholder="Name on Card (dummy)"> */}
        <button type="submit" name="confirm_booking" class="btn-confirm-booking">Confirm Booking & "Pay" (Dummy)</button>
    </form>
</div>

<style>
    /* Quick styles for checkout page - move to style.css */
    .checkout-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .checkout-table th, .checkout-table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    .checkout-table th { background-color: #f0f0f0; }
    .checkout-table tfoot td { font-weight: bold; }
    .btn-confirm-booking {
        background-color: #28a745; color: white; padding: 12px 25px; font-size: 1.1em;
        border: none; border-radius: 5px; cursor: pointer; margin-top: 20px;
    }
    .btn-confirm-booking:hover { background-color: #218838; }
    .error-message { color: red; border: 1px solid red; padding: 10px; margin-bottom:15px; background-color: #ffe6e6;}
</style>