<?php
// pages/cart.php

// IMPORTANT: Ensure session_start() is called at the very beginning of index.php
// or any script that needs session access, BEFORE any output.
// Example for index.php:
// <?php
// session_start(); // This must be one of the very first lines
// require_once 'config/config.php';
// // ... rest of your index.php
// ?>

// Ensure $conn (database connection) is available
if (!isset($conn) || !($conn instanceof mysqli)) {
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Database connection error. Cannot process cart.'];
    header("Location: " . SITE_URL . "index.php"); // Redirect to homepage or a generic error page
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add_to_cart') {
        $workshop_id = filter_input(INPUT_POST, 'workshop_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

        if (!$workshop_id || $workshop_id <= 0) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid workshop ID.'];
            header("Location: " . SITE_URL . "index.php?page=browse_workshops");
            exit;
        }
        if (!$quantity || $quantity <= 0) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid quantity.'];
            // Redirect back to the workshop detail page
            header("Location: " . SITE_URL . "index.php?page=workshop_detail&id=" . $workshop_id);
            exit;
        }

        // Fetch workshop details to verify availability and get price/title
        $sql = "SELECT title, price, max_participants, current_participants 
                FROM workshops 
                WHERE id = ? AND (status = 'active' OR status = 'upcoming')";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $workshop_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $workshop_data = $result->fetch_assoc();
            $stmt->close();

            if ($workshop_data) {
                $remaining_spots = $workshop_data['max_participants'] - $workshop_data['current_participants'];
                if ($quantity > $remaining_spots) {
                    $_SESSION['message'] = ['type' => 'error', 'text' => 'Not enough spots available for the selected quantity. Only ' . $remaining_spots . ' spot(s) left.'];
                    header("Location: " . SITE_URL . "index.php?page=workshop_detail&id=" . $workshop_id);
                    exit;
                }

                // Initialize cart if it doesn't exist
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }

                // Add or update item in cart
                if (isset($_SESSION['cart'][$workshop_id])) {
                    // If item already in cart, update quantity (or decide on your logic, e.g., prevent re-adding or sum quantities)
                    // For simplicity, let's assume we're setting the new quantity directly.
                    // You might want to add to existing quantity: $_SESSION['cart'][$workshop_id]['quantity'] += $quantity;
                    $_SESSION['cart'][$workshop_id]['quantity'] = $quantity;
                     $_SESSION['message'] = ['type' => 'success', 'text' => htmlspecialchars($workshop_data['title']) . ' quantity updated in cart.'];
                } else {
                    $_SESSION['cart'][$workshop_id] = [
                        'id' => $workshop_id,
                        'title' => $workshop_data['title'],
                        'price' => $workshop_data['price'],
                        'quantity' => $quantity
                        // You might want to store the image path here too for the cart display
                    ];
                    $_SESSION['message'] = ['type' => 'success', 'text' => htmlspecialchars($workshop_data['title']) . ' added to cart!'];
                }
                
                // Recalculate total items in cart (optional, for displaying in header)
                $_SESSION['cart_item_count'] = 0;
                foreach ($_SESSION['cart'] as $item) {
                    $_SESSION['cart_item_count'] += $item['quantity'];
                }


            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Workshop not found or is no longer available.'];
            }
        } else {
            error_log("Error preparing statement for fetching workshop for cart: " . $conn->error);
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Could not retrieve workshop details.'];
        }
        
        // Redirect back to the workshop detail page (or to a cart view page)
        header("Location: " . SITE_URL . "index.php?page=workshop_detail&id=" . $workshop_id);
        exit;

    } elseif ($_POST['action'] === 'update_cart') {
        // TODO: Handle quantity updates from the cart view page
    } elseif ($_POST['action'] === 'remove_from_cart') {
        // TODO: Handle item removal from the cart view page
    }
    // Add other cart actions as needed (e.g., clear cart)

} else {
    // If someone navigates to ?page=cart directly via GET, show the cart contents
    // This part becomes your cart viewing page.
?>
    <h2>Your Shopping Cart</h2>
    <?php
    // Display session messages if any (from add to cart, etc.)
    if (isset($_SESSION['message'])) {
        echo "<p class='message-{$_SESSION['message']['type']}'>" . htmlspecialchars($_SESSION['message']['text']) . "</p>";
        unset($_SESSION['message']); // Clear the message after displaying
    }

    if (empty($_SESSION['cart'])): ?>
        <p>Your cart is currently empty.</p>
        <p><a href="<?php echo SITE_URL; ?>index.php?page=browse_workshops">Browse workshops</a></p>
    <?php else: ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Workshop</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_cart_value = 0;
                foreach ($_SESSION['cart'] as $item_id => $item):
                    $subtotal = $item['price'] * $item['quantity'];
                    $total_cart_value += $subtotal;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                    <td>MYR <?php echo htmlspecialchars(number_format((float)$item['price'], 2)); ?></td>
                    <td>
                        <!-- Basic quantity update form (can be improved with AJAX) -->
                        <form action="index.php?page=cart" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="update_cart_item">
                            <input type="hidden" name="workshop_id" value="<?php echo $item_id; ?>">
                            <input type="number" name="quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="1" style="width: 50px;">
                            <button type="submit">Update</button>
                        </form>
                    </td>
                    <td>MYR <?php echo htmlspecialchars(number_format($subtotal, 2)); ?></td>
                    <td>
                        <form action="index.php?page=cart" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="remove_cart_item">
                            <input type="hidden" name="workshop_id" value="<?php echo $item_id; ?>">
                            <button type="submit" class="btn-remove">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:right;"><strong>Total:</strong></td>
                    <td colspan="2"><strong>MYR <?php echo htmlspecialchars(number_format($total_cart_value, 2)); ?></strong></td>
                </tr>
            </tfoot>
        </table>
        <div class="cart-actions">
            <a href="<?php echo SITE_URL; ?>index.php?page=browse_workshops" class="btn-secondary">Continue Shopping</a>
            <form action="index.php?page=checkout" method="POST" style="display:inline;"> <!-- Assuming checkout page -->
                <button type="submit" class="btn-primary" <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                    Proceed to Checkout
                </button>
            </form>
        </div>

    
     
                }
    <?php endif; ?>
<?php
 // End of the main if ($_SERVER['REQUEST_METHOD'] === 'POST') else block
?>