<?php include 'includes/header.php'; ?>

<!-- Contact Hero Banner with overlay text -->
<section class="contact-hero-banner"
    style="position:relative; width:100%; min-height:380px; background:url('assets/Get in Touch b.jpg') center center/cover no-repeat; display:flex; align-items:center; justify-content:center;">
    <div style="position:absolute; top:0; left:0; width:100%; height:100%;"></div>
    <div
        style="position:relative; z-index:2; width:100%; display:flex; flex-direction:column; align-items:center; justify-content:center;">
        
    </div>
</section>

<!-- Contact Section: Two Column Layout -->
<section class="contact-main-section" style="padding: 40px 0; background: #fff;">
    <div class="container"
        style="display: flex; flex-wrap: wrap; gap: 40px; align-items: flex-start; justify-content: center;">
        <!-- Left: Contact Info -->
        <div class="contact-info-box"
            style="flex: 1 1 320px; max-width: 340px; min-width: 260px; background: #fff; border-radius: 18px; padding: 32px 24px; margin-bottom: 24px;">
            <div style="margin-bottom: 28px;">
                <div style="font-size: 1.5rem; color: #2563eb; margin-bottom: 8px;"><i class="fas fa-phone"></i></div>
                <h3 class="serif" style="font-size: 1.15rem; font-weight: 700; margin-bottom: 2px;">Phone</h3>
                <p style="margin:0;">+91 9022975030</p>
                <p style="margin:0; font-size:0.98rem; color:#444;">Available 24/7</p>
            </div>
            <div style="margin-bottom: 28px;">
                <div style="font-size: 1.5rem; color: #2563eb; margin-bottom: 8px;"><i class="fas fa-envelope"></i>
                </div>
                <h3 class="serif" style="font-size: 1.15rem; font-weight: 700; margin-bottom: 2px;">Email</h3>
                <p style="margin:0;">support@alphanutrition.com</p>
                <p style="margin:0; font-size:0.98rem; color:#444;">Response within 24 hours</p>
            </div>
            <div>
                <div style="font-size: 1.5rem; color: #2563eb; margin-bottom: 8px;"><i
                        class="fas fa-map-marker-alt"></i></div>
                <h3 class="serif" style="font-size: 1.15rem; font-weight: 700; margin-bottom: 2px;">Location</h3>
                <p style="margin:0;"> 55 North Shivaji Nagar,</p>
                <p style="margin:0;">Near Apta Police Chowk , Sangli - 416416.</p>
            </div>
        </div>
        <!-- Right: Contact Form -->
        <div class="contact-form-container"
            style="flex: 2 1 700px; max-width: 800px; background: #fff; border-radius: 18px; box-shadow: 0 4px 32px rgba(0,0,0,0.10); display: flex; flex-direction: column; align-items: center; padding: 32px 24px; border: 2px solid #222C3A;">
            <!-- Remove the broken image and use a heading instead -->
            <h2 class="serif" style="font-size:2rem; font-weight:800; color:#003366; margin-bottom:18px; text-align:center;">Contact Us</h2>
            <form class="contact-form" style="width: 100%;">
                <div class="contact-form-row" style="display: flex; gap: 16px; margin-bottom: 16px;">
                    <input type="text" class="form-input" placeholder="Full Name" required
                        style="flex:1; background:#f7f7f7; border:1.5px solid rgb(196, 196, 197);  border-radius:8px; padding:14px 16px; font-size:1rem;">
                    <input type="email" class="form-input" placeholder="Email Address" required
                        style="flex:1; background:#f7f7f7; border:1.5px solid rgb(196, 196, 197); border-radius:8px; padding:14px 16px; font-size:1rem;">
                </div>
                <input type="text" class="form-input" placeholder="Subject" required
                    style="width:100%; background:#f7f7f7; border:1.5px solid rgb(196, 196, 197); border-radius:8px; padding:14px 16px; font-size:1rem; margin-bottom:16px;">
                <textarea class="form-input form-textarea" placeholder="Your Message" required
                    style="width:100%; background:#f7f7f7; border:1.5px solid rgb(196, 196, 197); border-radius:8px; padding:14px 16px; font-size:1rem; min-height:100px; margin-bottom:20px;"></textarea>
                <button type="submit" class="form-btn"
                    style="width:100%; background: linear-gradient(90deg, #2196f3 0%, #003366 100%); color:#fff; font-weight:600; font-size:1.1rem; border:none; border-radius:8px; padding:14px 0; cursor:pointer;">Send
                    Message</button>
            </form>
        </div>
    </div>
</section>

<!-- Location Map -->
<section class="map-section">
    <div class="container">
        <div class="map-container">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d193595.25436351647!2d-74.11976373946229!3d40.69766374934705!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c24fa5d33f083b%3A0xc80b8f06e177fe62!2sNew%20York%2C%20NY%2C%20USA!5e0!3m2!1sen!2s!4v1697042309038!5m2!1sen!2s"
                width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</section>


</footer><?php include 'includes/footer.php'; ?>