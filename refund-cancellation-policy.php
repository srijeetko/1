<?php
$pageTitle = "Refund & Cancellation Policy - Alpha Nutrition";
include 'includes/header.php';
?>

<style>
/* Premium Refund & Cancellation Policy Design */

/* Hero Section Styles */
.refund-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 80px 0;
    text-align: center;
    position: relative;
    overflow: hidden;
    margin-bottom: 0;
}

.refund-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.refund-hero-content {
    position: relative;
    z-index: 2;
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.refund-hero h1 {
    font-size: 3.5rem;
    font-weight: 700;
    color: white;
    margin-bottom: 1rem;
    font-family: 'Playfair Display', serif;
    text-shadow: 0 4px 20px rgba(0,0,0,0.3);
    letter-spacing: -1px;
    animation: titleGlow 3s ease-in-out infinite alternate;
}

@keyframes titleGlow {
    0% { text-shadow: 0 4px 20px rgba(0,0,0,0.3); }
    100% { text-shadow: 0 4px 30px rgba(255,255,255,0.2), 0 4px 20px rgba(0,0,0,0.3); }
}

.refund-hero .subtitle {
    font-size: 1.4rem;
    color: rgba(255,255,255,0.9);
    margin-bottom: 2rem;
    font-weight: 300;
    letter-spacing: 0.5px;
}

.effective-date {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.2);
    color: white;
    padding: 12px 30px;
    border-radius: 50px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    font-size: 1rem;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.effective-date:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    background: rgba(255,255,255,0.2);
}

.effective-date i {
    font-size: 1.1rem;
}

.refund-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.refund-content {
    background: white;
    margin: 50px auto 50px;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
    padding: 60px;
    position: relative;
    z-index: 3;
    line-height: 1.8;
    color: #444;
}

.refund-nav {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 40px;
    border-left: 5px solid #007cba;
}

.refund-nav h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.3em;
}

.refund-nav ul {
    list-style: none;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
}

.refund-nav li {
    margin: 0;
}

.refund-nav a {
    color: #007cba;
    text-decoration: none;
    padding: 8px 15px;
    border-radius: 8px;
    display: block;
    transition: all 0.3s ease;
    font-weight: 500;
}

.refund-nav a:hover {
    background: #007cba;
    color: white;
    transform: translateX(5px);
}

.section-card {
    background: #fff;
    border-radius: 15px;
    padding: 40px;
    margin: 30px 0;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.section-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.12);
}

.section-card h2 {
    color: #2c3e50;
    font-size: 1.8em;
    margin-bottom: 25px;
    font-family: 'Playfair Display', serif;
    position: relative;
    padding-bottom: 15px;
}

.section-card h2::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 2px;
}

.section-card h3 {
    color: #34495e;
    font-size: 1.3em;
    margin: 25px 0 15px 0;
    font-weight: 600;
}

.section-card p {
    margin-bottom: 18px;
    text-align: justify;
    font-size: 1.05em;
}

.section-card ul {
    margin: 20px 0;
    padding-left: 0;
}

.section-card li {
    margin-bottom: 12px;
    padding-left: 30px;
    position: relative;
    list-style: none;
}

.section-card li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: #28a745;
    font-weight: bold;
    font-size: 1.2em;
}

.warning-box {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    padding: 30px;
    border-radius: 15px;
    margin: 30px 0;
    border-left: 5px solid #ffc107;
    position: relative;
    overflow: hidden;
}

.warning-box::before {
    content: '⚠️';
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 2em;
    opacity: 0.3;
}

.warning-box p {
    margin: 0;
    font-weight: 500;
    color: #856404;
}

.contact-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px;
    padding: 40px;
    margin: 40px 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.contact-card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(180deg); }
}

.contact-card h3 {
    color: white;
    margin-bottom: 25px;
    font-size: 1.5em;
    position: relative;
    z-index: 2;
}

.contact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 30px;
    position: relative;
    z-index: 2;
}

