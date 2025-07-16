<?php
include 'includes/header.php';
// Example static categories and price ranges for women
$categories = [
    'Protein', 'Pre-Workout', 'Vitamins & Minerals', 'Weight Loss', 'Amino Acids', 'Wellness'
];
$priceRanges = [
    '$0 - $25', '$25 - $50', '$50 - $100', '$100 - $200'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Women | Alpha Nutrition</title>
  <link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&display=swap" rel="stylesheet">
  <style>
    body {
      background: #fff;
      font-family: 'Roboto', Arial, sans-serif;
      margin: 0;
      min-height: 100vh;
    }
    .women-container {
      max-width: 1400px;
      margin: 0 auto;
      display: flex;
      padding: 32px 0 0 0;
    }
    .women-sidebar {
      flex: 0 0 320px;
      padding: 0 32px 0 0;
      border-right: 1px solid #eee;
      min-height: 600px;
    }
    .women-main-content {
      flex: 1;
      padding: 0 32px;
      min-height: 600px;
      position: relative;
    }
    .women-title {
      font-size: 2.1rem;
      font-weight: 700;
      margin-bottom: 0;
      margin-top: 0;
      color: #222;
    }
    .women-subtitle {
      color: #888;
      font-size: 1.1rem;
      margin-bottom: 32px;
      margin-top: 8px;
    }
    .women-filters-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 18px;
      margin-top: 18px;
    }
    .women-filters-title {
      font-size: 1.15rem;
      font-weight: 600;
      color: #222;
    }
    .women-filters-reset {
      color: #888;
      font-size: 1rem;
      cursor: pointer;
      font-weight: 500;
      border: none;
      background: none;
      padding: 0;
      transition: color 0.2s;
    }
    .women-filters-reset:hover {
      color: #2874f0;
      text-decoration: underline;
    }
    .women-filter-section {
      margin-bottom: 32px;
    }
    .women-filter-label {
      font-size: 1.08rem;
      font-weight: 600;
      margin-bottom: 10px;
      color: #222;
      display: flex;
      align-items: center;
      justify-content: space-between;
      cursor: pointer;
    }
    .women-filter-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .women-filter-list li {
      margin-bottom: 10px;
      font-size: 1rem;
      color: #222;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .women-filter-list input[type="checkbox"] {
      accent-color: #2874f0;
      width: 16px;
      height: 16px;
    }
    .women-price-slider {
      width: 100%;
      margin: 18px 0 10px 0;
    }
    .women-price-range-btns {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 10px;
    }
    .women-price-btn {
      background: #fff;
      border: 1px solid #eee;
      border-radius: 8px;
      padding: 7px 18px;
      font-size: 1rem;
      color: #222;
      cursor: pointer;
      transition: border 0.2s, color 0.2s, background 0.2s;
    }
    .women-price-btn:hover, .women-price-btn.selected {
      border: 1.5px solid #2874f0;
      color: #2874f0;
      background: #f1f3f6;
    }
    .women-sort-row {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      margin-bottom: 32px;
      margin-top: 8px;
    }
    .women-sort-label {
      font-size: 1rem;
      color: #444;
      margin-right: 10px;
    }
    .women-sort-select {
      font-size: 1rem;
      padding: 7px 18px;
      border-radius: 8px;
      border: 1px solid #eee;
      background: #fff;
      color: #222;
      font-weight: 500;
      outline: none;
      cursor: pointer;
      transition: border 0.2s;
    }
    .women-sort-select:focus {
      border: 1.5px solid #2874f0;
    }
    .women-products-placeholder {
      color: #e53935;
      font-size: 1.3rem;
      font-weight: 600;
      text-align: center;
      margin-top: 100px;
    }
    @media (max-width: 1100px) {
      .women-container { flex-direction: column; }
      .women-sidebar { border-right: none; border-bottom: 1px solid #eee; padding: 0 0 32px 0; }
      .women-main-content { padding: 0 8vw; }
    }
    @media (max-width: 700px) {
      .women-container { flex-direction: column; }
      .women-sidebar { border-right: none; border-bottom: 1px solid #eee; padding: 0 0 24px 0; }
      .women-main-content { padding: 0 4vw; }
    }
  </style>
</head>
<body>
<div class="women-container">
  <aside class="women-sidebar">
    <h1 class="women-title">Women</h1>
    <div class="women-subtitle">Browse our collection of products for Women</div>
    <div class="women-filters-header">
      <span class="women-filters-title">Filters</span>
      <button class="women-filters-reset" onclick="window.location.reload()">Reset</button>
    </div>
    <div class="women-filter-section">
      <div class="women-filter-label">Categories</div>
      <ul class="women-filter-list">
        <?php foreach ($categories as $cat): ?>
          <li><input type="checkbox" id="cat_<?php echo htmlspecialchars($cat); ?>"> <label for="cat_<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></label></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="women-filter-section">
      <div class="women-filter-label">Price Range</div>
      <input type="range" min="0" max="200" value="200" class="women-price-slider">
      <div class="women-price-range-btns">
        <?php foreach ($priceRanges as $range): ?>
          <button class="women-price-btn"><?php echo $range; ?></button>
        <?php endforeach; ?>
      </div>
    </div>
  </aside>
  <main class="women-main-content">
    <div class="women-sort-row">
      <span class="women-sort-label">Sort by:</span>
      <select class="women-sort-select">
        <option>Featured</option>
        <option>Price: Low to High</option>
        <option>Price: High to Low</option>
        <option>Newest</option>
      </select>
    </div>
  </main>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
