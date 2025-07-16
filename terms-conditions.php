<?php
$pageTitle = "Terms and Conditions - Alpha Nutrition";
include 'includes/header.php';
?>

<style>
/* Premium Terms and Conditions Design */

/* Hero Section Styles */
.terms-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 80px 0;
    text-align: center;
    position: relative;
    overflow: hidden;
    margin-bottom: 0;
}

.terms-hero::before {
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

.terms-hero-content {
    position: relative;
    z-index: 2;
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.terms-hero h1 {
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

.terms-hero .subtitle {
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

.terms-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.terms-content {
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

.terms-nav {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 40px;
    border-left: 5px solid #007cba;
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
    color: #007cba;
    text-decoration: none;
    padding: 8px 15px;
    border-radius: 8px;
    display: block;
    transition: all 0.3s ease;
    font-weight: 500;
}

.terms-nav a:hover {
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
    .terms-hero {
        padding: 60px 0;
    }

    .terms-hero h1 {
        font-size: 2.5rem;
    }

    .terms-hero .subtitle {
        font-size: 1.2rem;
        margin-bottom: 1.5rem;
    }

    .effective-date {
        padding: 10px 20px;
        font-size: 0.9rem;
    }

    .terms-hero-content {
        padding: 0 15px;
    }

    .contact-grid {
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 15px;
    }
}

@media (max-width: 480px) {
    .terms-hero h1 {
        font-size: 2rem;
        letter-spacing: 0;
    }

    .terms-hero .subtitle {
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
<div class="terms-hero">
    <div class="terms-hero-content">
        <h1>Terms & Conditions</h1>
        <p class="subtitle">Your Agreement with Alpha Nutrition</p>
        <div class="effective-date">
            <i class="fas fa-calendar-alt"></i>
            Effective Date: August 24, 2024
        </div>
    </div>
</div>

<div class="terms-container">
    <div class="terms-content">
        <!-- Introduction -->
        <div class="highlight-box">
            <p><strong>These Terms and Conditions govern your use of the Alpha Nutrition website and services. By accessing or using our website, you agree to be bound by these terms. If you do not agree with any part of these terms, please do not use our services.</strong></p>
        </div>

        <!-- Quick Navigation -->
        <div class="terms-nav">
            <h3><i class="fas fa-list"></i> Quick Navigation</h3>
            <ul>
                <li><a href="#registration"><i class="fas fa-user-plus"></i> Registration & Account</a></li>
                <li><a href="#orders"><i class="fas fa-shopping-cart"></i> Orders & Purchases</a></li>
                <li><a href="#pricing"><i class="fas fa-dollar-sign"></i> Pricing & Payment</a></li>
                <li><a href="#shipping"><i class="fas fa-truck"></i> Shipping & Delivery</a></li>
                <li><a href="#returns"><i class="fas fa-undo"></i> Returns & Refunds</a></li>
                <li><a href="#liability"><i class="fas fa-shield-alt"></i> Liability & Disclaimers</a></li>
                <li><a href="#intellectual"><i class="fas fa-copyright"></i> Intellectual Property</a></li>
                <li><a href="#privacy-policy"><i class="fas fa-user-shield"></i> Privacy Policy</a></li>
                <li><a href="#contact"><i class="fas fa-phone"></i> Contact Information</a></li>
            </ul>
        </div>

        <!-- Registration and Account Section -->
        <div class="section-card" id="registration">
            <h2><i class="fas fa-user-plus"></i> 1. Registration and Account Information</h2>
            <p>As part of the registration process on the Website, Alpha Nutrition may collect the following personally identifiable information about you:</p>
            <ul>
                <li>First and last name, email address, mobile phone number</li>
                <li>Demographic details such as age, gender, occupation, education, address</li>
                <li>Browsing information including links clicked and page access frequency</li>
            </ul>
            <p>You must keep your account and registration details up-to-date with current and correct information for receiving communications related to your purchases. By agreeing to these Terms of Use, you can opt for receiving promotional offers and newsletters.</p>
            <p>Alpha Nutrition stores only the necessary information required to make a purchase and does not disclose your confidential information to any third party without your consent. Payment information (credit/debit cards or CVV numbers) is not saved and is processed through secure servers.</p>
        </div>

        <!-- Orders and Purchases Section -->
        <div class="section-card" id="orders">
            <h2><i class="fas fa-shopping-cart"></i> 2. Orders and Purchases</h2>
            <p>You agree and confirm that:</p>
            <ul>
                <li>All information, products, and services displayed on the Website do not constitute an offer to sell</li>
                <li>Your order constitutes an offer to purchase products under these Terms at the specified price</li>
                <li>Alpha Nutrition reserves the right to accept or reject your offer for any reason, including product unavailability or pricing errors</li>
                <li>You must check and verify product descriptions carefully before placing an order</li>
                <li>By placing an order, you are bound by the conditions of sale included in the product's description</li>
            </ul>
            <p>In the event Alpha Nutrition cancels your order, we will provide a full refund of any payment received. If we are unable to deliver a product, you will be notified by email and your order will be automatically cancelled.</p>
        </div>

        <!-- Pricing and Payment Section -->
        <div class="section-card" id="pricing">
            <h2><i class="fas fa-dollar-sign"></i> 3. Pricing and Payment</h2>

            <h3>Pricing:</h3>
            <ul>
                <li>All products are sold at maximum retail price unless otherwise specified</li>
                <li>Prices declared on the Website are inclusive of taxes</li>
                <li>Prices mentioned at ordering will be charged on delivery date</li>
                <li>No additional charges will be collected or refunded for price variations</li>
                <li>Prices and availability are subject to change without prior notice</li>
            </ul>

            <h3>Payment:</h3>
            <ul>
                <li>You may choose from various payment options available on the Website</li>
                <li>You must use your own debit/credit card or banking details for transactions</li>
                <li>Alpha Nutrition is not responsible for misuse of payment details if security checks are verified</li>
                <li>Alpha Nutrition will not be liable for credit card fraud</li>
                <li>The burden of proving fraudulent use of your card lies exclusively with you</li>
            </ul>
        </div>

        <!-- Shipping and Delivery Section -->
        <div class="section-card" id="shipping">
            <h2><i class="fas fa-truck"></i> 4. Shipping and Delivery</h2>
            <p>Alpha Nutrition handles the logistics of product delivery. At the time of placing your order, an estimated delivery time will be shared based on your location. Once shipped, you will receive a consignment number for tracking.</p>
            <p>While we strive to deliver orders as quickly as possible, external circumstances may cause delays. Please refer to our Shipping and Delivery Policy on our Website for detailed information.</p>
        </div>

        <!-- Returns and Refunds Section -->
        <div class="section-card" id="returns">
            <h2><i class="fas fa-undo"></i> 5. Cancellation, Refunds and Returns</h2>
            <p>Orders can be canceled before dispatch. If you cannot cancel your order, it means the order has been dispatched. Please contact our customer service for further assistance.</p>
            <p>Alpha Nutrition will not entertain any complaint after 7 days of the order having been delivered to you.</p>
        </div>

        <!-- User Responsibilities Section -->
        <div class="section-card">
            <h2><i class="fas fa-user-check"></i> 6. User Responsibilities</h2>
            <p>You are responsible for:</p>
            <ul>
                <li>Maintaining confidentiality of your account and password</li>
                <li>Restricting access to your computer and accepting responsibility for all account activities</li>
                <li>Informing us immediately if you suspect account misuse</li>
                <li>Providing true, accurate, and authentic information when requested</li>
                <li>Not violating the security of the Website</li>
                <li>Not interfering with other users' enjoyment of the Website</li>
            </ul>
            <p>Alpha Nutrition reserves the right to require password changes or temporarily/permanently block accounts for security breaches without liability.</p>
        </div>

        <!-- Product Information Section -->
        <div class="section-card">
            <h2><i class="fas fa-info-circle"></i> 7. Product Description and Information</h2>
            <p>Alpha Nutrition attempts to be as accurate as possible in product descriptions. However, we do not warrant that product descriptions, colors, or other content are accurate, complete, reliable, current, or error-free.</p>

            <h3>Important Notes:</h3>
            <ul>
                <li>Product pictures are indicative and may not match the actual product</li>
                <li>Colors may appear differently depending on your monitor</li>
                <li>Product availability depends on seasonal variations of naturally sourced raw materials</li>
                <li>Due to natural sourcing, color, texture, and taste may differ between batches</li>
                <li>Nutritional value remains constant despite seasonal variations</li>
            </ul>

            <p>Information provided is for general purposes and not intended as medical advice. For specific medical conditions, consult a certified medical practitioner.</p>
        </div>

        <!-- Liability and Disclaimers Section -->
        <div class="section-card" id="liability">
            <h2><i class="fas fa-shield-alt"></i> 8. Liability and Disclaimers</h2>
            <p>The Website is provided on an "as is" and "as available" basis. Alpha Nutrition makes no warranties of any kind, express or implied.</p>

            <h3>Disclaimer of Warranties:</h3>
            <ul>
                <li>Products are subject only to applicable warranties from manufacturers, distributors, and suppliers</li>
                <li>Alpha Nutrition disclaims all warranties of merchantability, non-infringement, or fitness for particular purpose</li>
                <li>We disclaim liability for product defects, damages from normal use, misuse, or modification</li>
                <li>No liability for consequential, incidental, indirect, punitive, or special damages</li>
            </ul>

            <h3>Health and Safety:</h3>
            <p>If any product causes side effects, Alpha Nutrition shall not be held liable. You should carefully read product descriptions to ensure ingredients do not cause allergic reactions and may consult a specialist before use.</p>
        </div>

        <!-- License and Usage Section -->
        <div class="section-card">
            <h2><i class="fas fa-key"></i> 9. License and Website Usage</h2>
            <p>Alpha Nutrition grants you a limited, non-exclusive, non-transferable license to access and make personal, non-commercial use of the Website. This license does not permit:</p>
            <ul>
                <li>Downloading or modifying any portion of the Website (except page caching)</li>
                <li>Resale of products for commercial purposes</li>
                <li>Commercial use of the Website or its contents</li>
                <li>Uploading harmful, defamatory, obscene, or unlawful content</li>
            </ul>
            <p>Alpha Nutrition reserves the right to discontinue your account without reason and to refuse service for Terms violations.</p>
        </div>

        <!-- Intellectual Property Section -->
        <div class="section-card" id="intellectual">
            <h2><i class="fas fa-copyright"></i> 10. Intellectual Property Rights</h2>
            <p>The "Alpha Nutrition" name, logo, and all related product and service names, design marks, and slogans are trademarks of Alpha Nutrition. We expressly reserve all intellectual property rights in all text, programs, products, processes, technology, content, and materials on this Website.</p>
            <ul>
                <li>Access to this Website does not confer any intellectual property rights</li>
                <li>Commercial use of Website contents is prohibited without permission</li>
                <li>You may copy or download contents for personal use only</li>
                <li>You may not modify, distribute, or re-post anything for any purpose</li>
                <li>No right, title, or interest in downloaded materials is transferred to you</li>
            </ul>
        </div>

        <!-- Termination Section -->
        <div class="section-card">
            <h2><i class="fas fa-times-circle"></i> 11. Termination</h2>
            <p>These Terms of Use are effective unless terminated by either you or Alpha Nutrition.</p>
            <ul>
                <li>You may terminate by discontinuing use of the Website</li>
                <li>Alpha Nutrition may terminate at any time without notice</li>
                <li>Upon termination, you must destroy all downloaded materials</li>
                <li>Termination does not cancel payment obligations for ordered products</li>
                <li>Termination does not affect any liability that may have arisen under these Terms</li>
            </ul>
        </div>

        <!-- Indemnity Section -->
        <div class="section-card">
            <h2><i class="fas fa-handshake"></i> 12. Indemnity</h2>
            <p>You agree to defend, indemnify, and hold harmless Alpha Nutrition, its employees, directors, officers, agents, successors, assigns, holding companies, subsidiaries, affiliates, partners, and licensors from and against any claims, liabilities, damages, losses, costs, and expenses, including attorney's fees, caused by or arising out of:</p>
            <ul>
                <li>Your actions or inactions that may result in loss or liability</li>
                <li>Breach of any warranties, representations, or undertakings</li>
                <li>Non-fulfillment of obligations under these Terms</li>
                <li>Violation of applicable laws, regulations, or intellectual property rights</li>
                <li>Claims of libel, defamation, or violation of privacy rights</li>
            </ul>
            <p>This clause survives the expiry or termination of the Terms of Use.</p>
        </div>

        <!-- Legal and Dispute Resolution Section -->
        <div class="section-card">
            <h2><i class="fas fa-gavel"></i> 13. Legal and Dispute Resolution</h2>
            <p>No claims or actions arising out of or related to the use of the Website or these Terms may be brought by you more than one (1) year after the cause of action arose.</p>
            <p>If you have a dispute with Alpha Nutrition or are dissatisfied with the Website, termination of your use is your sole remedy. Alpha Nutrition has no obligation, liability, or responsibility to you beyond what is stated in these Terms.</p>
        </div>

        <!-- Variation of Terms Section -->
        <div class="section-card">
            <h2><i class="fas fa-edit"></i> 14. Variation of the Terms of Use</h2>
            <p>At any given point of time, Alpha Nutrition may modify these Terms of Use of the Website, without giving any prior intimation to you. You can review the most current version of the Terms of Use at any time on the Website.</p>
            <p>Alpha Nutrition reserves its right to update, change or replace any part of these Terms of Use by posting updates and changes to the Website. It is your responsibility to check the Website periodically for changes.</p>
            <p><strong>Your continued use of or access of the Website, following the posting of any changes to the Terms of Use, constitutes acceptance of those changes.</strong></p>
        </div>

        <!-- Privacy Policy Section -->
        <div class="section-card" id="privacy-policy">
            <h2><i class="fas fa-shield-alt"></i> 15. Privacy Policy</h2>
            <p>Alpha Nutrition understands the importance of safeguarding your personal information and has formulated a Privacy Policy, to ensure that your personal information is sufficiently protected.</p>
            <p>Apart from these Terms of Use, the Privacy Policy shall also govern your visit and use of the Website. Your continued use of the Website implies that you have read and accepted the Privacy Policy and you agree to be bound by their terms and conditions.</p>
            <p>You hereby consent to the use of personal information by Alpha Nutrition in accordance with the terms and purpose set forth in the Terms of Use as well as the Privacy Policy, which may be subject to amendment from time to time at the sole discretion of Alpha Nutrition.</p>
            <p>Please refer to our <a href="privacy-policy.php" style="color: #007cba; text-decoration: none;">Privacy Policy</a> provided on our Website.</p>
        </div>

        <!-- General Terms Section -->
        <div class="section-card">
            <h2><i class="fas fa-file-contract"></i> 16. General</h2>
            <p>You acknowledge and hereby agree to these Terms of Use and that it constitutes the complete and exclusive agreement between us concerning your use of the Website and supersedes and governs all prior proposals, agreements, or other communications.</p>

            <h3>Rights and Modifications:</h3>
            <p>Alpha Nutrition reserves the right, in our sole discretion, to change/alter/modify these Terms of Use and Policies at any time by posting the changes on the Website. Any changes are effective immediately upon posting to the Website. Your continued use of the Website thereafter constitutes your agreement to all such changes.</p>

            <h3>Termination:</h3>
            <p>Alpha Nutrition may, with or without prior notice, terminate any of the rights granted by these Terms of Use and Policies. You shall comply immediately with any termination or other notice, including, as applicable, by ceasing all use of the Website.</p>

            <h3>Legal Provisions:</h3>
            <ul>
                <li>Nothing contained in these Terms shall be construed as creating any agency, partnership, affiliation, joint venture or other form of joint enterprise between us</li>
                <li>Alpha Nutrition's failure to require your performance of any provision shall not affect our full right to require such performance at any time thereafter</li>
                <li>Alpha Nutrition's waiver of a breach of any provision shall not be taken as a waiver of the provision itself</li>
                <li>If any provision is unenforceable or invalid under applicable law, the Terms shall be modified to the extent possible to reflect the original intent</li>
                <li>The headings of the Terms are for convenience purpose only and shall not be used in interpretation</li>
            </ul>
        </div>

        <!-- Contact Section -->
        <div class="section-card" id="contact">
            <h2><i class="fas fa-phone"></i> 17. Contact Information</h2>
            <p>For any questions, concerns, or inquiries regarding these Terms and Conditions, please contact our Customer Service Desk:</p>
        </div>

        <!-- Enhanced Contact Card -->
        <div class="contact-card">
            <h3><i class="fas fa-headset"></i> Customer Service Information</h3>
            <div class="contact-grid">
                <div class="contact-item">
                    <i class="fas fa-building"></i>
                    <strong>Company</strong>
                    Alpha Nutrition
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <strong>Email</strong>
                    <a href="mailto:support@alphanutrition.co.in">support@alphanutrition.co.in</a>
                </div>
                <div class="contact-item">
                    <i class="fas fa-globe"></i>
                    <strong>Website</strong>
                    <a href="https://alphanutrition.co.in">alphanutrition.co.in</a>
                </div>
                <div class="contact-item">
                    <i class="fas fa-clock"></i>
                    <strong>Support Hours</strong>
                    Monday – Friday<br>9:00 AM – 6:00 PM
                </div>
            </div>
        </div>

        <!-- Final Note -->
        <div class="highlight-box">
            <p><strong>Last Updated:</strong> August 24, 2024</p>
            <p>These Terms and Conditions are effective as of the date mentioned above and will remain in effect except with respect to any changes in provisions, which will be effective immediately after being posted on this page.</p>
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
