<!-- Admin Brand Section -->
<div class="admin-brand">
    <div class="brand-logo">
        <i class="fas fa-leaf"></i>
    </div>
    <div class="brand-text">
        <h3>Alpha Nutrition</h3>
        <span>Admin Panel</span>
    </div>
</div>

<!-- Navigation Menu -->
<nav class="admin-nav-menu">
    <div class="nav-section">
        <h4 class="nav-section-title">Main</h4>
        <ul>
            <li><a href="index.php" class="nav-link" data-page="dashboard">
                <div class="nav-icon">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <span class="nav-text">Dashboard</span>
                <div class="nav-indicator"></div>
            </a></li>
        </ul>
    </div>

    <div class="nav-section">
        <h4 class="nav-section-title">Content Management</h4>
        <ul>
            <li><a href="products.php" class="nav-link" data-page="products">
                <div class="nav-icon">
                    <i class="fas fa-box"></i>
                </div>
                <span class="nav-text">Products</span>
                <div class="nav-indicator"></div>
            </a></li>
            <li><a href="categories.php" class="nav-link" data-page="categories">
                <div class="nav-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <span class="nav-text">Categories</span>
                <div class="nav-indicator"></div>
            </a></li>
            <li><a href="blogs.php" class="nav-link" data-page="blogs">
                <div class="nav-icon">
                    <i class="fas fa-blog"></i>
                </div>
                <span class="nav-text">Blog Management</span>
                <div class="nav-indicator"></div>
            </a></li>
            <li><a href="banner-images.php" class="nav-link" data-page="banners">
                <div class="nav-icon">
                    <i class="fas fa-image"></i>
                </div>
                <span class="nav-text">Banner Images</span>
                <div class="nav-indicator"></div>
            </a></li>
            <li><a href="reviews.php" class="nav-link" data-page="reviews">
                <div class="nav-icon">
                    <i class="fas fa-star"></i>
                </div>
                <span class="nav-text">Reviews</span>
                <div class="nav-indicator"></div>
            </a></li>
            <li><a href="review-analytics.php" class="nav-link" data-page="review-analytics">
                <div class="nav-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <span class="nav-text">Review Analytics</span>
                <div class="nav-indicator"></div>
            </a></li>
        </ul>
    </div>

    <div class="nav-section">
        <h4 class="nav-section-title">Settings</h4>
        <ul>
            <li><a href="change-password.php" class="nav-link" data-page="password">
                <div class="nav-icon">
                    <i class="fas fa-key"></i>
                </div>
                <span class="nav-text">Change Password</span>
                <div class="nav-indicator"></div>
            </a></li>
        </ul>
    </div>
</nav>

<!-- Admin Profile Section -->
<div class="admin-profile-section">
    <div class="profile-card">
        <div class="profile-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="profile-info">
            <span class="profile-name">Admin User</span>
            <span class="profile-role">Administrator</span>
        </div>
    </div>
</div>

<style>
/* Modern Admin Sidebar Design */
.admin-container .admin-sidebar {
    background: linear-gradient(180deg, #1e293b 0%, #334155 100%) !important;
    color: white !important;
    position: relative;
    overflow: hidden;
    width: 250px !important;
    padding: 0 !important;
}

.admin-sidebar::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
        radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
    pointer-events: none;
}

/* Admin Brand Section */
.admin-brand {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
    z-index: 2;
}

.brand-logo {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.brand-text h3 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    color: white;
}

.brand-text span {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.7);
    font-weight: 500;
}

/* Navigation Sections */
.nav-section {
    margin: 1.5rem 0;
    position: relative;
    z-index: 2;
}

.nav-section-title {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(255, 255, 255, 0.6);
    margin: 0 0 0.75rem 1.5rem;
    padding: 0;
}

.admin-nav-menu ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.admin-nav-menu li {
    margin: 0;
}

/* Navigation Links */
.nav-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 1.5rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    border-radius: 0;
    margin: 0 0.75rem;
    border-radius: 12px;
}

.nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    transform: translateX(4px);
}

.nav-link.active {
    background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.nav-icon {
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.nav-text {
    font-weight: 500;
    font-size: 0.9rem;
}

.nav-indicator {
    width: 6px;
    height: 6px;
    background: #3b82f6;
    border-radius: 50%;
    margin-left: auto;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.nav-link.active .nav-indicator {
    opacity: 1;
}

/* Admin Profile Section */
.admin-profile-section {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(10px);
}

.profile-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
}

.profile-card:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
}

.profile-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: white;
}

.profile-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.profile-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: white;
}

.profile-role {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.6);
}

/* Responsive Design */
@media (max-width: 768px) {
    .admin-sidebar {
        width: 100%;
        position: fixed;
        top: 0;
        left: -100%;
        height: 100vh;
        z-index: 1000;
        transition: left 0.3s ease;
    }

    .admin-sidebar.open {
        left: 0;
    }

    .brand-text h3 {
        font-size: 1.1rem;
    }

    .nav-link {
        padding: 1rem 1.5rem;
    }
}

/* Animation Effects */
@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.nav-link {
    animation: slideInLeft 0.3s ease forwards;
}

.nav-section:nth-child(1) .nav-link { animation-delay: 0.1s; }
.nav-section:nth-child(2) .nav-link:nth-child(1) { animation-delay: 0.2s; }
.nav-section:nth-child(2) .nav-link:nth-child(2) { animation-delay: 0.3s; }
.nav-section:nth-child(2) .nav-link:nth-child(3) { animation-delay: 0.4s; }
.nav-section:nth-child(2) .nav-link:nth-child(4) { animation-delay: 0.5s; }
.nav-section:nth-child(3) .nav-link { animation-delay: 0.6s; }

/* Hover Effects */
.nav-link::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
    border-radius: 0 3px 3px 0;
    transform: scaleY(0);
    transition: transform 0.3s ease;
}

.nav-link:hover::before,
.nav-link.active::before {
    transform: scaleY(1);
}

/* Scrollbar Styling */
.admin-sidebar::-webkit-scrollbar {
    width: 4px;
}

.admin-sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.admin-sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
}

.admin-sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}
</style>

<script>
// Enhanced Admin Sidebar Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get current page from URL
    const currentPage = window.location.pathname.split('/').pop().replace('.php', '');

    // Map of page names to data-page attributes
    const pageMap = {
        'index': 'dashboard',
        'products': 'products',
        'categories': 'categories',
        'blogs': 'blogs',
        'banner-images': 'banners',
        'change-password': 'password'
    };

    // Set active link based on current page
    const activePageData = pageMap[currentPage] || 'dashboard';
    const activeLink = document.querySelector(`[data-page="${activePageData}"]`);

    if (activeLink) {
        // Remove active class from all links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });

        // Add active class to current page link
        activeLink.classList.add('active');
    }

    // Add click handlers for smooth interactions
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            // Add loading state
            this.style.opacity = '0.7';

            // Create ripple effect
            const ripple = document.createElement('div');
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.3);
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            `;

            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';

            this.style.position = 'relative';
            this.appendChild(ripple);

            // Remove ripple after animation
            setTimeout(() => {
                if (ripple.parentNode) {
                    ripple.parentNode.removeChild(ripple);
                }
            }, 600);
        });

        // Hover effects
        link.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateX(8px)';
            }
        });

        link.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.style.transform = 'translateX(0)';
            }
        });
    });

    // Profile card interaction
    const profileCard = document.querySelector('.profile-card');
    if (profileCard) {
        profileCard.addEventListener('click', function() {
            // Add a subtle pulse effect
            this.style.animation = 'pulse 0.3s ease';
            setTimeout(() => {
                this.style.animation = '';
            }, 300);
        });
    }

    // Mobile sidebar toggle (if needed)
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 &&
                !sidebar.contains(e.target) &&
                !sidebarToggle.contains(e.target) &&
                sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });
    }
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
`;
document.head.appendChild(style);
</script>
