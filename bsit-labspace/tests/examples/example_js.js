/**
 * Shopping Cart Module
 * This module handles shopping cart operations including:
 * - Adding items
 * - Removing items
 * - Calculating totals
 * - Applying discounts
 * - Formatting currency
 */

// Cart data structure
let cart = [];

/**
 * Add item to shopping cart
 * @param {string} id - Product ID
 * @param {string} name - Product name
 * @param {number} price - Product price
 * @param {number} quantity - Quantity to add (default: 1)
 * @return {Object} The added item
 */
function addItem(id, name, price, quantity = 1) {
    // Check if item already exists in cart
    const existingItemIndex = cart.findIndex(item => item.id === id);
    
    if (existingItemIndex >= 0) {
        // Update quantity if item exists
        cart[existingItemIndex].quantity += quantity;
        return cart[existingItemIndex];
    } else {
        // Add new item if it doesn't exist
        const newItem = { id, name, price, quantity };
        cart.push(newItem);
        return newItem;
    }
}

/**
 * Remove item from shopping cart
 * @param {string} id - Product ID to remove
 * @return {boolean} True if item was removed, false if not found
 */
function removeItem(id) {
    const initialLength = cart.length;
    cart = cart.filter(item => item.id !== id);
    return cart.length !== initialLength;
}

/**
 * Update item quantity
 * @param {string} id - Product ID
 * @param {number} quantity - New quantity
 * @return {boolean} True if updated, false if item not found
 */
function updateQuantity(id, quantity) {
    if (quantity <= 0) {
        return removeItem(id);
    }
    
    const item = cart.find(item => item.id === id);
    if (item) {
        item.quantity = quantity;
        return true;
    }
    return false;
}

/**
 * Calculate total price of items in cart
 * @return {number} Total price
 */
function calculateTotal() {
    return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
}

/**
 * Apply discount to cart total
 * @param {number} percentage - Discount percentage (0-100)
 * @return {number} Discounted total
 */
function applyDiscount(percentage) {
    if (percentage < 0 || percentage > 100) {
        throw new Error('Discount percentage must be between 0 and 100');
    }
    
    const total = calculateTotal();
    const discount = (percentage / 100) * total;
    return total - discount;
}

/**
 * Format currency value
 * @param {number} amount - Amount to format
 * @param {string} currencyCode - Currency code (default: USD)
 * @return {string} Formatted currency string
 */
function formatCurrency(amount, currencyCode = 'USD') {
    return new Intl.NumberFormat('en-US', { 
        style: 'currency', 
        currency: currencyCode 
    }).format(amount);
}

/**
 * Get items in cart
 * @return {Array} Array of cart items
 */
function getCartItems() {
    return [...cart]; // Return a copy to prevent direct mutation
}

/**
 * Clear all items from cart
 */
function clearCart() {
    cart = [];
}

/**
 * Validate email format
 * @param {string} email - Email to validate
 * @return {boolean} True if valid, false otherwise
 */
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Safe JSON parser that won't throw errors
 * @param {string} json - JSON string to parse
 * @return {Object|null} Parsed object or null if invalid
 */
function safelyParseJSON(json) {
    try {
        return JSON.parse(json);
    } catch (e) {
        return null;
    }
}

/**
 * Sort array of objects by property
 * @param {Array} array - Array of objects
 * @param {string} property - Property to sort by
 * @return {Array} Sorted array
 */
function sortByProperty(array, property) {
    return [...array].sort((a, b) => {
        if (a[property] < b[property]) return -1;
        if (a[property] > b[property]) return 1;
        return 0;
    });
}

/**
 * Create and append DOM element
 * @param {string} tag - Element tag
 * @param {string} id - Element ID
 * @param {string} content - Text content
 * @return {Element} Created element
 */
function createElement(tag, id, content) {
    const el = document.createElement(tag);
    el.id = id;
    el.textContent = content;
    document.body.appendChild(el);
    return el;
}

/**
 * Setup event listeners for cart buttons
 */
function setupEventListeners() {
    const addButtons = document.querySelectorAll('.add-to-cart');
    const removeButtons = document.querySelectorAll('.remove-from-cart');
    
    addButtons.forEach(button => {
        button.addEventListener('click', handleAddToCart);
    });
    
    removeButtons.forEach(button => {
        button.addEventListener('click', handleRemoveFromCart);
    });
    
    // Checkout button
    const checkoutBtn = document.getElementById('checkout-btn');
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', handleCheckout);
    }
}

/**
 * Fetch data from API
 * @param {string} url - API URL
 * @param {Object} options - Fetch options
 * @return {Promise} Promise resolving to response data
 */
function fetchData(url, options = {}) {
    return fetch(url, options)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }
            return response.json();
        });
}

/**
 * Export module functions
 */
export {
    addItem,
    removeItem,
    updateQuantity,
    calculateTotal,
    applyDiscount,
    formatCurrency,
    getCartItems,
    clearCart,
    validateEmail
};
