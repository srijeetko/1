<?php
$pageTitle = "Terms and Conditions - Alpha Nutrition";
include 'includes/header.php';
?>

<style>
/* Premium Terms & Conditions Design - Same as Privacy Policy */
.terms-hero {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 80px 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.terms-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.terms-hero-content {
    position: relative;
    z-index: 2;
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.terms-hero h1 {
    font-size: 3.5em;
    font-weight: 700;
    margin-bottom: 20px;
    font-family: 'Playfair Display', serif;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.terms-hero .subtitle {
    font-size: 1.3em;
    margin-bottom: 15px;
    opacity: 0.9;
}

.effective-date {
    background: rgba(255,255,255,0.2);
    padding: 10px 20px;
    border-radius: 25px;
    display: inline-block;
    font-weight: 500;
    backdrop-filter: blur(10px);
}

.terms-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.terms-content {
    background: white;
    margin: -50px auto 50px;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
    padding: 60px;
    position: relative;
    z-index: 3;
    line-height: 1.8;
    color: #444;
}

.terms-nav {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 40px;
    border-left: 5px solid #28a745;
}

.terms-nav h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.3em;
}

.terms-nav ul {
    list-style: none;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
}

.terms-nav li {
    margin: 0;
}

.terms-nav a {
    color: #28a745;
    text-decoration: none;
    padding: 8px 15px;
    border-radius: 8px;
    display: block;
    transition: all 0.3s ease;
    font-weight: 500;
}

.terms-nav a:hover {
    background: #28a745;
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
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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

.contact-card {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
}

.contact-item i {
    font-size: 1.5em;
    margin-bottom: 10px;
    display: block;
}

.contact-item strong {
    display: block;
    margin-bottom: 5px;
    font-size: 0.9em;
    opacity: 0.8;
}

.contact-item a {
    color: white;
    text-decoration: none;
    font-weight: 500;
}

.contact-item a:hover {
    text-decoration: underline;
}

.highlight-box {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    padding: 30px;
    border-radius: 15px;
    margin: 30px 0;
    border-left: 5px solid #28a745;
    position: relative;
    overflow: hidden;
}

.highlight-box::before {
    content: '📋';
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 2em;
    opacity: 0.3;
}

.highlight-box p {
    margin: 0;
    font-weight: 500;
    color: #155724;
}

.back-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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

@media (max-width: 768px) {
    .terms-hero {
        padding: 60px 0;
    }

    .terms-hero h1 {
        font-size: 2.5em;
    }

    .terms-content {
        margin: -30px 20px 30px;
        padding: 40px 30px;
        border-radius: 15px;
    }

    .section-card {
        padding: 30px 25px;
    }

    .contact-grid {
        grid-template-columns: 1fr;
    }

    .terms-nav ul {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Hero Section -->
<div class="terms-hero">
    <div class="terms-hero-content">
        <h1>Terms & Conditions</h1>
        <p class="subtitle">Your Agreement with Alpha Nutrition</p>
        <div class="effective-date">Effective Date: August 24, 2024</div>
    </div>
</div>

<div class="terms-container">
    <div class="terms-content">
        <!-- Introduction -->
        <div class="highlight-box">
            <p><strong>Welcome to Alpha Nutrition (Pure Nutrition Co.). These Terms and Conditions govern your use of our website and services. By accessing or using our services, you agree to be bound by these terms.</strong></p>
        </div>

        <!-- Quick Navigation -->
        <div class="terms-nav">
            <h3><i class="fas fa-list"></i> Quick Navigation</h3>
            <ul>
                <li><a href="#acceptance"><i class="fas fa-check-circle"></i> Acceptance of Terms</a></li>
                <li><a href="#license"><i class="fas fa-key"></i> Use License</a></li>
                <li><a href="#orders"><i class="fas fa-shopping-cart"></i> Orders & Payment</a></li>
                <li><a href="#shipping"><i class="fas fa-truck"></i> Shipping & Delivery</a></li>
                <li><a href="#prohibited"><i class="fas fa-ban"></i> Prohibited Uses</a></li>
                <li><a href="#contact"><i class="fas fa-phone"></i> Contact Us</a></li>
            </ul>
        </div>

        <!-- Acceptance Section -->
        <div class="section-card" id="acceptance">
            <h2><i class="fas fa-check-circle"></i> 1. Acceptance of Terms</h2>
            <p>By accessing and using Alpha Nutrition's website and services, you accept and agree to be bound by the terms and provision of this agreement.</p>
        </div>

        <!-- License Section -->
        <div class="section-card" id="license">
            <h2><i class="fas fa-key"></i> 2. Use License</h2>
            <p>Permission is granted to temporarily download one copy of the materials on Alpha Nutrition's website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
            <ul>
                <li>Modify or copy the materials</li>
                <li>Use the materials for any commercial purpose or for any public display</li>
                <li>Attempt to reverse engineer any software contained on the website</li>
                <li>Remove any copyright or other proprietary notations from the materials</li>
            </ul>
        </div>

        <h2>3. Product Information</h2>
        <p>We strive to provide accurate product information, including descriptions, prices, and availability. However, we do not warrant that product descriptions or other content is accurate, complete, reliable, current, or error-free.</p>

        <h2>4. Orders and Payment</h2>
        <ul>
            <li>All orders are subject to acceptance and availability</li>
            <li>We reserve the right to refuse or cancel any order</li>
            <li>Payment must be made at the time of order</li>
            <li>Prices are subject to change without notice</li>
        </ul>

        <h2>5. Shipping and Delivery</h2>
        <p>We will make every effort to deliver products within the estimated timeframe. However, delivery times are estimates and not guaranteed. We are not responsible for delays caused by shipping carriers or circumstances beyond our control.</p>

        <h2>6. Returns and Refunds</h2>
        <p>Please refer to our separate Refund and Cancellation Policy for detailed information about returns, exchanges, and refunds.</p>

        <h2>7. User Accounts</h2>
        <p>When you create an account with us, you must provide information that is accurate, complete, and current at all times. You are responsible for safeguarding the password and for all activities that occur under your account.</p>

        <h2>8. Prohibited Uses</h2>
        <p>You may not use our service:</p>
        <ul>
            <li>For any unlawful purpose or to solicit others to perform unlawful acts</li>
            <li>To violate any international, federal, provincial, or state regulations, rules, laws, or local ordinances</li>
            <li>To infringe upon or violate our intellectual property rights or the intellectual property rights of others</li>
            <li>To harass, abuse, insult, harm, defame, slander, disparage, intimidate, or discriminate</li>
            <li>To submit false or misleading information</li>
        </ul>

        <h2>9. Disclaimer</h2>
        <p>The information on this website is provided on an 'as is' basis. To the fullest extent permitted by law, Alpha Nutrition excludes all representations, warranties, conditions and terms.</p>

        <h2>10. Limitations</h2>
        <p>In no event shall Alpha Nutrition or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on Alpha Nutrition's website.</p>

        <h2>11. Privacy Policy</h2>
        <p>Your privacy is important to us. Please review our Privacy Policy, which also governs your use of the website, to understand our practices.</p>

        <h2>12. Governing Law</h2>
        <p>These terms and conditions are governed by and construed in accordance with the laws of India and you irrevocably submit to the exclusive jurisdiction of the courts in that State or location.</p>

        <h2>13. Changes to Terms</h2>
        <p>We reserve the right to revise these terms of service at any time without notice. By using this website, you are agreeing to be bound by the then current version of these terms of service.</p>

        <h2>14. Contact Information</h2>
        <div class="contact-info">
            <h3>For questions about these Terms and Conditions, please contact:</h3>
            <p><strong>Pure Nutrition Co.</strong></p>
            <p><strong>Address:</strong> Sangli, India</p>
            <p><strong>Email:</strong> <a href="mailto:support@purenutritionco.com">support@purenutritionco.com</a></p>
            <p><strong>Phone:</strong> <a href="tel:+919022975030">+91 9022975030</a></p>
            <p><strong>Office Hours:</strong> Monday – Friday, 9:00 AM – 6:00 PM</p>
        </div>

        <div class="highlight-box">
            <p><strong>Last Updated:</strong> August 24, 2024</p>
            <p>These Terms and Conditions are effective as of the date mentioned above and will remain in effect except with respect to any changes in its provisions in the future.</p>
        </div>
    </div>
</div>

<!-- Back to Top Button -->
<a href="#" class="back-to-top" id="backToTop">
    <i class="fas fa-arrow-up"></i>
</a>

<script>
// Smooth scrolling for navigation links
document.querySelectorAll('.terms-nav a[href^="#"]').forEach(anchor => {
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
