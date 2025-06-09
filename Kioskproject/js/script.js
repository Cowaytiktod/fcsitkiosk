// js/script.js

document.addEventListener('DOMContentLoaded', function() {
    console.log('SkillShare Hub JS Loaded!');
    initializeCart();
    updateCartDisplayCount(); // Update count on page load

    // Event listener for "Add to Cart", "Remove from Cart", "Update Quantity"
    document.body.addEventListener('click', function(event) {
        // --- ADD TO CART ---
        if (event.target && event.target.classList.contains('btn-add-to-cart')) {
            const button = event.target;

            // LOGIN CHECK (Relies on 'logged-in' class on <body> and global SITE_URL)
            const isLoggedIn = document.body.classList.contains('logged-in');
            if (!isLoggedIn) {
                alert('You need to be logged in to add items to your cart. Please log in or register.');
                if (typeof SITE_URL !== 'undefined' && SITE_URL && SITE_URL !== '') {
                    if (confirm('Would you like to go to the login page? (Cancel for registration)')) {
                        window.location.href = SITE_URL + 'index.php?page=login';
                    } else {
                        window.location.href = SITE_URL + 'index.php?page=register';
                    }
                } else {
                    console.error("SITE_URL is not defined or empty for JavaScript redirect. Check footer.php and config.php.");
                }
                return; // Stop further processing for adding to cart
            }

            // If logged in, proceed to add to cart, relying on data attributes
            const workshopId = button.dataset.workshopId;
            const workshopTitle = button.dataset.workshopTitle || ('Workshop ' + (workshopId || 'Unknown'));
            const workshopPriceString = button.dataset.workshopPrice;
            const workshopPrice = parseFloat(workshopPriceString);

            // Validate fetched data
            if (!workshopId) {
                console.error("Workshop ID is missing from the 'Add to Cart' button data attribute.", button);
                alert("Could not add to cart: Critical workshop information missing.");
                return;
            }
            if (isNaN(workshopPrice)) {
                console.error("Workshop price is invalid or missing from data attribute for ID:", workshopId, "Raw price data:", workshopPriceString, button);
                alert(`Could not add "${workshopTitle}" to cart: Price information is invalid.`);
                // Defaulting price to 0 if it's truly problematic, though alerting is better
                // workshopPrice = 0.00; // Or handle as an error and don't add
                return; // Don't add item with invalid price
            }


            const workshop = {
                id: workshopId,
                title: workshopTitle,
                price: workshopPrice, // Should be a valid number here
                quantity: 1 // Default quantity
            };

            console.log("Attempting to add to cart:", workshop); // Good for debugging
            addItemToCart(workshop);

            // UI feedback
            button.textContent = 'Added!';
            button.disabled = true;
            setTimeout(() => {
                button.textContent = 'Add to Cart';
                button.disabled = false;
            }, 2000);
        }

        // --- REMOVE FROM CART (in modal) ---
        if (event.target && event.target.classList.contains('cart-remove-item')) {
            const workshopId = event.target.dataset.workshopId;
            if (workshopId) {
                removeItemFromCart(workshopId);
            }
        }
    });

    // --- UPDATE QUANTITY (in modal, on 'input' event for better UX) ---
    document.body.addEventListener('input', function(event) {
        if (event.target && event.target.classList.contains('cart-item-quantity')) {
            const workshopId = event.target.dataset.workshopId;
            const newQuantity = parseInt(event.target.value, 10); // Always specify radix for parseInt

            if (workshopId && !isNaN(newQuantity)) {
                 if (newQuantity >= 0) { // Allow setting to 0 to effectively remove
                    updateCartItemQuantity(workshopId, newQuantity);
                } else {
                    // If user types negative, reset to previous valid quantity or 1.
                    const cart = getCart();
                    const currentItem = cart.find(item => item.id === workshopId);
                    event.target.value = currentItem ? currentItem.quantity : 1;
                }
            }
        }
    });


    // --- CART MODAL HANDLING ---
    const cartModal = document.getElementById('cartModal');
    const viewCartBtn = document.getElementById('viewCartBtn');
    const closeCartBtn = cartModal ? cartModal.querySelector('.close-button') : null; // Scope to modal

    if (viewCartBtn) {
        viewCartBtn.addEventListener('click', function(e) {
            e.preventDefault();
            renderCartItems();
            if (cartModal) cartModal.style.display = 'block';
        });
    }

    if (closeCartBtn) {
        closeCartBtn.addEventListener('click', function() {
            if (cartModal) cartModal.style.display = 'none';
        });
    }

    window.addEventListener('click', function(event) {
        if (cartModal && event.target === cartModal) { // Ensure cartModal exists
            cartModal.style.display = 'none';
        }
    });

    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function() {
            const isLoggedIn = document.body.classList.contains('logged-in');
            const cart = getCart();

            if (cart.length === 0) {
                alert("Your cart is empty. Please add some workshops before proceeding to checkout.");
                return;
            }

            if (typeof SITE_URL !== 'undefined' && SITE_URL && SITE_URL !== '') {
                if (!isLoggedIn) {
                    alert('You need to be logged in to checkout. Redirecting to registration...');
                    window.location.href = SITE_URL + 'index.php?page=register';
                } else {
                    // If logged in, POST cart data to checkout.php
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = SITE_URL + 'index.php?page=checkout'; // Ensure 'checkout' is an allowed page in index.php

                    const cartDataInput = document.createElement('input');
                    cartDataInput.type = 'hidden';
                    cartDataInput.name = 'cart_data';
                    cartDataInput.value = JSON.stringify(cart); // Send cart as JSON string
                    form.appendChild(cartDataInput);

                    document.body.appendChild(form); // Form needs to be in the DOM to be submitted
                    form.submit();
                    // JavaScript will effectively stop here as the page navigates.
                    // The client-side cart (localStorage) is NOT cleared here yet.
                    // It should be cleared by JavaScript on the booking_success.php page.
                }
            } else {
                console.error("SITE_URL is not defined or empty for JavaScript. Check footer.php and config.php.");
                alert('Configuration error. Cannot proceed to checkout.');
            }
        });
    }
}); // End of DOMContentLoaded


