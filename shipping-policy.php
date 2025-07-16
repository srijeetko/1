<?php
$pageTitle = "Shipping Policy - Alpha Nutrition";
include 'includes/header.php';
?>

<style>
/* Premium Shipping Policy Design */

/* Hero Section Styles */
.shipping-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 80px 0;
    text-align: center;
    position: relative;
    overflow: hidden;
    margin-bottom: 0;
}

.shipping-hero::before {
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

.shipping-hero-content {
    position: relative;
    z-index: 2;
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.shipping-hero h1 {
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

.shipping-hero .subtitle {
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

.shipping-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.shipping-content {
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

.shipping-nav {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 40px;
    border-left: 5px solid #007cba;
}

.shipping-nav h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.3em;
}

.shipping-nav ul {
    list-style: none;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
}

.shipping-nav li {
    margin: 0;
}

.shipping-nav a {
    color: #007cba;
    text-decoration: none;
    padding: 8px 15px;
    border-radius: 8px;
    display: block;
    transition: all 0.3s ease;
    font-weight: 500;
}

.shipping-nav a:hover {
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

.timeline-box {
    background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
    padding: 30px;
    border-radius: 15px;
    margin: 30px 0;
    border-left: 5px solid #28a745;
    position: relative;
    overflow: hidden;
}

.timeline-box::before {
    content: '🚚';
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 2em;
    opacity: 0.3;
}

.timeline-box p {
    margin: 0;
    font-weight: 500;
    color: #155724;
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
    content: '📦';
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
    .shipping-hero {
        padding: 60px 0;
    }

    .shipping-hero h1 {
        font-size: 2.5rem;
    }

    .shipping-hero .subtitle {
        font-size: 1.2rem;
        margin-bottom: 1.5rem;
    }

    .effective-date {
        padding: 10px 20px;
        font-size: 0.9rem;
    }

    .shipping-hero-content {
        padding: 0 15px;
    }

    .contact-grid {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 15px;
    }
}

@media (max-width: 480px) {
    .shipping-hero h1 {
        font-size: 2rem;
        letter-spacing: 0;
    }

    .shipping-hero .subtitle {
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
<div class="shipping-hero">
    <div class="shipping-hero-content">
        <h1>Shipping Policy</h1>
        <p class="subtitle">Fast & Reliable Delivery Services</p>
        <div class="effective-date">
            <i class="fas fa-calendar-alt"></i>
            Effective Date: August 24, 2024
        </div>
    </div>
</div>

<div class="shipping-container">
    <div class="shipping-content">
        <!-- Introduction -->
        <div class="highlight-box">
            <p><strong>This shipping policy outlines our delivery procedures, timelines, and terms for all orders placed through our platform. We are committed to providing reliable and efficient shipping services to ensure your products reach you safely and on time.</strong></p>
        </div>

        <!-- Quick Navigation -->
        <div class="shipping-nav">
            <h3><i class="fas fa-list"></i> Quick Navigation</h3>
            <ul>
                <li><a href="#shipping-methods"><i class="fas fa-truck"></i> Shipping Methods</a></li>
                <li><a href="#delivery-timeline"><i class="fas fa-clock"></i> Delivery Timeline</a></li>
                <li><a href="#delivery-address"><i class="fas fa-map-marker-alt"></i> Delivery Address</a></li>
                <li><a href="#shipping-costs"><i class="fas fa-dollar-sign"></i> Shipping Costs</a></li>
                <li><a href="#limitations"><i class="fas fa-exclamation-triangle"></i> Limitations</a></li>
                <li><a href="#contact"><i class="fas fa-phone"></i> Contact Support</a></li>
            </ul>
        </div>

        <!-- Shipping Methods Section -->
        <div class="section-card" id="shipping-methods">
            <h2><i class="fas fa-truck"></i> 1. Shipping Methods</h2>
            <p>We ensure secure and reliable delivery of your orders through trusted shipping partners:</p>

            <h3>Authorized Shipping Partners:</h3>
            <ul>
                <li>Registered domestic courier companies</li>
                <li>India Post Speed Post services</li>
                <li>Other verified logistics partners</li>
            </ul>

            <h3>Shipping Coverage:</h3>
            <ul>
                <li>Pan-India delivery coverage</li>
                <li>Both urban and rural area delivery</li>
                <li>Secure packaging for product safety</li>
                <li>Real-time tracking facilities</li>
            </ul>

            <div class="timeline-box">
                <p><strong>Quality Assurance:</strong> All orders are shipped through registered domestic courier companies and/or speed post only to ensure maximum security and reliability.</p>
            </div>
        </div>

        <!-- Delivery Timeline Section -->
        <div class="section-card" id="delivery-timeline">
            <h2><i class="fas fa-clock"></i> 2. Delivery Timeline</h2>
            <p>We strive to process and ship your orders as quickly as possible while maintaining quality standards:</p>

            <h3>Standard Processing Time:</h3>
            <ul>
                <li>Orders are shipped within <strong>10 days</strong> from the date of order</li>
                <li>Processing begins after payment confirmation</li>
                <li>Delivery timeline as agreed at the time of order confirmation</li>
                <li>Subject to courier company and post office norms</li>
            </ul>

            <h3>Factors Affecting Delivery:</h3>
            <ul>
                <li>Product availability and stock status</li>
                <li>Payment verification and processing</li>
                <li>Delivery location and accessibility</li>
                <li>Weather conditions and external factors</li>
                <li>Courier company operational schedules</li>
            </ul>

            <div class="timeline-box">
                <p><strong>Shipping Timeline:</strong> Orders are shipped within 10 days from the date of the order and/or payment or as per the delivery date agreed at the time of order confirmation.</p>
            </div>
        </div>

        <!-- Delivery Address Section -->
        <div class="section-card" id="delivery-address">
            <h2><i class="fas fa-map-marker-alt"></i> 3. Delivery Address</h2>
            <p>Accurate delivery information is crucial for successful order fulfillment:</p>

            <h3>Address Requirements:</h3>
            <ul>
                <li>Complete and accurate delivery address must be provided at purchase</li>
                <li>Include landmark details for easy location</li>
                <li>Provide correct PIN code and area details</li>
                <li>Ensure recipient contact number is active</li>
            </ul>

            <h3>Delivery Confirmation:</h3>
            <ul>
                <li>All deliveries will be made to the address provided by the buyer</li>
                <li>Delivery confirmation will be sent to your registered email ID</li>
                <li>SMS notifications for delivery updates</li>
                <li>Tracking information provided for order monitoring</li>
            </ul>

            <div class="warning-box">
                <p><strong>Important:</strong> Delivery of all orders will be made to the address provided by the buyer at the time of purchase. Please ensure address accuracy to avoid delivery delays.</p>
            </div>
        </div>

        <!-- Shipping Costs Section -->
        <div class="section-card" id="shipping-costs">
            <h2><i class="fas fa-dollar-sign"></i> 4. Shipping Costs</h2>
            <p>Transparent pricing policy for all shipping-related charges:</p>

            <h3>Shipping Charges:</h3>
            <ul>
                <li>Shipping costs may be levied by the seller or Platform Owner</li>
                <li>All shipping charges are clearly displayed at checkout</li>
                <li>Charges vary based on delivery location and product weight</li>
                <li>Special rates may apply for bulk orders</li>
            </ul>

            <h3>Refund Policy for Shipping:</h3>
            <ul>
                <li>Shipping costs are <strong>non-refundable</strong> once charged</li>
                <li>Applies to all successful deliveries</li>
                <li>No refund for shipping charges in case of order cancellation after dispatch</li>
            </ul>

            <div class="warning-box">
                <p><strong>Non-Refundable:</strong> If there are any shipping cost(s) levied by the seller or the Platform Owner (as the case be), the same is not refundable.</p>
            </div>
        </div>

        <!-- Limitations and Liability Section -->
        <div class="section-card" id="limitations">
            <h2><i class="fas fa-exclamation-triangle"></i> 5. Limitations and Liability</h2>
            <p>Important terms regarding our shipping responsibilities and limitations:</p>

            <h3>Platform Owner Liability:</h3>
            <ul>
                <li>Platform Owner shall <strong>not be liable</strong> for any delay in delivery by courier company</li>
                <li>No responsibility for delays caused by postal authority</li>
                <li>External factors beyond our control are not our responsibility</li>
                <li>Force majeure events affecting delivery timelines</li>
            </ul>

            <h3>Courier Company Dependencies:</h3>
            <ul>
                <li>Delivery timelines subject to courier company norms</li>
                <li>Post office operational schedules and limitations</li>
                <li>Regional restrictions and accessibility issues</li>
                <li>Weather-related delays and disruptions</li>
            </ul>

            <h3>Customer Responsibilities:</h3>
            <ul>
                <li>Provide accurate and complete delivery information</li>
                <li>Be available at delivery address during business hours</li>
                <li>Check package condition upon delivery</li>
                <li>Report any delivery issues immediately</li>
            </ul>

            <div class="warning-box">
                <p><strong>Disclaimer:</strong> Platform Owner shall not be liable for any delay in delivery by the courier company / postal authority. Delivery timelines are subject to courier company / post office norms.</p>
            </div>
        </div>

        <!-- Order Tracking Section -->
        <div class="section-card">
            <h2><i class="fas fa-search"></i> 6. Order Tracking</h2>
            <p>Stay updated with your order status through our tracking system:</p>

            <h3>Tracking Features:</h3>
            <ul>
                <li>Real-time order status updates</li>
                <li>SMS and email notifications</li>
                <li>Tracking number provided after dispatch</li>
                <li>Estimated delivery date information</li>
            </ul>

            <h3>Tracking Process:</h3>
            <ul>
                <li>Order confirmation and processing status</li>
                <li>Dispatch notification with tracking details</li>
                <li>In-transit updates and location tracking</li>
                <li>Delivery confirmation and receipt</li>
            </ul>
        </div>

        <!-- Special Circumstances Section -->
        <div class="section-card">
            <h2><i class="fas fa-info-circle"></i> 7. Special Circumstances</h2>
            <p>Additional information for specific delivery scenarios:</p>

            <h3>Remote Area Delivery:</h3>
            <ul>
                <li>Additional time may be required for remote locations</li>
                <li>Extra charges may apply for difficult-to-reach areas</li>
                <li>Alternative delivery arrangements may be necessary</li>
            </ul>

            <h3>Failed Delivery Attempts:</h3>
            <ul>
                <li>Multiple delivery attempts will be made</li>
                <li>Customer will be notified of failed attempts</li>
                <li>Package may be held at local courier office</li>
                <li>Return to sender after specified holding period</li>
            </ul>

            <h3>Delivery Confirmation:</h3>
            <ul>
                <li>Email confirmation sent to registered email ID</li>
                <li>SMS notification for successful delivery</li>
                <li>Delivery receipt and tracking completion</li>
            </ul>
        </div>

        <!-- Contact Section -->
        <div class="section-card" id="contact">
            <h2><i class="fas fa-phone"></i> 8. Shipping Support</h2>
            <p>For any shipping-related queries or concerns, please contact our support team:</p>
        </div>

        <!-- Enhanced Contact Card -->
        <div class="contact-card">
            <h3><i class="fas fa-shipping-fast"></i> Shipping Support Information</h3>
            <div class="contact-grid">
                <div class="contact-item">
                    <i class="fas fa-building"></i>
                    <strong>Company</strong>
                    Alpha Nutrition<br>(Pure Nutrition Co.)
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <strong>Email Support</strong>
                    <a href="mailto:support@alphanutrition.co.in">support@alphanutrition.co.in</a>
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
            <p>This Shipping Policy is effective as of the date mentioned above. We reserve the right to update this policy as needed to improve our services. Any changes will be communicated through our website.</p>
        </div>
    </div>
</div>

<!-- Back to Top Button -->
<a href="#" class="back-to-top" id="backToTop">
    <i class="fas fa-arrow-up"></i>
</a>

<script>
// Smooth scrolling for navigation links
document.querySelectorAll('.shipping-nav a[href^="#"]').forEach(anchor => {
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
