<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alpha Nutrition - Premium Sports Supplements</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <!-- Loading Overlay
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loader"></div> -->
    </div>

    <!-- Header -->    <header class="header" id="header">
        <div class="header-top">
            <div class="container">
                <nav class="navbar">
                    <a href="index.php" class="logo">
                        <img src="assets/Alpha-Logo.png" alt="Alpha Nutrition Logo" style="height:48px; width:auto;">
                    </a>
                      <div class="search-container">
                        <form action="search.php" method="GET" class="search-wrapper" id="header-search-form">
                            <div class="search-icon-container">
                                <svg viewBox="0 0 20 20" aria-hidden="true" class="search-svg">
                                    <path d="M16.72 17.78a.75.75 0 1 0 1.06-1.06l-1.06 1.06ZM9 14.5A5.5 5.5 0 0 1 3.5 9H2a7 7 0 0 0 7 7v-1.5ZM3.5 9A5.5 5.5 0 0 1 9 3.5V2a7 7 0 0 0-7 7h1.5ZM9 3.5A5.5 5.5 0 0 1 14.5 9H16a7 7 0 0 0-7-7v1.5Zm3.89 10.45 3.83 3.83 1.06-1.06-3.83-3.83-1.06 1.06ZM14.5 9a5.48 5.48 0 0 1-1.61 3.89l1.06 1.06A6.98 6.98 0 0 0 16 9h-1.5Zm-1.61 3.89A5.48 5.48 0 0 1 9 14.5V16a6.98 6.98 0 0 0 4.95-2.05l-1.06-1.06Z"></path>
                                </svg>
                            </div>
                            <input type="text" name="q" class="search-bar" placeholder="Search for Products, Brands and More" id="header-search-input" autocomplete="off">
                            <button type="submit" class="search-button">Search</button>
                            <div id="search-suggestions" class="search-suggestions-dropdown" style="display: none;"></div>
                        </form>
                    </div>
                    
                    <div class="header-icons">
                        <a href="wishlist.php" class="header-icon wishlist-icon">
                            <i class="fas fa-heart"></i>
                            <span class="wishlist-count">0</span>
                        </a>
                        <a href="login.php" class="header-icon"><i class="fas fa-user"></i></a>
                        <a href="#" class="header-icon cart-icon" onclick="toggleCartSidebar(event)">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count">0</span>
                        </a>
                    </div>
                </nav>
            </div>
        </div>
        
        <div class="header-bottom">
            <div class="container">                
                <ul class="nav-links">
    
    <li><a href="sports-supplements.php">SPORTS SUPPLEMENT</a></li>
    <li class="dropdown">
        <a class="dropdown-toggle">CATEGORIES</a>
        <div class="dropdown-menu">
            <div class="dropdown-tabs">
            </div>
            <div class="tab-content active" id="tab-men">
                <ul>
                    <li><a href="men.php">MEN</a></li>
                      <li><a href="women.php">WOMEN</a></li>
                    
                </ul>
            </div>
        </div>
    </li>

    </li>
    
    <li><a href="best-sellers.php">BEST SELLERS</a></li>
    <li><a href="featured-products.php">FEATURED</a></li>
    <li><a href="about-us.php">ABOUT US</a></li>
    <li><a href="contact.php">CONTACT</a></li>
    <li><a href="products.php">OUR PRODUCTS</a></li>
    <li><a href="blog.php">BLOGS</a></li>
</li>
</ul>
<style>
.dropdown { position: relative; }
.dropdown-menu {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    background:rgb(206, 178, 178);
    color: #fff;
    min-width:200px;
    max-width: 220px;
    z-index: 1000;
    padding: 0.1rem 0.1rem;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 16px rgba(121, 102, 102, 0.1);
}
.dropdown:hover .dropdown-menu { display: block; }
.dropdown-tabs { display: flex; gap: 1rem; margin-bottom: 1rem; }
.tab-link { background: none; border: none; color: #fff; font-weight: bold; cursor: pointer; padding: 0.5rem 1rem; border-bottom: 2px solid transparent; }
.tab-link.active { border-bottom: 2px solid #00bfff; }
.tab-content { display: none; }
.tab-content.active { display: block; }
.dropdown-menu ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.dropdown-menu ul li {
    padding: 0.5rem 0 0.5rem 1rem;
    font-size: 1rem;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    transition: background 0.2s;
}
.dropdown-menu ul li:last-child {
    border-bottom: none;
}
.dropdown-menu ul li:hover {
    background: rgba(255,255,255,0.08);
}
.nav-links > li.dropdown:hover > .dropdown-menu {
    display: block;
}
.nav-links > li.dropdown > a {
    cursor: pointer;
}

/* Wishlist Count Styles */
.wishlist-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    font-size: 11px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    min-width: 18px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
}

.wishlist-icon {
    position: relative;
}

.wishlist-count:empty {
    display: none;
}

.wishlist-icon:hover .wishlist-count {
    transform: scale(1.1);
    box-shadow: 0 3px 8px rgba(220, 53, 69, 0.4);
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabContents = document.querySelectorAll('.tab-content');
    tabLinks.forEach(link => {
        link.addEventListener('click', function() {
            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.add('active');
        });
    });

    // Load cart count on page load
    fetch('cart-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'get_cart_count'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = data.cart_count;
            }
        }
    })
    .catch(error => {
        console.error('Error loading cart count:', error);
    });

    // Load wishlist count on page load
    fetch('api/wishlist-handler.php?action=get_wishlist_count')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const wishlistCount = document.querySelector('.wishlist-count');
            if (wishlistCount) {
                wishlistCount.textContent = data.data.wishlist_count;
                // Hide count if zero
                if (data.data.wishlist_count === 0) {
                    wishlistCount.style.display = 'none';
                } else {
                    wishlistCount.style.display = 'inline-flex';
                }
            }
        }
    })
    .catch(error => {
        console.error('Error loading wishlist count:', error);
    });
});

