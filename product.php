<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Alpha Nutrition</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>

<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loader"></div>
    </div>

    <!-- Header -->
    <header class="header" id="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.html" class="logo"><img src="assets/Alpha-Logo.png" alt="Alpha Nutrition Logo"
                        class="logo" style="height:48px; width:auto;"></a>

                <div class="search-container">
                    <input type="text" class="search-bar" placeholder="Search premium supplements...">
                </div>

                <div class="header-icons">
                    <i class="fas fa-heart header-icon"></i>
                    <i class="fas fa-user header-icon"></i>
                    <div class="header-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">0</span>
                    </div>
                </div>

                <div class="nav-menu">
                    <ul class="nav-links">
                        <li><a href="index.html#blogs">Blogs</a></li>
                        <li><a href="index.html#sports">Sports Supplements</a></li>
                        <li><a href="index.html#men">Men</a></li>
                        <li><a href="index.html#women">Women</a></li>
                        <li><a href="index.html#kids">Kids</a></li>
                        <li><a href="index.html#gummies">Gummies</a></li>
                        <li><a href="index.html#bestsellers">Best Sellers</a></li>
                        <li><a href="contact.html">Contact</a></li>
                        <li><a href="product.html" class="active">our Products </a></li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>

    <!-- Products Section -->
    <section class="products-section">
        <div class="container">
            <h1 class="section-title serif">Our Products</h1>
            <div class="products-grid">
                <!-- Product 1 -->
                <div class="product-card">
                    <div class="product-image-container">
                        <img src="assets/Black-Powder.jpg" alt="Carbamide Forte Multivitamin Tablets for Women"
                            class="product-image">
                    </div>
                    <h3 class="product-title">CARBAMIDE FORTE MULTIVITAMIN TABLETS FOR WOMEN | MULTI VITAMIN WOMEN'S
                        WELLNESS | COMPLETE MULTIVITAMIN FOR WOMEN WITH 43 INGREDIENTS | WOMEN MULTIVITAMIN TABLETS FOR
                        ENERGY & HEALTH-100 VEG TABLETS</h3>
                    <div class="rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <span class="review-count">317 reviews</span>
                    </div>
                    <div class="product-price">
                        <span class="current-price">₹599.00</span>
                        <span class="original-price">₹770.00</span>
                    </div>
                    <div class="quantity-selector">
                        <button class="quantity-btn minus">-</button>
                        <input type="number" value="1" min="1" class="quantity-input">
                        <button class="quantity-btn plus">+</button>
                    </div>
                    <button class="add-to-cart">Add to cart</button>
                </div>

                <!-- Product 2 -->
                <div class="product-card">
                    <div class="product-image-container">
                        <img src="assets/G-One-Gainer-1-Kg.jpg" alt="Carbamide Forte Multivitamin Gummies for Women"
                            class="product-image">
                    </div>
                    <h3 class="product-title">CARBAMIDE FORTE MULTIVITAMIN GUMMIES FOR WOMEN | MULTIVITAMIN FOR WOMEN'S
                        HAIR, SKIN & NAILS, WITH BIOTIN & ANTIOXIDANTS FOR IMMUNITY & PROBIOTICS FOR DIGESTION | 23
                        INGREDIENTS - 60 VEG GUMMIES</h3>
                    <div class="rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <span class="review-count">68 reviews</span>
                    </div>
                    <div class="product-price">
                        <span class="current-price">₹599.00</span>
                        <span class="original-price">₹770.00</span>
                    </div>
                    <div class="quantity-selector">
                        <button class="quantity-btn minus">-</button>
                        <input type="number" value="1" min="1" class="quantity-input">
                        <button class="quantity-btn plus">+</button>
                    </div>
                    <button class="add-to-cart">Add to cart</button>
                </div>

                <!-- Product 3 -->
                <div class="product-card">
                    <div class="product-image-container">
                        <img src="assets/Cratein-100g.jpg" alt="Carbamide Forte Multivitamin Tablets for Women"
                            class="product-image">
                    </div>
                    <h3 class="product-title">CARBAMIDE FORTE MULTIVITAMIN TABLETS FOR WOMEN | MULTI VITAMIN WOMEN'S
                        WELLNESS | COMPLETE MULTIVITAMIN FOR WOMEN WITH 43 INGREDIENTS | WOMEN MULTIVITAMIN TABLETS FOR
                        ENERGY & HEALTH - 60 VEG TABLETS</h3>
                    <div class="rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                        <span class="review-count">54 reviews</span>
                    </div>
                    <div class="product-price">
                        <span class="current-price">₹440.00</span>
                        <span class="original-price">₹575.00</span>
                    </div>
                    <div class="quantity-selector">
                        <button class="quantity-btn minus">-</button>
                        <input type="number" value="1" min="1" class="quantity-input">
                        <button class="quantity-btn plus">+</button>
                    </div>
                    <button class="add-to-cart">Add to cart</button>
                </div>

                <!-- Product 4 -->
                <div class="product-card">
                    <div class="product-image-container">
                        <img src="G-one-Gainer-1kg.jpg" alt="Carbamide Forte Chelated Iron Supplement"
                            class="product-image">
                    </div>
                    <h3 class="product-title">CARBAMIDE FORTE CHELATED IRON SUPPLEMENT FOR WOMEN AND MEN | FOLIC ACID
                        TABLETS FOR PREGNANCY | HEMOGLOBIN BOOSTER | 60 VEG TABLETS FOR HAIR GROWTH WITH IRON
                        BISGLYCINATE</h3>
                    <div class="rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <span class="review-count">105 reviews</span>
                    </div>
                    <div class="product-price">
                        <span class="current-price">₹324.00</span>
                        <span class="original-price">₹535.00</span>
                    </div>
                    <div class="quantity-selector">
                        <button class="quantity-btn minus">-</button>
                        <input type="number" value="1" min="1" class="quantity-input">
                        <button class="quantity-btn plus">+</button>
                    </div>
                    <button class="add-to-cart">Add to cart</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="serif">ALPHA NUTRITION</h3>
                    <p>Your distinguished partner in health and fitness excellence. We provide premium quality
                        supplements to help you achieve your ultimate wellness aspirations.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3 class="serif">SHOP</h3>
                    <ul>
                        <li><a href="#">All Products</a></li>
                        <li><a href="#">Protein</a></li>
                        <li><a href="#">Pre-Workout</a></li>
                        <li><a href="#">Vitamins</a></li>
                        <li><a href="#">Weight Management</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3 class="serif">COMPANY</h3>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Press</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3 class="serif">SUPPORT</h3>
                    <ul>
                        <li><a href="contact.html">Contact Us</a></li>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Returns</a></li>
                        <li><a href="#">Track Order</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 ALPHA NUTRITION. ALL RIGHTS RESERVED.</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body> 

</html>