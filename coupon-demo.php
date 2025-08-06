<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupon System Demo - Alpha Nutrition</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="js/coupon-handler.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .demo-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5rem;
        }
        
        .demo-section {
            margin: 30px 0;
            padding: 20px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            background: #f8f9fa;
        }
        
        .demo-section h3 {
            color: #495057;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .order-summary-demo {
            background: white;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #dee2e6;
            margin: 20px 0;
        }
        
        .order-summary-demo h4 {
            margin: 0 0 20px 0;
            color: #343a40;
            font-size: 1.3rem;
        }
        
        .demo-items {
            margin-bottom: 20px;
        }
        
        .demo-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .demo-item:last-child {
            border-bottom: none;
        }
        
        .item-name {
            font-weight: 500;
            color: #495057;
        }
        
        .item-price {
            font-weight: 600;
            color: #28a745;
        }
        
        .coupon-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            border: 1px solid #e9ecef;
        }
        
        .coupon-section h4 {
            margin: 0 0 15px 0;
            color: #495057;
            font-size: 16px;
            font-weight: 600;
        }
        
        .price-breakdown {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            margin: 20px 0;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .price-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 18px;
            color: #1f2937;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
        }
        
        .discount-row {
            color: #28a745 !important;
            font-weight: 600;
        }
        
        .discount-row span:last-child {
            color: #28a745 !important;
        }
        
        .available-coupons {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #2196f3;
        }
        
        .coupon-code {
            background: #fff;
            padding: 10px 15px;
            border-radius: 8px;
            margin: 10px 0;
            border: 2px dashed #2196f3;
            font-family: monospace;
            font-weight: bold;
            color: #1976d2;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .coupon-code:hover {
            background: #f3e5f5;
            border-color: #9c27b0;
            color: #7b1fa2;
        }
        
        .coupon-details {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
        
        .instructions {
            background: #fff3cd;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #ffc107;
            margin: 20px 0;
        }
        
        .instructions h4 {
            color: #856404;
            margin-top: 0;
        }
        
        .instructions ul {
            color: #856404;
            margin: 0;
        }
        
        .demo-note {
            background: #d1ecf1;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #17a2b8;
            margin: 15px 0;
            font-size: 0.95rem;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <h1><i class="fas fa-ticket-alt"></i> Coupon System Demo</h1>
        
        <div class="demo-section">
            <h3><i class="fas fa-info-circle"></i> Available Test Coupons</h3>
            <div class="available-coupons">
                <div class="coupon-code" onclick="applyCouponCode('WELCOME10')">
                    WELCOME10
                    <div class="coupon-details">10% off on orders above ₹500</div>
                </div>
                <div class="coupon-code" onclick="applyCouponCode('SAVE50')">
                    SAVE50
                    <div class="coupon-details">₹50 off on orders above ₹1000</div>
                </div>
                <div class="coupon-code" onclick="applyCouponCode('MEGA20')">
                    MEGA20
                    <div class="coupon-details">20% off on all products</div>
                </div>
            </div>
            <div class="demo-note">
                <strong>Note:</strong> Click on any coupon code above to automatically apply it to the demo order below.
            </div>
        </div>
        
        <div class="demo-section">
            <h3><i class="fas fa-shopping-cart"></i> Demo Order Summary</h3>
            
            <div class="order-summary-demo">
                <h4>Your Order</h4>
                
                <div class="demo-items">
                    <div class="demo-item">
                        <span class="item-name">Alpha Whey Protein (1kg)</span>
                        <span class="item-price">₹2,499</span>
                    </div>
                    <div class="demo-item">
                        <span class="item-name">Alpha BCAA (30 servings)</span>
                        <span class="item-price">₹1,299</span>
                    </div>
                    <div class="demo-item">
                        <span class="item-name">Alpha Multivitamin (60 tablets)</span>
                        <span class="item-price">₹899</span>
                    </div>
                </div>
                
                <!-- Coupon Section -->
                <div class="coupon-section">
                    <h4>Have a Coupon Code?</h4>
                    <div class="coupon-input-group">
                        <input type="text" id="coupon_code" class="coupon-input" placeholder="Enter coupon code" maxlength="50">
                        <button type="button" id="apply_coupon" class="coupon-btn coupon-btn-apply">
                            <i class="fas fa-tag"></i> Apply
                        </button>
                        <button type="button" id="remove_coupon" class="coupon-btn coupon-btn-remove" style="display: none;">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </div>
                    <div id="coupon_message"></div>
                </div>
                
                <div class="price-breakdown">
                    <div class="price-row">
                        <span>Subtotal</span>
                        <span id="subtotal_amount">₹4,697</span>
                    </div>
                    <div class="price-row">
                        <span>Shipping</span>
                        <span class="free-shipping">FREE</span>
                    </div>
                    <div class="price-row discount-row" id="discount_row" style="display: none;">
                        <span>Discount</span>
                        <span id="discount_amount">-₹0.00</span>
                    </div>
                    <div class="price-row">
                        <span>Total</span>
                        <span id="total_amount">₹4,697</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="instructions">
            <h4><i class="fas fa-lightbulb"></i> How to Test</h4>
            <ul>
                <li>Click on any coupon code above to auto-fill it</li>
                <li>Or manually enter a coupon code in the input field</li>
                <li>Click "Apply" to validate and apply the coupon</li>
                <li>Watch the price breakdown update in real-time</li>
                <li>Try different coupons to see various discount types</li>
                <li>Use "Remove" button to clear applied coupons</li>
            </ul>
        </div>
        
        <div class="demo-note">
            <strong>Integration:</strong> This same coupon system is integrated into your checkout page. 
            Customers can apply coupons during checkout, and the discounts will be automatically calculated and applied to their orders.
        </div>
    </div>
    
    <script>
        // Initialize coupon handler
        const couponHandler = new CouponHandler({
            apiUrl: 'admin/api/coupon-api.php',
            couponInputId: 'coupon_code',
            applyButtonId: 'apply_coupon',
            removeButtonId: 'remove_coupon',
            messageContainerId: 'coupon_message',
            totalAmountId: 'total_amount',
            discountAmountId: 'discount_amount',
            finalAmountId: 'total_amount'
        });
        
        // Function to apply coupon code from clicking on demo coupons
        function applyCouponCode(code) {
            const couponInput = document.getElementById('coupon_code');
            couponInput.value = code;
            couponInput.focus();
            
            // Add a small delay to show the code being filled
            setTimeout(() => {
                couponHandler.applyCoupon();
            }, 500);
        }
        
        // Custom discount row toggle for demo
        const originalUpdatePriceDisplay = couponHandler.updatePriceDisplay;
        couponHandler.updatePriceDisplay = function(discountAmount, finalAmount) {
            const discountRow = document.getElementById('discount_row');
            const discountElement = document.getElementById('discount_amount');
            const finalElement = document.getElementById('total_amount');
            
            if (discountElement) {
                if (discountAmount > 0) {
                    discountElement.textContent = `-₹${discountAmount.toFixed(2)}`;
                    discountRow.style.display = 'flex';
                } else {
                    discountRow.style.display = 'none';
                }
            }
            
            if (finalElement) {
                finalElement.textContent = `₹${finalAmount.toFixed(2)}`;
            }
        };
    </script>
</body>
</html>