.contact-item {
    background: rgba(255,255,255,0.1);
    padding: 20px;
    border-radius: 12px;
    backdrop-filter: blur(10px);
    word-wrap: break-word;
    overflow-wrap: break-word;
    min-height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

.contact-item i {
    font-size: 1.5em;
    margin-bottom: 10px;
    display: block;
    color: white;
}

.contact-item strong {
    display: block;
    margin-bottom: 8px;
    font-size: 0.9em;
    opacity: 0.8;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.contact-item a {
    color: white;
    text-decoration: none;
    font-weight: 500;
    word-break: break-all;
    line-height: 1.4;
    font-size: 0.95rem;
}

.contact-item a:hover {
    text-decoration: underline;
    color: #fff;
}

.highlight-box {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    padding: 30px;
    border-radius: 15px;
    margin: 30px 0;
    border-left: 5px solid #2196f3;
    position: relative;
    overflow: hidden;
}

.highlight-box::before {
    content: '💰';
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 2em;
    opacity: 0.3;
}

.highlight-box p {
    margin: 0;
    font-weight: 500;
    color: #1565c0;
}

.back-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    z-index: 1000;
}

.back-to-top:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .refund-hero {
        padding: 60px 0;
    }

    .refund-hero h1 {
        font-size: 2.5rem;
    }

    .refund-hero .subtitle {
        font-size: 1.2rem;
        margin-bottom: 1.5rem;
    }

    .effective-date {
        padding: 10px 20px;
        font-size: 0.9rem;
    }

    .refund-hero-content {
        padding: 0 15px;
    }

    .contact-grid {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 15px;
    }
}

@media (max-width: 480px) {
    .refund-hero h1 {
        font-size: 2rem;
        letter-spacing: 0;
    }

    .refund-hero .subtitle {
        font-size: 1.1rem;
    }

    .effective-date {
        padding: 8px 16px;
        font-size: 0.85rem;
    }

    .contact-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
}
</style>

<!-- Hero Section -->
<div class="refund-hero">
    <div class="refund-hero-content">
        <h1>Refund & Cancellation Policy</h1>
        <p class="subtitle">Your Rights and Our Commitments</p>
        <div class="effective-date">
            <i class="fas fa-calendar-alt"></i>
            Effective Date: August 24, 2024
        </div>
    </div>
</div>

