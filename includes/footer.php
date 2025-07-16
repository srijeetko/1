    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3 class="serif">ALPHA NUTRITION</h3>
                    <p>Your distinguished partner in health and fitness excellence. We provide premium quality supplements to help you achieve your ultimate wellness aspirations.</p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/Alphanutritionindia"><i class="fab fa-facebook"></i></a>
                        <a href="https://www.instagram.com/alphanutrition123/"><i class="fab fa-instagram"></i></a>
                        <a href="https://x.com/alpha_nutr44538"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.youtube.com/@alphanutrition1"><i class="fab fa-youtube"></i></a>
                        <a href="https://www.linkedin.com/company/107380500"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3 class="serif">SHOP</h3>
                    <ul>
                        <li><a href="products.php">All Products</a></li>
                        <li><a href="protien.php">Protein</a></li>
                        <li><a href="pre-Workout.php">Pre-Workout</a></li>
                        <li><a href="#">Vitamins</a></li>
                        <li><a href="Weight Management.php">Weight Management</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3 class="serif">COMPANY</h3>
                    <ul>
                        <li><a href="about-us.php">About Us</a></li>
                        <li><a href="blog.php">Blog</a></li>
                        <li><a href="terms-conditions.php">Terms And Conditions</a></li>
                        <li><a href="privacy-policy.php">Privacy Policy</a></li>
                        <li><a href="refund-cancellation-policy.php">Refund & Cancellation Policy</a></li>
                        <li><a href="shipping-policy.php">Shipping Policy</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3 class="serif">SUPPORT</h3>
                    <ul>
                        <li><a href="terms-conditions.php">Terms And Conditions</a></li>
                        <li><a href="mailto:support@purenutritionco.com">Email Support</a></li>
                        <li><a href="tel:+919022975030">Phone: +91 9022975030</a></li>
                        <li><a href="https://wa.me/919022975030">WhatsApp</a></li>
                        <li><a href="#">Help Center</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p>&copy; <?php echo date('Y'); ?> ALPHA NUTRITION (Pure Nutrition Co.). ALL RIGHTS RESERVED.</p>
                    <p class="company-info">Sangli, India | Email: <a href="mailto:support@purenutritionco.com">support@purenutritionco.com</a> | Phone: <a href="tel:+919022975030">+91 9022975030</a></p>
                    <p class="privacy-links">
                        <a href="privacy-policy.php">Privacy Policy</a> |
                        <a href="terms-conditions.php">Terms of Service</a> |
                        <a href="refund-cancellation-policy.php">Refund Policy</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <style>
    /* Enhanced Footer Styling */
    .footer {
        position: relative;
        overflow: hidden;
    }

    .footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, #333, transparent);
    }

    .footer-content {
        position: relative;
        z-index: 2;
    }

    .footer-section {
        position: relative;
        padding: 0 1rem;
        transition: transform 0.3s ease;
    }

    .footer-section:hover {
        transform: translateY(-5px);
    }

    .footer-section h3 {
        position: relative;
        margin-bottom: 2rem;
        padding-bottom: 0.5rem;
    }

    .footer-section h3::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 2px;
        background: linear-gradient(90deg, #fff, transparent);
        transition: width 0.3s ease;
    }

    .footer-section:hover h3::after {
        width: 80px;
    }

    .footer-section ul li {
        position: relative;
        padding-left: 1rem;
        transition: all 0.3s ease;
    }

    .footer-section ul li::before {
        content: '▸';
        position: absolute;
        left: 0;
        color: #666;
        transition: all 0.3s ease;
        opacity: 0;
        transform: translateX(-10px);
    }

    .footer-section ul li:hover::before {
        opacity: 1;
        transform: translateX(0);
        color: #fff;
    }

    .footer-section ul li:hover {
        padding-left: 1.5rem;
    }

    .footer-section ul li a {
        position: relative;
        display: inline-block;
        padding: 0.3rem 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .footer-section ul li a::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 0;
        height: 1px;
        background: #fff;
        transition: width 0.3s ease;
    }

    .footer-section ul li a:hover::after {
        width: 100%;
    }

    .social-links {
        gap: 1rem;
        margin-top: 1rem;
    }

    .social-links a {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        text-decoration: none !important;
    }

    .social-links a::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s ease;
    }

    .social-links a:hover::before {
        left: 100%;
    }

    .social-links a:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-3px) scale(1.1);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }

    .footer-bottom {
        position: relative;
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 1px solid #333;
        background: rgba(0, 0, 0, 0.3);
        backdrop-filter: blur(10px);
    }

    .footer-bottom-content {
        text-align: center;
        position: relative;
    }

    .footer-bottom-content p {
        margin: 8px 0;
        font-size: 0.9em;
        font-weight: 500;
        letter-spacing: 0.5px;
        transition: color 0.3s ease;
    }

    .footer-bottom-content p:hover {
        color: #fff;
    }

    .company-info {
        color: #aaa !important;
        font-size: 0.85em !important;
        padding: 0.5rem 0;
        border-radius: 5px;
        transition: all 0.3s ease;
    }

    .company-info:hover {
        background: rgba(255, 255, 255, 0.05);
        color: #ccc !important;
    }

    .company-info a {
        color: #007cba;
        text-decoration: none;
        padding: 2px 4px;
        border-radius: 3px;
        transition: all 0.3s ease;
        position: relative;
    }

    .company-info a::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 124, 186, 0.1);
        border-radius: 3px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .company-info a:hover::before {
        opacity: 1;
    }

    .company-info a:hover {
        color: #4da6d9;
        transform: translateY(-1px);
    }

    .privacy-links {
        font-size: 0.85em !important;
        margin-top: 15px;
        padding: 1rem 0;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .privacy-links a {
        color: #007cba;
        text-decoration: none;
        margin: 0 8px;
        padding: 5px 10px;
        border-radius: 15px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        display: inline-block;
    }

    .privacy-links a::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 124, 186, 0.1);
        border-radius: 15px;
        transform: scale(0);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .privacy-links a:hover::before {
        transform: scale(1);
    }

    .privacy-links a:hover {
        color: #4da6d9;
        transform: translateY(-2px);
    }

    /* Responsive Enhancements */
    @media (max-width: 768px) {
        .footer-section {
            padding: 0 0.5rem;
            margin-bottom: 2rem;
        }

        .footer-section:hover {
            transform: none;
        }

        .footer-section h3 {
            font-size: 1.1rem;
            text-align: center;
        }

        .footer-section ul {
            text-align: center;
        }

        .footer-section ul li {
            padding-left: 0;
        }

        .footer-section ul li::before {
            display: none;
        }

        .footer-section ul li:hover {
            padding-left: 0;
        }

        .social-links {
            justify-content: center;
            gap: 0.8rem;
        }

        .social-links a {
            width: 40px;
            height: 40px;
        }

        .footer-bottom-content p {
            font-size: 0.8em;
        }

        .company-info {
            font-size: 0.75em !important;
        }

        .privacy-links {
            font-size: 0.75em !important;
        }

        .privacy-links a {
            margin: 2px 4px;
            padding: 3px 8px;
        }
    }

    /* Animation for footer sections */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .footer-section {
        animation: fadeInUp 0.6s ease forwards;
    }

    .footer-section:nth-child(1) { animation-delay: 0.1s; }
    .footer-section:nth-child(2) { animation-delay: 0.2s; }
    .footer-section:nth-child(3) { animation-delay: 0.3s; }
    .footer-section:nth-child(4) { animation-delay: 0.4s; }
    </style>

    <script src="script.js"></script>
</body>
</html>
