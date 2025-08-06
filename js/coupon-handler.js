/**
 * Coupon Handler for Alpha Nutrition
 * Handles coupon validation and application on checkout
 */

class CouponHandler {
    constructor(options = {}) {
        this.apiUrl = options.apiUrl || '/admin/api/coupon-api.php';
        this.couponInputId = options.couponInputId || 'coupon_code';
        this.applyButtonId = options.applyButtonId || 'apply_coupon';
        this.removeButtonId = options.removeButtonId || 'remove_coupon';
        this.messageContainerId = options.messageContainerId || 'coupon_message';
        this.totalAmountId = options.totalAmountId || 'total_amount';
        this.discountAmountId = options.discountAmountId || 'discount_amount';
        this.finalAmountId = options.finalAmountId || 'final_amount';
        
        this.appliedCoupon = null;
        this.originalAmount = 0;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.updateOriginalAmount();
    }
    
    bindEvents() {
        const applyButton = document.getElementById(this.applyButtonId);
        const removeButton = document.getElementById(this.removeButtonId);
        const couponInput = document.getElementById(this.couponInputId);
        
        if (applyButton) {
            applyButton.addEventListener('click', () => this.applyCoupon());
        }
        
        if (removeButton) {
            removeButton.addEventListener('click', () => this.removeCoupon());
        }
        
        if (couponInput) {
            couponInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.applyCoupon();
                }
            });
            
            // Convert to uppercase as user types
            couponInput.addEventListener('input', (e) => {
                e.target.value = e.target.value.toUpperCase();
            });
        }
    }
    
    updateOriginalAmount() {
        const totalElement = document.getElementById(this.totalAmountId);
        if (totalElement) {
            this.originalAmount = parseFloat(totalElement.textContent.replace(/[^\d.]/g, '')) || 0;
        }
    }
    
    async applyCoupon() {
        const couponInput = document.getElementById(this.couponInputId);
        const applyButton = document.getElementById(this.applyButtonId);
        
        if (!couponInput || !couponInput.value.trim()) {
            this.showMessage('Please enter a coupon code', 'error');
            return;
        }
        
        const couponCode = couponInput.value.trim().toUpperCase();
        
        // Show loading state
        if (applyButton) {
            applyButton.disabled = true;
            applyButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';
        }
        
        try {
            this.updateOriginalAmount();
            
            const response = await fetch(`${this.apiUrl}?action=validate&code=${encodeURIComponent(couponCode)}&amount=${this.originalAmount}`);
            const data = await response.json();
            
            if (data.success) {
                this.appliedCoupon = data.coupon;
                this.updatePriceDisplay(data.discount_amount, data.final_amount);
                this.showMessage(`Coupon applied! You saved ₹${data.discount_amount.toFixed(2)}`, 'success');
                this.toggleCouponButtons(true);
            } else {
                this.showMessage(data.message, 'error');
            }
        } catch (error) {
            console.error('Coupon validation error:', error);
            this.showMessage('Failed to validate coupon. Please try again.', 'error');
        } finally {
            // Reset button state
            if (applyButton) {
                applyButton.disabled = false;
                applyButton.innerHTML = '<i class="fas fa-tag"></i> Apply Coupon';
            }
        }
    }
    
    removeCoupon() {
        this.appliedCoupon = null;
        this.updatePriceDisplay(0, this.originalAmount);
        this.showMessage('Coupon removed', 'info');
        this.toggleCouponButtons(false);
        
        // Clear coupon input
        const couponInput = document.getElementById(this.couponInputId);
        if (couponInput) {
            couponInput.value = '';
        }
    }
    
    updatePriceDisplay(discountAmount, finalAmount) {
        const discountElement = document.getElementById(this.discountAmountId);
        const finalElement = document.getElementById(this.finalAmountId);
        
        if (discountElement) {
            if (discountAmount > 0) {
                discountElement.textContent = `₹${discountAmount.toFixed(2)}`;
                discountElement.parentElement.style.display = 'flex';
            } else {
                discountElement.parentElement.style.display = 'none';
            }
        }
        
        if (finalElement) {
            finalElement.textContent = `₹${finalAmount.toFixed(2)}`;
        }
        
        // Update any hidden form fields for order processing
        this.updateHiddenFields(discountAmount, finalAmount);
    }
    
    updateHiddenFields(discountAmount, finalAmount) {
        // Update hidden fields that might be used in form submission
        let couponIdField = document.querySelector('input[name="coupon_id"]');
        let discountField = document.querySelector('input[name="discount_amount"]');
        let finalAmountField = document.querySelector('input[name="final_amount"]');
        
        if (!couponIdField) {
            couponIdField = document.createElement('input');
            couponIdField.type = 'hidden';
            couponIdField.name = 'coupon_id';
            document.querySelector('form').appendChild(couponIdField);
        }
        
        if (!discountField) {
            discountField = document.createElement('input');
            discountField.type = 'hidden';
            discountField.name = 'discount_amount';
            document.querySelector('form').appendChild(discountField);
        }
        
        if (!finalAmountField) {
            finalAmountField = document.createElement('input');
            finalAmountField.type = 'hidden';
            finalAmountField.name = 'final_amount';
            document.querySelector('form').appendChild(finalAmountField);
        }
        
        couponIdField.value = this.appliedCoupon ? this.appliedCoupon.coupon_id : '';
        discountField.value = discountAmount.toFixed(2);
        finalAmountField.value = finalAmount.toFixed(2);
    }
    
    toggleCouponButtons(couponApplied) {
        const applyButton = document.getElementById(this.applyButtonId);
        const removeButton = document.getElementById(this.removeButtonId);
        const couponInput = document.getElementById(this.couponInputId);
        
        if (applyButton) {
            applyButton.style.display = couponApplied ? 'none' : 'inline-flex';
        }
        
        if (removeButton) {
            removeButton.style.display = couponApplied ? 'inline-flex' : 'none';
        }
        
        if (couponInput) {
            couponInput.disabled = couponApplied;
        }
    }
    
    showMessage(message, type = 'info') {
        const messageContainer = document.getElementById(this.messageContainerId);
        if (!messageContainer) return;
        
        // Clear existing message
        messageContainer.innerHTML = '';
        
        const messageElement = document.createElement('div');
        messageElement.className = `coupon-message coupon-message-${type}`;
        
        const icon = this.getMessageIcon(type);
        messageElement.innerHTML = `
            <i class="${icon}"></i>
            <span>${message}</span>
        `;
        
        messageContainer.appendChild(messageElement);
        
        // Auto-hide success and info messages after 5 seconds
        if (type === 'success' || type === 'info') {
            setTimeout(() => {
                if (messageElement.parentNode) {
                    messageElement.remove();
                }
            }, 5000);
        }
    }
    
    getMessageIcon(type) {
        const icons = {
            'success': 'fas fa-check-circle',
            'error': 'fas fa-exclamation-circle',
            'info': 'fas fa-info-circle',
            'warning': 'fas fa-exclamation-triangle'
        };
        return icons[type] || icons.info;
    }
    
    // Public method to get applied coupon data
    getAppliedCoupon() {
        return this.appliedCoupon;
    }
    
    // Public method to check if coupon is applied
    hasCouponApplied() {
        return this.appliedCoupon !== null;
    }
    
    // Public method to get discount amount
    getDiscountAmount() {
        if (!this.appliedCoupon) return 0;
        
        let discount = 0;
        if (this.appliedCoupon.discount_type === 'percentage') {
            discount = (this.originalAmount * this.appliedCoupon.discount_value) / 100;
        } else {
            discount = this.appliedCoupon.discount_value;
        }
        
        return Math.min(discount, this.originalAmount);
    }
}

