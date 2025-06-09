        </div> <!-- .main-content -->
    </div> <!-- .container -->
    <footer>
        <p>© <?php echo date("Y"); ?> SkillShare Hub - Group Project</p>
    </footer>

    <div id="cartModal" class="cart-modal">
        <div class="cart-modal-content">
            <span class="close-button">×</span>
            <h2>Your Cart</h2>
            <div id="cartItemsContainer">
                <p>Loading cart...</p>
            </div>
            <div class="cart-summary">
                <strong>Total: RM<span id="cartTotal">0.00</span></strong>
            </div>
            <div class="cart-actions">
                <button id="checkoutBtn" class="btn-primary">Proceed to Checkout</button>
                <button onclick="clearCart()" class="btn-secondary">Clear Cart</button>
            </div>
        </div>
    </div>

    <script>
        const SITE_URL = "<?php echo defined('SITE_URL') ? addslashes(SITE_URL) : ''; ?>";
        // addslashes() is a good idea if SITE_URL could ever contain quotes, though unlikely for a URL.
        // It ensures the JS string is valid.
    </script>
    <script src="<?php echo defined('SITE_URL') ? SITE_URL : ''; ?>js/script.js"></script>
    </body>
    </html>
<?php
if (isset($conn) && ($conn instanceof mysqli)) { // Check if $conn is a valid mysqli object before closing
    $conn->close();
}
?>