<?php
include 'includes/header.php';
?>

<!-- Category Selection Pills -->
<section class="category-selection">
    <div class="container">
        <div class="select-concern">
            <span class="filter-icon">🔍</span>
            <span class="select-label">SELECT CONCERN:</span>
            <div class="category-pills">
                <a href="workout-performance.php" class="pill active">
                    <img src="assets/pre.jpg" alt="workout Performance" class="category-icon">
                    <span>workout Performance</span>
                    <span class="check-icon">✓</span>
                </a>
                <a href="#" class="pill">
                    <img src="assets/pro.jpg" alt="Protein" class="category-icon">
                    <span>Protein</span>
                </a>
                <a href="#" class="pill">
                    <img src="assets/gainer.jpg" alt="Gainer" class="category-icon">
                    <span>Gainer</span>
                </a>
                <a href="#" class="pill">
                    <img src="assets/wei.jpg" alt="Weight Management" class="category-icon">
                    <span>Weight Management</span>
                </a>
                <a href="#" class="pill">
                    <img src="assets/muscle.png" alt="Muscle Builder" class="category-icon">
                    <span>Muscle Builder</span>
                </a>
                <a href="Health and Beautyr.php" class="pill">
                    <img src="assets/beauty.jpg" alt="Health and Beauty" class="category-icon">
                    <span>Health and Beauty</span>
                </a>
                <a href="#" class="pill">
                    <img src="assets/tab.jpg" alt="Tablets" class="category-icon">
                    <span>Tablets</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Product Grid Section -->
<section class="products-section">
    <div class="container">
        <h2 class="section-title serif">Health and Beauty</h2>
        <div class="products-grid">
            <div class="product-card"
                style="display:flex;flex-direction:column;justify-content:space-between;height:420px;box-shadow:0 2px 12px #0001;border-radius:12px;background:#fff;">
                <div style="position:relative;background:#f7f7f7;border-radius:12px 12px 0 0;overflow:hidden;">
                    <img src="assets/G-One-Gainer-1-Kg.jpg" class="product-image" alt="G-One Gainer 1Kg"
                        style="width:100%;height:200px;object-fit:contain;background:#fff;">
                    
                </div>
                <div class="product-info"
                    style="padding:18px 18px 0 18px;flex:1;display:flex;flex-direction:column;justify-content:flex-start;">
                    <h3 class="product-title" style="font-size:1.15rem;font-weight:600;margin-bottom:8px;">Apple Cider
                        Vinegar</h3>
                    <div class="product-price" style="font-size:1.35rem;font-weight:700;margin-bottom:6px;">₹499 <span
                            style="color:#888;font-size:1rem;text-decoration:line-through;">598</span></div>
                    <div style="margin-bottom:18px;color:#888;font-size:1rem;">Earn <span
                            style="background:#fffde7;color:#bfa100;padding:2px 8px;border-radius:12px;font-weight:600;">🪙
                            25 Coins</span></div>
                </div>
                <div style="margin-top:auto;display:flex;align-items:center;gap:0.5rem;padding:0 18px 22px 18px;">
                    <button
                        style="background:none;border:none;font-size:1.5rem;color:#222;cursor:pointer;padding:0 12px 0 0;"><i
                            class="fas fa-shopping-cart"></i></button>
                    <button
                        style="flex:1;background:#8bc34a;color:#fff;font-weight:700;font-size:1.1rem;padding:12px 0;border:none;border-radius:4px;cursor:pointer;">BUY
                        NOW</button>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>