// Cart sidebar functionality
function toggleCartSidebar(event) {
    event.preventDefault();
    const sidebar = document.getElementById('cartSidebar');
    const overlay = document.getElementById('cartOverlay');

    if (sidebar.classList.contains('active')) {
        closeCartSidebar();
    } else {
        openCartSidebar();
    }
}

function openCartSidebar() {
    const sidebar = document.getElementById('cartSidebar');
    const overlay = document.getElementById('cartOverlay');

    sidebar.classList.add('active');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Load cart contents
    loadCartContents();
}

function closeCartSidebar() {
    const sidebar = document.getElementById('cartSidebar');
    const overlay = document.getElementById('cartOverlay');

    sidebar.classList.remove('active');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

function loadCartContents() {
    fetch('get-cart-contents.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('cartContents').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading cart contents:', error);
            document.getElementById('cartContents').innerHTML = '<p>Error loading cart contents</p>';
        });
}

// Cart management functions - moved to global scope for cart sidebar
function updateCartQuantity(cartKey, change) {
    const cartItem = document.querySelector(`[data-cart-key="${cartKey}"]`);
    const quantityDisplay = cartItem.querySelector('.quantity-display');
    let currentQuantity = parseInt(quantityDisplay.textContent);
    let newQuantity = currentQuantity + change;

    if (newQuantity < 1) {
        removeCartItem(cartKey);
        return;
    }

    // Extract product ID and variant ID from cart key
    const [productId, variantId] = cartKey.split('_');

    // Update quantity via AJAX
    fetch('cart-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_quantity',
            product_id: productId,
            variant_id: variantId === 'default' ? null : variantId,
            quantity: newQuantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the display
            quantityDisplay.textContent = newQuantity;

            // Update cart count in header
            const cartCount = document.querySelector('.cart-count');
            if (cartCount && data.cart_count !== undefined) {
                cartCount.textContent = data.cart_count;
            }

            // Update price display
            const quantityPriceElement = cartItem.querySelector('.quantity-price');
            if (quantityPriceElement && data.item_total !== undefined) {
                const priceMatch = quantityPriceElement.textContent.match(/₹([\d,]+)/);
                if (priceMatch) {
                    const unitPrice = parseInt(priceMatch[1].replace(/,/g, '')) / currentQuantity;
                    quantityPriceElement.textContent = `${newQuantity} x ₹${Math.round(unitPrice).toLocaleString()}`;
                }
            }

            // Update subtotal
            updateCartSummary();
        } else {
            alert('Failed to update quantity: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update quantity');
    });
}

