<?php
include 'includes/header.php';
?>

<link rel="stylesheet" href="styles.css">
<div class="best-seller-container">
    <div class="container">
        <h1 class="page-title">Best Sellers</h1>
        <div class="best-seller-grid">
            <!-- Product Card 1 -->
            <div class="product-card">
                <div class="product-image-container">
                    <img src="assets/Whey-gold-1kg.jpg" alt="Whey Gold 1kg">
                    <div class="best-seller-badge">
                        <i class="fas fa-star"></i>
                        Best Seller
                    </div>
                </div>
                <div class="product-info">
                    <h2>Whey Gold 1kg</h2>
                    <p>High-quality protein for muscle growth and recovery.</p>
                </div>
            </div>
            <!-- Product Card 2 -->
            <div class="product-card">
                <div class="product-image-container">
                    <img src="assets/G-One-Gainer-1-Kg.jpg" alt="G-One Gainer 1kg">
                    <div class="best-seller-badge">
                        <i class="fas fa-star"></i>
                        Best Seller
                    </div>
                </div>
                <div class="product-info">
                    <h2>G-One Gainer 1kg</h2>
                    <p>Mass gainer for effective weight and muscle gain.</p>
                </div>
            </div>
            <!-- Product Card 3 -->
            <div class="product-card">
                <div class="product-image-container">
                    <img src="assets/Intense-Pre-workout.jpg" alt="Intense Pre-workout">
                    <div class="best-seller-badge">
                        <i class="fas fa-star"></i>
                        Best Seller
                    </div>
                </div>
                <div class="product-info">
                    <h2>Intense Pre-workout</h2>
                    <p>Boost your workout performance and energy levels.</p>
                </div>
            </div>
            <!-- Add more product cards as needed -->
        </div>
    </div>
</div>

<style>
/* Best Seller Section Styles - Same as index.php */
.best-seller-container {
    padding: 4rem 0;
    background: #f8f9fa;
    border-bottom: 1px solid #999999;
}

.page-title {
    text-align: center;
    font-size: 2.5rem;
    margin-bottom: 2rem;
    font-weight: bold;
    letter-spacing: 2px;
    color: #333;
}

.best-seller-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-top: 2rem;
    max-width: 1000px;
    margin-left: auto;
    margin-right: auto;
    padding: 0 1rem;
}

.product-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    border: none;
    text-align: center;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.product-image-container {
    position: relative;
    width: 100%;
    height: 260px;
    overflow: hidden;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    border-bottom: none;
}

.product-card img {
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: all 0.3s ease;
}

.product-card:hover img {
    transform: scale(1.02);
}

.best-seller-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: #ffc107;
    color: #000;
    padding: 0.4rem 0.8rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 3;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
}

.best-seller-badge i {
    margin-right: 0.3rem;
    font-size: 0.6rem;
}

.product-info {
    padding: 1rem;
    text-align: left;
}

.product-card h2 {
    font-size: 1.2rem;
    margin: 10px 0 8px;
    font-weight: 600;
    color: #333;
}

.product-card p {
    font-size: 1rem;
    color: #666;
    line-height: 1.5;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .best-seller-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 0.8rem;
        max-width: 800px;
    }
}

@media (max-width: 768px) {
    .best-seller-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.8rem;
        padding: 0 0.5rem;
        max-width: 600px;
    }
}

@media (max-width: 480px) {
    .best-seller-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
        max-width: 300px;
    }
}
</style>

<?php
include 'includes/footer.php';
?>