<div class="refund-container">
    <div class="refund-content">
        <!-- Introduction -->
        <div class="highlight-box">
            <p><strong>This refund and cancellation policy outlines how you can cancel or seek a refund for a product/service that you have purchased through the Platform. Please read this policy carefully to understand your rights and our procedures.</strong></p>
        </div>

        <!-- Quick Navigation -->
        <div class="refund-nav">
            <h3><i class="fas fa-list"></i> Quick Navigation</h3>
            <ul>
                <li><a href="#cancellation"><i class="fas fa-times-circle"></i> Cancellation Policy</a></li>
                <li><a href="#perishable"><i class="fas fa-leaf"></i> Perishable Items</a></li>
                <li><a href="#damaged"><i class="fas fa-exclamation-triangle"></i> Damaged/Defective Items</a></li>
                <li><a href="#warranty"><i class="fas fa-shield-alt"></i> Warranty Issues</a></li>
                <li><a href="#refund-process"><i class="fas fa-money-bill-wave"></i> Refund Process</a></li>
                <li><a href="#contact"><i class="fas fa-phone"></i> Customer Service</a></li>
            </ul>
        </div>

        <!-- Cancellation Policy Section -->
        <div class="section-card" id="cancellation">
            <h2><i class="fas fa-times-circle"></i> 1. Cancellation Policy</h2>
            <p>Cancellations will only be considered if the request is made within <strong>7 days of placing the order</strong>. However, there are certain conditions that apply:</p>

            <h3>When Cancellations Are Accepted:</h3>
            <ul>
                <li>Request made within 7 days of order placement</li>
                <li>Order has not been communicated to sellers/merchants</li>
                <li>Shipping process has not been initiated</li>
                <li>Product is not out for delivery</li>
            </ul>

            <h3>When Cancellations May Not Be Entertained:</h3>
            <ul>
                <li>Orders already communicated to sellers/merchants on the Platform</li>
                <li>Shipping process has been initiated</li>
                <li>Product is out for delivery</li>
            </ul>

            <div class="warning-box">
                <p><strong>Alternative Option:</strong> If your cancellation request cannot be processed due to the above reasons, you may choose to reject the product at the doorstep during delivery.</p>
            </div>
        </div>

        <!-- Perishable Items Section -->
        <div class="section-card" id="perishable">
            <h2><i class="fas fa-leaf"></i> 2. Perishable Items Policy</h2>
            <p>PURE NUTRITION CO has specific policies for perishable items to ensure quality and safety:</p>

            <h3>Items Not Eligible for Cancellation:</h3>
            <ul>
                <li>Flowers and floral arrangements</li>
                <li>Eatables and food products</li>
                <li>Other perishable items with limited shelf life</li>
            </ul>

            <h3>Quality-Based Refund/Replacement:</h3>
            <p>While cancellations are not accepted for perishable items, refund or replacement can be made if you can establish that the quality of the product delivered is not good.</p>

            <div class="warning-box">
                <p><strong>Quality Assurance:</strong> We are committed to delivering fresh, high-quality perishable items. If you receive items that don't meet our quality standards, please contact our customer service immediately.</p>
            </div>
        </div>

        <!-- Damaged/Defective Items Section -->
        <div class="section-card" id="damaged">
            <h2><i class="fas fa-exclamation-triangle"></i> 3. Damaged or Defective Items</h2>
            <p>In case of receipt of damaged or defective items, we have a structured process to address your concerns:</p>

            <h3>Reporting Process:</h3>
            <ul>
                <li>Report the issue to our customer service team immediately</li>
                <li>Provide clear photos or evidence of the damage/defect</li>
                <li>Include order details and product information</li>
                <li>Report must be made within <strong>7 days of receipt</strong></li>
            </ul>

            <h3>Resolution Process:</h3>
            <ul>
                <li>Our customer service team will review your complaint</li>
                <li>The seller/merchant listed on the Platform will check and determine the issue</li>
                <li>Resolution will be provided based on the assessment</li>
                <li>Appropriate action will be taken (refund, replacement, or other remedy)</li>
            </ul>

            <h3>Product Expectations:</h3>
            <p>If you feel that the product received is not as shown on the site or does not meet your expectations, you must bring it to the notice of our customer service within <strong>7 days of receiving the product</strong>. Our team will investigate your complaint and take appropriate action.</p>
        </div>

        <!-- Warranty Issues Section -->
        <div class="section-card" id="warranty">
            <h2><i class="fas fa-shield-alt"></i> 4. Warranty Issues</h2>
            <p>For products that come with manufacturer warranties, we have specific procedures:</p>

            <h3>Manufacturer Warranty Products:</h3>
            <ul>
                <li>Products covered under manufacturer warranty should be referred directly to the manufacturer</li>
                <li>Warranty claims must be processed through the manufacturer's authorized service centers</li>
                <li>PURE NUTRITION CO will assist in providing necessary purchase documentation</li>
                <li>Warranty terms and conditions are as per the manufacturer's policy</li>
            </ul>

            <div class="warning-box">
                <p><strong>Important:</strong> In case of complaints regarding products that come with a warranty from the manufacturers, please refer the issue directly to them for faster resolution.</p>
            </div>
        </div>

        <!-- Refund Process Section -->
        <div class="section-card" id="refund-process">
            <h2><i class="fas fa-money-bill-wave"></i> 5. Refund Process</h2>
            <p>When refunds are approved by PURE NUTRITION CO, we follow a structured timeline:</p>

            <h3>Refund Timeline:</h3>
            <ul>
                <li>Approved refunds will be processed within <strong>10 days</strong></li>
                <li>Refund will be credited to the original payment method</li>
                <li>You will receive confirmation once the refund is initiated</li>
                <li>Bank processing time may vary depending on your financial institution</li>
            </ul>

            <h3>Refund Methods:</h3>
            <ul>
                <li>Credit/Debit Card: 5-7 business days after processing</li>
                <li>Net Banking: 5-7 business days after processing</li>
                <li>Digital Wallets: 3-5 business days after processing</li>
                <li>Cash on Delivery: Bank transfer to provided account details</li>
            </ul>

            <div class="highlight-box">
                <p><strong>Processing Time:</strong> In case of any refunds approved by PURE NUTRITION CO, it will take 10 days for the refund to be processed to you.</p>
            </div>
        </div>

        <!-- Important Guidelines Section -->
        <div class="section-card">
            <h2><i class="fas fa-info-circle"></i> 6. Important Guidelines</h2>
            <p>Please keep these important points in mind when requesting cancellations or refunds:</p>

            <h3>Time Limits:</h3>
            <ul>
                <li>All cancellation requests must be made within 7 days of order placement</li>
                <li>Damaged/defective item reports must be made within 7 days of receipt</li>
                <li>Product expectation issues must be reported within 7 days of receipt</li>
            </ul>

            <h3>Documentation Required:</h3>
            <ul>
                <li>Order confirmation details</li>
                <li>Product photos (for damage/defect claims)</li>
                <li>Clear description of the issue</li>
                <li>Contact information for follow-up</li>
            </ul>

            <h3>Customer Responsibilities:</h3>
            <ul>
                <li>Report issues promptly within specified timeframes</li>
                <li>Provide accurate and complete information</li>
                <li>Cooperate with the investigation process</li>
                <li>Keep products in original condition until resolution</li>
            </ul>
        </div>

        <!-- Contact Section -->
        <div class="section-card" id="contact">
            <h2><i class="fas fa-phone"></i> 7. Customer Service</h2>
            <p>For any cancellation or refund requests, please contact our dedicated customer service team:</p>
        </div>

        <!-- Enhanced Contact Card -->
        <div class="contact-card">
            <h3><i class="fas fa-headset"></i> Customer Service Information</h3>
            <div class="contact-grid">
                <div class="contact-item">
                    <i class="fas fa-building"></i>
                    <strong>Company</strong>
                    PURE NUTRITION CO<br>(Alpha Nutrition)
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <strong>Email Support</strong>
                    <a href="mailto:support@purenutritionco.com">support@purenutritionco.com</a>
                </div>
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <strong>Phone Support</strong>
                    <a href="tel:+919022975030">+91 9022975030</a>
                </div>
                <div class="contact-item">
                    <i class="fab fa-whatsapp"></i>
                    <strong>WhatsApp</strong>
                    <a href="https://wa.me/919022975030">+91 9022975030</a>
                </div>
                <div class="contact-item">
                    <i class="fas fa-clock"></i>
                    <strong>Support Hours</strong>
                    Monday – Friday<br>9:00 AM – 6:00 PM
                </div>
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <strong>Location</strong>
                    Sangli, India
                </div>
            </div>
        </div>

        <!-- Final Note -->
        <div class="highlight-box">
            <p><strong>Last Updated:</strong> August 24, 2024</p>
            <p>This Refund & Cancellation Policy is effective as of the date mentioned above. We reserve the right to update this policy as needed. Any changes will be communicated through our website.</p>
        </div>
    </div>
</div>

<!-- Back to Top Button -->
<a href="#" class="back-to-top" id="backToTop">
    <i class="fas fa-arrow-up"></i>
</a>

<script>
// Smooth scrolling for navigation links
document.querySelectorAll('.refund-nav a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Back to top functionality
const backToTop = document.getElementById('backToTop');

window.addEventListener('scroll', () => {
    if (window.pageYOffset > 300) {
        backToTop.style.display = 'flex';
    } else {
        backToTop.style.display = 'none';
    }
});

backToTop.addEventListener('click', (e) => {
    e.preventDefault();
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// Add animation on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe all section cards
document.querySelectorAll('.section-card').forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(30px)';
    card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(card);
});
</script>

<?php include 'includes/footer.php'; ?>
