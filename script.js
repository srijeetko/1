// Premium Loading Animation
window.addEventListener('load', function() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        setTimeout(() => {
            loadingOverlay.classList.add('hidden');
        }, 1000);
    }
});

// Premium Header Scroll Effect
const header = document.getElementById('header');
if (header) {
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
}

// Carousel functionality with premium animations
let currentSlide = 0;
const carousel = document.getElementById('productCarousel');
if (carousel) {
    const cards = carousel.children;
    const totalCards = cards.length;
    const cardWidth = 350; // 320px + 30px gap

    function slideCarousel(direction) {
        currentSlide += direction;

        if (currentSlide < 0) {
            currentSlide = totalCards - 3;
        } else if (currentSlide > totalCards - 3) {
            currentSlide = 0;
        }

        const translateX = -currentSlide * cardWidth;
        carousel.style.transform = `translateX(${translateX}px)`;
    }

    // Auto-slide carousel with premium timing
    setInterval(() => {
        slideCarousel(1);
    }, 6000);
}

// Premium Add to cart functionality
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('add-to-cart')) {
        e.target.style.transform = 'scale(0.95)';
        e.target.textContent = 'ADDED!';
        e.target.style.background = '#000';
        e.target.style.color = 'white';
        
        setTimeout(() => {
            e.target.style.transform = 'scale(1)';
            e.target.textContent = 'ADD TO CART';
            e.target.style.background = '';
            e.target.style.color = '';
        }, 2000);
        
        // Update cart count with animation
        const cartCount = document.querySelector('.cart-count');
        let count = parseInt(cartCount.textContent);
        cartCount.textContent = count + 1;
        cartCount.style.transform = 'scale(1.3)';
        setTimeout(() => {
            cartCount.style.transform = 'scale(1)';
        }, 300);
    }
});

// Premium Newsletter form submission
document.querySelector('.newsletter-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const email = this.querySelector('.newsletter-input').value;
    const btn = this.querySelector('.newsletter-btn');
    
    btn.textContent = 'SUBSCRIBED!';
    btn.style.background = 'white';
    btn.style.color = '#000';
    
    setTimeout(() => {
        btn.textContent = 'SUBSCRIBE';
        btn.style.background = '';
        btn.style.color = '';
        this.reset();
    }, 3000);
});

// Enhanced Search functionality with fuzzy search
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('header-search-form');
    const searchInput = document.getElementById('header-search-input');

    if (searchForm && searchInput) {
        // Handle form submission
        searchForm.addEventListener('submit', function(e) {
            const searchTerm = searchInput.value.trim();
            if (!searchTerm) {
                e.preventDefault();
                searchInput.focus();
                return;
            }

            // Visual feedback
            searchInput.style.transform = 'scale(1.02)';
            setTimeout(() => {
                searchInput.style.transform = 'scale(1)';
            }, 200);
        });

        // Handle Enter key press
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.trim();
                if (searchTerm) {
                    this.style.transform = 'scale(1.02)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 200);
                    // Form will submit automatically
                } else {
                    e.preventDefault();
                    this.focus();
                }
            }
        });

        // Auto-complete suggestions with fuzzy search
        let suggestionTimeout;
        let currentSuggestionIndex = -1;
        const suggestionsContainer = document.getElementById('search-suggestions');

        searchInput.addEventListener('input', function() {
            clearTimeout(suggestionTimeout);
            const query = this.value.trim();

            if (query.length >= 2) {
                suggestionTimeout = setTimeout(() => {
                    fetchSearchSuggestions(query);
                }, 300);
            } else {
                hideSuggestions();
            }
        });

        // Handle keyboard navigation
        searchInput.addEventListener('keydown', function(e) {
            const suggestions = suggestionsContainer.querySelectorAll('.suggestion-item');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                currentSuggestionIndex = Math.min(currentSuggestionIndex + 1, suggestions.length - 1);
                updateSuggestionHighlight(suggestions);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                currentSuggestionIndex = Math.max(currentSuggestionIndex - 1, -1);
                updateSuggestionHighlight(suggestions);
            } else if (e.key === 'Enter' && currentSuggestionIndex >= 0) {
                e.preventDefault();
                const selectedSuggestion = suggestions[currentSuggestionIndex];
                if (selectedSuggestion) {
                    window.location.href = selectedSuggestion.dataset.url;
                }
            } else if (e.key === 'Escape') {
                hideSuggestions();
                currentSuggestionIndex = -1;
            }
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchForm.contains(e.target)) {
                hideSuggestions();
            }
        });

        function fetchSearchSuggestions(query) {
            showLoadingSuggestions();

            fetch(`api/search-suggestions.php?q=${encodeURIComponent(query)}&limit=8`)
                .then(response => response.json())
                .then(data => {
                    displaySuggestions(data.suggestions);
                })
                .catch(error => {
                    console.error('Error fetching suggestions:', error);
                    hideSuggestions();
                });
        }

        function showLoadingSuggestions() {
            suggestionsContainer.innerHTML = '<div class="suggestion-loading"><i class="fas fa-spinner fa-spin"></i> Loading suggestions...</div>';
            suggestionsContainer.style.display = 'block';
        }

        function displaySuggestions(suggestions) {
            if (suggestions.length === 0) {
                suggestionsContainer.innerHTML = '<div class="suggestion-no-results">No suggestions found</div>';
                suggestionsContainer.style.display = 'block';
                return;
            }

            const html = suggestions.map((suggestion, index) => {
                const iconClass = suggestion.type === 'product' ? 'fas fa-box' :
                                suggestion.type === 'category' ? 'fas fa-tags' : 'fas fa-search';

                return `
                    <div class="suggestion-item" data-url="${suggestion.url}" data-index="${index}">
                        <div class="suggestion-icon ${suggestion.type}">
                            <i class="${iconClass}"></i>
                        </div>
                        <div class="suggestion-content">
                            <div class="suggestion-title">${suggestion.title}</div>
                            <div class="suggestion-subtitle">${suggestion.subtitle}</div>
                        </div>
                    </div>
                `;
            }).join('');

            suggestionsContainer.innerHTML = html;
            suggestionsContainer.style.display = 'block';
            currentSuggestionIndex = -1;

            // Add click handlers
            suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('click', function() {
                    window.location.href = this.dataset.url;
                });
            });
        }

        function updateSuggestionHighlight(suggestions) {
            suggestions.forEach((item, index) => {
                item.classList.toggle('highlighted', index === currentSuggestionIndex);
            });
        }

        function hideSuggestions() {
            suggestionsContainer.style.display = 'none';
            currentSuggestionIndex = -1;
        }
    }
});

