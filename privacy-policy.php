<?php
$pageTitle = "Privacy Policy - Alpha Nutrition";
include 'includes/header.php';
?>

<style>
/* Premium Privacy Policy Design */

/* Hero Section Styles */
.privacy-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 80px 0;
    text-align: center;
    position: relative;
    overflow: hidden;
    margin-bottom: 0;
}

.privacy-hero::before {
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

.privacy-hero-content {
    position: relative;
    z-index: 2;
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.privacy-hero h1 {
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

.privacy-hero .subtitle {
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

.privacy-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.privacy-content {
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

.privacy-nav {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 40px;
    border-left: 5px solid #007cba;
}

.privacy-nav h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.3em;
}

.privacy-nav ul {
    list-style: none;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
}

.privacy-nav li {
    margin: 0;
}

.privacy-nav a {
    color: #007cba;
    text-decoration: none;
    padding: 8px 15px;
    border-radius: 8px;
    display: block;
    transition: all 0.3s ease;
    font-weight: 500;
}

.privacy-nav a:hover {
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

@media (max-width: 768px) {
    .contact-grid {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 15px;
    }
}

@media (max-width: 480px) {
    .contact-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
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

/* Specific styling for email contact item */
.contact-item:has(i.fa-envelope) {
    min-height: 140px;
}

.contact-item:has(i.fa-envelope) a {
    font-size: 0.9rem;
    word-break: break-all;
    hyphens: auto;
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
    content: '🔒';
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
    .privacy-hero {
        padding: 60px 0;
    }

    .privacy-hero h1 {
        font-size: 2.5rem;
    }

    .privacy-hero .subtitle {
        font-size: 1.2rem;
        margin-bottom: 1.5rem;
    }

    .effective-date {
        padding: 10px 20px;
        font-size: 0.9rem;
    }

    .privacy-hero-content {
        padding: 0 15px;
    }
}

@media (max-width: 480px) {
    .privacy-hero h1 {
        font-size: 2rem;
        letter-spacing: 0;
    }

    .privacy-hero .subtitle {
        font-size: 1.1rem;
    }

    .effective-date {
        padding: 8px 16px;
        font-size: 0.85rem;
    }
}
</style>

<!-- Hero Section -->
<div class="privacy-hero">
    <div class="privacy-hero-content">
        <h1>Privacy Policy</h1>
        <p class="subtitle">Your Privacy is Our Priority</p>
        <div class="effective-date">
            <i class="fas fa-calendar-alt"></i>
            Effective Date: August 24, 2024
        </div>
    </div>
</div>

<div class="privacy-container">
    <div class="privacy-content">
        <!-- Introduction -->
        <div class="highlight-box">
            <p><strong>This Privacy Policy outlines how Alpha Nutrition (Pure Nutrition Co.) collects, uses, shares, protects, and processes your personal information through our website and services. By using Alpha Nutrition, you agree to the terms outlined in this Privacy Policy. If you do not agree, please do not use our services.</strong></p>
        </div>

        <!-- Quick Navigation -->
        <div class="privacy-nav">
            <h3><i class="fas fa-list"></i> Quick Navigation</h3>
            <ul>
                <li><a href="#information-collection"><i class="fas fa-database"></i> Information Collection</a></li>
                <li><a href="#use-of-information"><i class="fas fa-cogs"></i> Use of Information</a></li>
                <li><a href="#sharing-information"><i class="fas fa-share-alt"></i> Sharing Information</a></li>
                <li><a href="#security"><i class="fas fa-shield-alt"></i> Security</a></li>
                <li><a href="#user-rights"><i class="fas fa-user-check"></i> User Rights</a></li>
                <li><a href="#contact"><i class="fas fa-phone"></i> Contact Us</a></li>
            </ul>
        </div>

        <!-- Information Collection Section -->
        <div class="section-card" id="information-collection">
            <h2><i class="fas fa-database"></i> 1. Information Collection</h2>
            <p>We collect personal data that you provide when using our website and services, including:</p>

            <h3>Personal Information:</h3>
            <ul>
                <li>Name, date of birth, address, phone number, email ID</li>
                <li>Identity verification details</li>
                <li>Order and delivery information</li>
            </ul>

            <h3>Sensitive Information:</h3>
            <ul>
                <li>Bank account details and credit/debit card information</li>
                <li>Biometric data (e.g., facial features) as permitted by applicable laws</li>
                <li>Other sensitive personal data as required for service provision</li>
            </ul>

            <p>We also collect behavioral data and transaction details to enhance your experience and provide the requested services.</p>
        </div>

        <!-- Use of Information Section -->
        <div class="section-card" id="use-of-information">
            <h2><i class="fas fa-cogs"></i> 2. Use of Information</h2>
            <p>We use your information to:</p>
            <ul>
                <li>Provide and improve our services</li>
                <li>Process transactions and manage your orders</li>
                <li>Customize your experience and communicate offers and updates</li>
                <li>Conduct marketing and surveys, with your consent</li>
                <li>Ensure security and prevent fraud</li>
                <li>Comply with legal requirements</li>
            </ul>
            <p>You can opt-out of marketing communications by following the instructions provided in those communications or by contacting our support team.</p>
        </div>

        <!-- Sharing Information Section -->
        <div class="section-card" id="sharing-information">
            <h2><i class="fas fa-share-alt"></i> 3. Sharing of Information</h2>
            <p>We may share your information with:</p>

            <h3>Internal Entities:</h3>
            <ul>
                <li>Affiliates and subsidiaries for operational purposes</li>
            </ul>

            <h3>Third Parties:</h3>
            <ul>
                <li>Service providers and business partners</li>
                <li>Payment processors to fulfill orders and manage services</li>
                <li>Delivery and logistics partners</li>
            </ul>

            <h3>Legal and Compliance:</h3>
            <ul>
                <li>Government authorities or legal entities if required by law</li>
                <li>To protect our rights and prevent fraud</li>
            </ul>

            <p>We do not control third-party privacy practices and recommend reviewing their policies.</p>
        </div>

        <!-- Security Section -->
        <div class="section-card" id="security">
            <h2><i class="fas fa-shield-alt"></i> 4. Security</h2>
            <p>We implement reasonable security measures to protect your data from unauthorized access and misuse. However, no transmission over the internet is completely secure. By using our services, you acknowledge the inherent risks of data transmission online.</p>
        </div>

        <!-- Data Retention Section -->
        <div class="section-card">
            <h2><i class="fas fa-clock"></i> 5. Data Retention and Deletion</h2>
            <p>You can delete your account via your account settings or by contacting our support team. We retain data only as long as necessary for service provision or as required by law. We may keep anonymized data for analytical purposes.</p>
        </div>

        <!-- User Rights Section -->
        <div class="section-card" id="user-rights">
            <h2><i class="fas fa-user-check"></i> 6. User Rights</h2>
            <p>You have the right to:</p>
            <ul>
                <li>Access your personal information</li>
                <li>Rectify and update your personal information</li>
                <li>Request deletion of your data</li>
                <li>Withdraw consent for data processing</li>
            </ul>
            <p>To exercise these rights or for other privacy concerns, contact our Grievance Officer.</p>
        </div>

        <!-- Consent Section -->
        <div class="section-card">
            <h2><i class="fas fa-handshake"></i> 7. Consent</h2>
            <p>By using our services, you consent to the collection, use, and processing of your information as described in this Privacy Policy. If you provide information about others, you must have their consent.</p>
        </div>

        <!-- Changes Section -->
        <div class="section-card">
            <h2><i class="fas fa-edit"></i> 8. Changes to Privacy Policy</h2>
            <p>We may update this Privacy Policy from time to time. Significant changes will be communicated as required by law. We encourage you to review this policy periodically.</p>
        </div>

        <!-- Contact Section -->
        <div class="section-card" id="contact">
            <h2><i class="fas fa-phone"></i> 9. Grievance Officer</h2>
            <p>For any concerns or inquiries regarding your personal data, please contact our Grievance Officer:</p>
        </div>

        <!-- Enhanced Contact Card -->
        <div class="contact-card">
            <h3><i class="fas fa-user-tie"></i> Contact Information</h3>
            <div class="contact-grid">
                <div class="contact-item">
                    <i class="fas fa-user"></i>
                    <strong>Name</strong>
                    Muzammil Shaikh
                </div>
                <div class="contact-item">
                    <i class="fas fa-briefcase"></i>
                    <strong>Designation</strong>
                    Inventory Keeper
                </div>
                <div class="contact-item">
                    <i class="fas fa-building"></i>
                    <strong>Company</strong>
                    Pure Nutrition Co.
                </div>
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <strong>Address</strong>
                    Sangli, India
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <strong>Email</strong>
                    <a href="mailto:support@purenutritionco.com">support@purenutritionco.com</a>
                </div>
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <strong>Phone</strong>
                    <a href="tel:+919022975030">+91 9022975030</a>
                </div>
                <div class="contact-item">
                    <i class="fas fa-clock"></i>
                    <strong>Office Hours</strong>
                    Monday – Friday<br>9:00 AM – 6:00 PM
                </div>
                <div class="contact-item">
                    <i class="fab fa-whatsapp"></i>
                    <strong>WhatsApp</strong>
                    <a href="https://wa.me/919022975030">+91 9022975030</a>
                </div>
            </div>
        </div>

        <!-- Final Note -->
        <div class="highlight-box">
            <p><strong>Last Updated:</strong> August 24, 2024</p>
            <p>This Privacy Policy is effective as of the date mentioned above and will remain in effect except with respect to any changes in its provisions in the future, which will be in effect immediately after being posted on this page.</p>
        </div>
    </div>
</div>

<!-- Back to Top Button -->
<a href="#" class="back-to-top" id="backToTop">
    <i class="fas fa-arrow-up"></i>
</a>

<script>
// Smooth scrolling for navigation links
document.querySelectorAll('.privacy-nav a[href^="#"]').forEach(anchor => {
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