// --- CART HELPER FUNCTIONS ---
const CART_KEY = 'skillshareCart';

function initializeCart() {
    if (!localStorage.getItem(CART_KEY)) {
        localStorage.setItem(CART_KEY, JSON.stringify([]));
    }
}

function getCart() {
    try {
        const cartData = localStorage.getItem(CART_KEY);
        return cartData ? JSON.parse(cartData) : [];
    } catch (e) {
        console.error("Error reading cart from localStorage:", e);
        return [];
    }
}

function saveCart(cart) {
    try {
        localStorage.setItem(CART_KEY, JSON.stringify(cart));
        updateCartDisplayCount();
        if (document.getElementById('cartModal')) { // Only re-render if modal might be open
            renderCartItems();
        }
    } catch (e) {
        console.error("Error saving cart to localStorage:", e);
        alert("Could not save your cart. Browser storage might be full or privacy settings are restricting it.");
    }
}

function addItemToCart(item) {
    const cart = getCart();
    const existingItemIndex = cart.findIndex(cartItem => cartItem.id === item.id);

    if (existingItemIndex > -1) {
        alert(`"${htmlspecialchars(item.title)}" is already in your cart.`);
        // If you wanted to update quantity for an existing item:
        // cart[existingItemIndex].quantity += item.quantity;
        // But for workshops, typically one booking per item.
    } else {
        if (item.quantity <= 0) {
            alert("Cannot add an item with zero or negative quantity.");
            return;
        }
        if (typeof item.price !== 'number' || isNaN(item.price)) {
            alert(`Cannot add "${htmlspecialchars(item.title)}" to cart: Invalid price data.`);
            return;
        }
        cart.push(item);
        alert(`"${htmlspecialchars(item.title)}" added to cart!`);
    }
    saveCart(cart);
}

function removeItemFromCart(itemId) {
    let cart = getCart();
    const originalLength = cart.length;
    cart = cart.filter(item => item.id !== itemId);
    if (cart.length < originalLength) {
        saveCart(cart);
    }
}