// CSS styles for coupon messages
const couponStyles = `
    .coupon-message {
        padding: 12px 16px;
        border-radius: 8px;
        margin: 10px 0;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        animation: slideIn 0.3s ease;
    }
    
    .coupon-message-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    
    .coupon-message-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }
    
    .coupon-message-info {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #93c5fd;
    }
    
    .coupon-message-warning {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .coupon-input-group {
        display: flex;
        gap: 10px;
        align-items: center;
        margin: 15px 0;
    }
    
    .coupon-input {
        flex: 1;
        padding: 12px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        text-transform: uppercase;
        font-family: monospace;
        font-weight: 600;
    }
    
    .coupon-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .coupon-input:disabled {
        background: #f9fafb;
        color: #6b7280;
    }
    
    .coupon-btn {
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }
    
    .coupon-btn-apply {
        background: #3b82f6;
        color: white;
    }
    
    .coupon-btn-apply:hover:not(:disabled) {
        background: #2563eb;
        transform: translateY(-1px);
    }
    
    .coupon-btn-apply:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        transform: none;
    }
    
    .coupon-btn-remove {
        background: #ef4444;
        color: white;
    }
    
    .coupon-btn-remove:hover {
        background: #dc2626;
        transform: translateY(-1px);
    }
    
    .price-breakdown {
        background: #f9fafb;
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
        color: #059669;
        font-weight: 600;
    }
`;

// Inject styles
if (!document.getElementById('coupon-styles')) {
    const styleSheet = document.createElement('style');
    styleSheet.id = 'coupon-styles';
    styleSheet.textContent = couponStyles;
    document.head.appendChild(styleSheet);
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CouponHandler;
} else if (typeof window !== 'undefined') {
    window.CouponHandler = CouponHandler;
}