function removeCartItem(cartKey) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }

    // Get the cart item element
    const cartItem = document.querySelector(`[data-cart-key="${cartKey}"]`);

    // Add removing animation
    if (cartItem) {
        cartItem.style.opacity = '0.5';
        cartItem.style.pointerEvents = 'none';
        cartItem.style.transform = 'translateX(20px)';
        cartItem.style.transition = 'all 0.3s ease';
    }

    // Extract product ID and variant ID from cart key
    const [productId, variantId] = cartKey.split('_');

    // Remove item via AJAX
    fetch('cart-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'remove_from_cart',
            product_id: productId,
            variant_id: variantId === 'default' ? null : variantId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the item from DOM immediately
            if (cartItem) {
                setTimeout(() => {
                    cartItem.style.height = cartItem.offsetHeight + 'px';
                    cartItem.style.overflow = 'hidden';

                    setTimeout(() => {
                        cartItem.style.height = '0px';
                        cartItem.style.margin = '0px';
                        cartItem.style.padding = '0px';

                        setTimeout(() => {
                            cartItem.remove();

                            // Check if cart is empty and reload if needed
                            const remainingItems = document.querySelectorAll('.cart-sidebar-item');
                            if (remainingItems.length === 0) {
                                loadCartContents();
                            } else {
                                // Update subtotal without full reload
                                updateCartSummary();
                            }
                        }, 300);
                    }, 50);
                }, 300);
            }

            // Update cart count in header
            const cartCount = document.querySelector('.cart-count');
            if (cartCount && data.cart_count !== undefined) {
                cartCount.textContent = data.cart_count;

                // Add bounce animation to cart count
                cartCount.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    cartCount.style.transform = 'scale(1)';
                }, 200);
            }
        } else {
            // Restore item appearance on error
            if (cartItem) {
                cartItem.style.opacity = '1';
                cartItem.style.pointerEvents = 'auto';
                cartItem.style.transform = 'translateX(0px)';
            }
            alert('Failed to remove item: ' + data.message);
        }
    })
    .catch(error => {
        // Restore item appearance on error
        if (cartItem) {
            cartItem.style.opacity = '1';
            cartItem.style.pointerEvents = 'auto';
            cartItem.style.transform = 'translateX(0px)';
        }
        console.error('Error:', error);
        alert('Failed to remove item');
    });
}

function updateCartSummary() {
    // Calculate new subtotal from remaining items
    let newSubtotal = 0;
    const remainingItems = document.querySelectorAll('.cart-sidebar-item');

    remainingItems.forEach(item => {
        const cartKey = item.getAttribute('data-cart-key');
        const quantityElement = item.querySelector('.quantity-display');
        const priceText = item.querySelector('.quantity-price').textContent;

        if (quantityElement && priceText) {
            const quantity = parseInt(quantityElement.textContent);
            const priceMatch = priceText.match(/₹([\d,]+)/);
            if (priceMatch) {
                const price = parseInt(priceMatch[1].replace(/,/g, ''));
                newSubtotal += (price * quantity);
            }
        }
    });

    // Update the subtotal display
    const totalAmountElement = document.querySelector('.total-amount');
    if (totalAmountElement) {
        totalAmountElement.textContent = '₹' + newSubtotal.toLocaleString();

        // Add update animation
        totalAmountElement.style.transform = 'scale(1.1)';
        totalAmountElement.style.color = '#e74c3c';
        setTimeout(() => {
            totalAmountElement.style.transform = 'scale(1)';
            totalAmountElement.style.color = '#333';
        }, 300);
    }
}

function addRecommendedToCart(productId) {
    // Add recommended product to cart
    fetch('cart-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add_to_cart',
            product_id: productId,
            quantity: 1,
            variant_id: null
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in header
            const cartCount = document.querySelector('.cart-count');
            if (cartCount && data.cart_count !== undefined) {
                cartCount.textContent = data.cart_count;
                cartCount.style.transform = 'scale(1.3)';
                setTimeout(() => {
                    cartCount.style.transform = 'scale(1)';
                }, 300);
            }

            // Reload cart contents
            loadCartContents();
        } else {
            alert('Failed to add item to cart: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to add item to cart');
    });
}

function proceedToCheckout() {
    // Check if cart has items before proceeding
    const cartItems = document.querySelectorAll('.cart-sidebar-item');
    if (cartItems.length === 0) {
        alert('Your cart is empty. Please add some items before checkout.');
        return;
    }

    // Close the cart sidebar
    closeCartSidebar();

    // Redirect to checkout page
    window.location.href = 'checkout.php';
}

// Function to show cart preview when item is added
function showCartPreview() {
    openCartSidebar();

    // Auto-close after 3 seconds if user doesn't interact
    setTimeout(() => {
        const sidebar = document.getElementById('cartSidebar');
        if (sidebar && sidebar.classList.contains('active')) {
            // Only close if user hasn't interacted (no hover)
            if (!sidebar.matches(':hover')) {
                closeCartSidebar();
            }
        }
    }, 3000);
}
</script>

<!-- Cart Sidebar -->
<div id="cartOverlay" class="cart-overlay" onclick="closeCartSidebar()"></div>
<div id="cartSidebar" class="cart-sidebar">
    <div class="cart-sidebar-header">
        <h2>Your Cart</h2>
        <button class="cart-close-btn" onclick="closeCartSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="cart-sidebar-content">
        <div id="cartContents">
            <!-- Cart contents will be loaded here -->
        </div>
    </div>
</div>
            </div>
        </div>
    </header>
    