function updateCartItemQuantity(itemId, quantity) {
    let cart = getCart();
    const itemIndex = cart.findIndex(cartItem => cartItem.id === itemId);
    if (itemIndex > -1) {
        if (quantity <= 0) {
            cart.splice(itemIndex, 1);
        } else {
            cart[itemIndex].quantity = quantity;
        }
        saveCart(cart);
    }
}

function clearCart() {
    if (confirm("Are you sure you want to clear your entire cart?")) {
        saveCart([]); // Saves an empty array
        // No separate alert here, renderCartItems will show it's empty.
        const cartModal = document.getElementById('cartModal');
        if (cartModal && cartModal.style.display === 'block') {
            renderCartItems(); // Re-render the empty cart
        }
    }
}

function getCartTotal() {
    const cart = getCart();
    return cart.reduce((total, item) => {
        const price = parseFloat(item.price) || 0;
        const quantity = parseInt(item.quantity, 10) || 0;
        return total + (price * quantity);
    }, 0);
}

function updateCartDisplayCount() {
    const cart = getCart();
    const cartCountElement = document.getElementById('cartItemCount');
    if (cartCountElement) {
        let totalItems = 0;
        cart.forEach(item => { totalItems += (parseInt(item.quantity, 10) || 0); });
        cartCountElement.textContent = totalItems;
    }
}

function renderCartItems() {
    const cartItemsContainer = document.getElementById('cartItemsContainer');
    const cartTotalElement = document.getElementById('cartTotal');
    const checkoutBtn = document.getElementById('checkoutBtn');

    if (!cartItemsContainer || !cartTotalElement) {
        console.warn('Cart display elements not found for renderCartItems.');
        return;
    }

    const cart = getCart();
    cartItemsContainer.innerHTML = ''; // Clear previous items

    if (cart.length === 0) {
        const p = document.createElement('p');
        p.textContent = 'Your cart is empty.';
        cartItemsContainer.appendChild(p); // Simpler empty message
        cartTotalElement.textContent = '0.00';
        if (checkoutBtn) checkoutBtn.disabled = true;
        return;
    }

    const ul = document.createElement('ul');
    ul.className = 'cart-items-list';
    cart.forEach(item => {
        const li = document.createElement('li');
        li.className = 'cart-item';
        const itemPrice = parseFloat(item.price) || 0;
        const itemQuantity = parseInt(item.quantity, 10) || 0;
        const itemSubtotal = itemPrice * itemQuantity;

        // Using template literals for cleaner HTML structure
        li.innerHTML = `
            <span class="cart-item-title">${htmlspecialchars(item.title || 'Unknown Item')} (ID: ${htmlspecialchars(item.id || 'N/A')})</span>
            <div class="cart-item-controls">
                <span class="cart-item-price-each">RM${itemPrice.toFixed(2)}</span>
                 x 
                <input type="number" class="cart-item-quantity" value="${itemQuantity}" min="1" data-workshop-id="${htmlspecialchars(item.id || '')}" aria-label="Quantity for ${htmlspecialchars(item.title || '')}">
                <span class="cart-item-subtotal">= RM${itemSubtotal.toFixed(2)}</span>
                <button class="cart-remove-item" data-workshop-id="${htmlspecialchars(item.id || '')}" title="Remove ${htmlspecialchars(item.title || '')}">×</button>
            </div>
        `;
        ul.appendChild(li);
    });
    cartItemsContainer.appendChild(ul);
    cartTotalElement.textContent = getCartTotal().toFixed(2);
    if (checkoutBtn) checkoutBtn.disabled = false;
}

// Simplified htmlspecialchars (without single quote replacement for now)
function htmlspecialchars(str) {
    if (typeof str !== 'string') {
        if (typeof str === 'number' || typeof str === 'boolean') {
            return String(str);
        }
        return '';
    }
    let newStr = str.replace(/&/g, '&');
    newStr = newStr.replace(/</g, '<');
    newStr = newStr.replace(/>/g, '>');
    newStr = newStr.replace(/"/g, '"');
    return newStr;
}