// Premium Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
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

// Premium Cursor Effect (Optional)
document.addEventListener('mousemove', function(e) {
    const cursor = document.querySelector('.custom-cursor');
    if (cursor) {
        cursor.style.left = e.clientX + 'px';
        cursor.style.top = e.clientY + 'px';
    }
});

// Premium Product Card Hover Effects
document.querySelectorAll('.product-card, .featured-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-15px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

// Premium Category Card Interactions
document.querySelectorAll('.category-card').forEach(card => {
    card.addEventListener('click', function() {
        this.style.transform = 'scale(0.98)';
        setTimeout(() => {
            this.style.transform = 'scale(1)';
        }, 150);
    });
});

// Quantity selector functionality
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('quantity-btn')) {
        const input = e.target.parentElement.querySelector('.quantity-input');
        const currentValue = parseInt(input.value);
        
        if (e.target.classList.contains('plus')) {
            input.value = currentValue + 1;
        } else if (e.target.classList.contains('minus') && currentValue > 1) {
            input.value = currentValue - 1;
        }
    }
});

// Product Filtering
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const productCards = document.querySelectorAll('.product-card');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active button
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const selectedCategory = this.getAttribute('data-category');

            // Update URL with selected category
            const url = new URL(window.location);
            if (selectedCategory === 'all') {
                url.searchParams.delete('category');
            } else {
                url.searchParams.set('category', selectedCategory);
            }
            window.history.pushState({}, '', url);

            // Refresh the page to load filtered products
            window.location.reload();
        });
    });

    // Weight variant selection
    const variantButtons = document.querySelectorAll('.variant-option');
    variantButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.disabled) return;

            // Update selection in the same product card
            const card = this.closest('.product-card');
            card.querySelectorAll('.variant-option').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');

            // Update price display
            const price = this.getAttribute('data-price');
            card.querySelector('.price-display').textContent = '₹' + parseFloat(price).toFixed(2);
        });
    });
});

// HERO IMAGE SLIDER (reset and robust)
document.addEventListener('DOMContentLoaded', function() {
    const heroSlides = document.querySelectorAll('.hero-img-slide');
    const leftBtn = document.getElementById('hero-slider-left');
    const rightBtn = document.getElementById('hero-slider-right');
    let currentHero = 0;
    const totalHero = heroSlides.length;

    function showHeroSlide(idx) {
        heroSlides.forEach((img, i) => {
            img.style.opacity = (i === idx) ? '1' : '0';
            img.style.zIndex = (i === idx) ? '2' : '1';
        });
    }
    if (leftBtn && rightBtn && totalHero > 1) {
        leftBtn.onclick = function() {
            currentHero = (currentHero - 1 + totalHero) % totalHero;
            showHeroSlide(currentHero);
        };
        rightBtn.onclick = function() {
            currentHero = (currentHero + 1) % totalHero;
            showHeroSlide(currentHero);
        };
        setInterval(function() {
            currentHero = (currentHero + 1) % totalHero;
            showHeroSlide(currentHero);
        }, 6000);
    }
    showHeroSlide(0);
});