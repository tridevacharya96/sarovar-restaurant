<?php
require_once 'config/settings.php';
$s = getSettings();
?>
<!DOCTYPE html>
<!-- index.html -->
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= setting('restaurant_name') ?> – Rourkela</title>
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/events.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
</head>
<body>

<!-- ===== PRELOADER ===== -->
<div id="preloader">
    <div class="preloader-inner">
        <div class="preloader-logo">🍽️</div>
        <div class="preloader-text">Sarovar</div>
        <div class="preloader-bar"><span></span></div>
    </div>
</div>

<!-- ===== NAVBAR ===== -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <div class="nav-logo">
            <span class="logo-icon">🍽️</span>
            <div class="logo-text">
                <span class="logo-main">The Sarovar Court</span>
                <span class="logo-sub">Multicuisine Restaurant · Rourkela</span>
            </div>
        </div>
        <ul class="nav-links" id="navLinks">
            <li><a href="#home"        class="nav-link active">Home</a></li>
            <li><a href="#about"       class="nav-link">About</a></li>
            <li><a href="#menu"        class="nav-link">Menu</a></li>
            <li><a href="#gallery"     class="nav-link">Gallery</a></li>
            <li><a href="events.php"   class="nav-link">Events</a></li>
            <li><a href="#reservation" class="nav-link">Reserve</a></li>
            <li><a href="#order"       class="nav-link">Order</a></li>
            <li><a href="#contact"     class="nav-link">Contact</a></li>
        </ul>
        <div class="nav-actions">
            <button class="cart-btn" id="cartBtn">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count" id="cartCount">0</span>
            </button>
            <div class="auth-buttons" id="authButtons">
                <button class="btn btn-outline" onclick="openModal('loginModal')">Login</button>
                <button class="btn btn-primary" onclick="openModal('registerModal')">Sign Up</button>
            </div>
            <div class="user-menu" id="userMenu" style="display:none">
                <button class="user-btn" id="userBtn">
                    <i class="fas fa-user-circle"></i>
                    <span id="userName"></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <a href="#" onclick="showMyOrders()"><i class="fas fa-box"></i> My Orders</a>
                    <a href="#" onclick="showMyReservations()"><i class="fas fa-calendar"></i> My Reservations</a>
                    <a href="#" onclick="logoutUser()"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            <button class="hamburger" id="hamburger">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>

<!-- ===== HERO ===== -->
<section class="hero" id="home">
    <div class="hero-slider" id="heroSlider">
        <div class="hero-slide active" style="background-image:url('images/hero1.jpg')"></div>
        <div class="hero-slide"       style="background-image:url('images/hero2.jpg')"></div>
        <div class="hero-slide"       style="background-image:url('images/hero3.jpg')"></div>
    </div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <p class="hero-tagline">Welcome to</p>
        <h1 class="hero-title"><?= setting('restaurant_name') ?></h1>
        <p class="hero-subtitle"><?= setting('tagline') ?></p>
        <div class="hero-badges">
            <span><i class="fas fa-star"></i> Premium Dining</span>
            <span><i class="fas fa-leaf"></i> Fresh Ingredients</span>
            <span><i class="fas fa-truck"></i> Home Delivery</span>
        </div>
        <div class="hero-buttons">
            <a href="#menu"        class="btn btn-primary btn-lg">Explore Menu</a>
            <a href="#reservation" class="btn btn-outline btn-lg">Book a Table</a>
        </div>
    </div>
    <div class="hero-slider-dots" id="sliderDots">
        <span class="dot active" onclick="goToSlide(0)"></span>
        <span class="dot"        onclick="goToSlide(1)"></span>
        <span class="dot"        onclick="goToSlide(2)"></span>
    </div>
    <div class="scroll-indicator">
        <div class="scroll-mouse"><div class="scroll-wheel"></div></div>
        <span>Scroll Down</span>
    </div>
</section>

<!-- ===== STATS BAR ===== -->
<section class="stats-bar">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-icon"><i class="fas fa-utensils"></i></div>
                <div class="stat-info">
                    <span class="stat-number" data-target="150">0</span><span>+</span>
                    <p>Menu Items</p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <span class="stat-number" data-target="5000">0</span><span>+</span>
                    <p>Happy Customers</p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon"><i class="fas fa-award"></i></div>
                <div class="stat-info">
                    <span class="stat-number" data-target="15">0</span><span>+</span>
                    <p>Years Experience</p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-info">
                    <span class="stat-number" data-target="4">0</span><span>.8 Rating</span>
                    <p>Customer Rating</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== ABOUT ===== -->
<section class="about" id="about">
    <div class="container">
        <div class="about-grid">
            <div class="about-images">
                <div class="about-img-main">
                    <img src="images/about1.jpg" alt="Sarovar Restaurant Interior" />
                </div>
                <div class="about-img-secondary">
                    <img src="images/about2.jpg" alt="Chef at work" />
                </div>
                <div class="about-experience-badge">
                    <span class="badge-number">15+</span>
                    <span class="badge-text">Years of Excellence</span>
                </div>
            </div>
            <div class="about-content">
                <div class="section-label">Our Story</div>
                <h2 class="section-title">A Culinary Journey<br/><span>Since <?= setting('established_year') ?></span></h2>
                <p class="about-desc"><?= setting('about_text_1') ?></p>
                <p class="about-desc"><?= setting('about_text_2') ?></p>
                <div class="about-features">
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>100% Fresh & Hygienic Ingredients</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Authentic Traditional Recipes</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Expert Chefs with 20+ Years Experience</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle"></i>
                        <span>Warm & Welcoming Ambiance</span>
                    </div>
                </div>
                <div class="about-buttons">
                    <a href="#menu"        class="btn btn-primary">View Our Menu</a>
                    <a href="#reservation" class="btn btn-outline">Book a Table</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FEATURED MENU ===== -->
<section class="featured-menu" id="menu">
    <div class="container">
        <div class="section-header">
            <div class="section-label">Our Menu</div>
            <h2 class="section-title">Signature <span>Dishes</span></h2>
            <p class="section-subtitle">Handcrafted with love using the finest ingredients</p>
        </div>
        <div class="menu-filter" id="menuFilter">
            <button class="filter-btn active" data-category="all">All</button>
        </div>
        <div class="menu-grid" id="menuGrid">
            <div class="menu-loading">
                <i class="fas fa-spinner fa-spin"></i> Loading menu...
            </div>
        </div>
        <div class="menu-cta">
            <button class="btn btn-primary btn-lg" onclick="openFullMenu()">
                <i class="fas fa-book-open"></i> View Full Menu
            </button>
        </div>
    </div>
</section>

<!-- ===== SPECIAL OFFER BANNER ===== -->
<section class="offer-banner">
    <div class="container">
        <div class="offer-content">
            <div class="offer-text">
                <span class="offer-tag">Special Offer</span>
                <h2>Get 20% Off on Your First Online Order!</h2>
                <p>Use code <strong>SAROVAR20</strong> at checkout. Valid for new customers only.</p>
            </div>
            <div class="offer-action">
                <a href="#order" class="btn btn-white btn-lg">Order Now</a>
            </div>
        </div>
    </div>
</section>

<!-- ===== GALLERY ===== -->
<section class="gallery-section" id="gallery">
    <div class="container">
        <div class="section-header">
            <div class="section-label">Gallery</div>
            <h2 class="section-title">A Glimpse of <span>Sarovar</span></h2>
            <p class="section-subtitle">Moments captured from our kitchen and dining hall</p>
        </div>
        <div class="gallery-filter">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="food">Food</button>
            <button class="filter-btn" data-filter="interior">Interior</button>
        </div>
        <div class="gallery-grid" id="galleryGrid">
            <div class="gallery-item" data-category="interior">
                <img src="images/gallery1.jpg" alt="Restaurant Interior" />
                <div class="gallery-overlay">
                    <i class="fas fa-expand"></i>
                    <span>Restaurant Interior</span>
                </div>
            </div>
            <div class="gallery-item" data-category="interior">
                <img src="images/gallery2.jpg" alt="Dining Area" />
                <div class="gallery-overlay">
                    <i class="fas fa-expand"></i>
                    <span>Dining Area</span>
                </div>
            </div>
            <div class="gallery-item" data-category="food">
                <img src="images/gallery3.jpg" alt="Special Thali" />
                <div class="gallery-overlay">
                    <i class="fas fa-expand"></i>
                    <span>Special Thali</span>
                </div>
            </div>
            <div class="gallery-item" data-category="food">
                <img src="images/gallery4.jpg" alt="Biryani" />
                <div class="gallery-overlay">
                    <i class="fas fa-expand"></i>
                    <span>Chicken Biryani</span>
                </div>
            </div>
            <div class="gallery-item" data-category="food">
                <img src="images/gallery5.jpg" alt="Desserts" />
                <div class="gallery-overlay">
                    <i class="fas fa-expand"></i>
                    <span>Desserts</span>
                </div>
            </div>
            <div class="gallery-item" data-category="interior">
                <img src="images/gallery6.jpg" alt="Private Dining" />
                <div class="gallery-overlay">
                    <i class="fas fa-expand"></i>
                    <span>Private Dining</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== RESERVATION ===== -->
<section class="reservation-section" id="reservation">
    <div class="reservation-bg"></div>
    <div class="container">
        <div class="reservation-grid">
            <div class="reservation-info">
                <div class="section-label light">Reservations</div>
                <h2 class="section-title light">Book Your <span>Table</span></h2>
                <p>Reserve your table in advance and enjoy a seamless dining experience at Sarovar.</p>
                <div class="res-details">
                    <div class="res-detail-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Opening Hours</strong>
                            <span><?= setting('hours_weekday') ?>: <?= setting('hours_open') ?> – <?= setting('hours_close') ?></span>
                        </div>
                    </div>
                    <div class="res-detail-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <strong>Call Us</strong>
                            <span>+91 98765 43210</span>
                        </div>
                    </div>
                    <div class="res-detail-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <strong>Location</strong>
                            <span>Civil Township, Rourkela, Odisha – 769004</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="reservation-form-card">
                <h3>Make a Reservation</h3>
                <form id="reservationForm" class="reservation-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Full Name *</label>
                            <input type="text" name="name" placeholder="Your full name" required />
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email *</label>
                            <input type="email" name="email" placeholder="your@email.com" required />
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone *</label>
                            <input type="tel" name="phone" placeholder="+91 XXXXX XXXXX" required />
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-users"></i> Guests *</label>
                            <select name="guests" required>
                                <option value="">Select guests</option>
                                <option value="1">1 Person</option>
                                <option value="2">2 People</option>
                                <option value="3">3 People</option>
                                <option value="4">4 People</option>
                                <option value="5">5 People</option>
                                <option value="6">6 People</option>
                                <option value="7">7+ People</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Date *</label>
                            <input type="date" name="date" required />
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Time *</label>
                            <select name="time" required>
                                <option value="">Select time</option>
                                <option value="11:00">11:00 AM</option>
                                <option value="11:30">11:30 AM</option>
                                <option value="12:00">12:00 PM</option>
                                <option value="12:30">12:30 PM</option>
                                <option value="13:00">1:00 PM</option>
                                <option value="13:30">1:30 PM</option>
                                <option value="14:00">2:00 PM</option>
                                <option value="19:00">7:00 PM</option>
                                <option value="19:30">7:30 PM</option>
                                <option value="20:00">8:00 PM</option>
                                <option value="20:30">8:30 PM</option>
                                <option value="21:00">9:00 PM</option>
                                <option value="21:30">9:30 PM</option>
                                <option value="22:00">10:00 PM</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-comment"></i> Special Requests</label>
                        <textarea name="special_requests" rows="3"
                            placeholder="Any dietary requirements or special occasions?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-calendar-check"></i> Confirm Reservation
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- ===== ORDER SECTION ===== -->
<section class="order-section" id="order">
    <div class="container">
        <div class="section-header">
            <div class="section-label">Online Order</div>
            <h2 class="section-title">Order <span>Delivery</span></h2>
            <p class="section-subtitle">Fresh food delivered to your doorstep within 45–60 minutes</p>
        </div>
        <div class="order-grid">
            <div class="order-menu-panel">
                <div class="order-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="orderSearch" placeholder="Search dishes..." />
                </div>
                <div class="order-categories" id="orderCategories"></div>
                <div class="order-items-grid" id="orderItemsGrid">
                    <div class="menu-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
                </div>
            </div>
            <div class="cart-panel" id="cartPanel">
                <div class="cart-header">
                    <h3><i class="fas fa-shopping-cart"></i> Your Cart</h3>
                    <button class="clear-cart" onclick="clearCart()">
                        <i class="fas fa-trash"></i> Clear
                    </button>
                </div>
                <div class="cart-items" id="cartItems">
                    <div class="cart-empty">
                        <i class="fas fa-shopping-basket"></i>
                        <p>Your cart is                         empty</p>
                        <span>Add items from the menu</span>
                    </div>
                </div>
                <div class="cart-summary" id="cartSummary" style="display:none">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="cartSubtotal">₹0</span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery Charge</span>
                        <span>₹40</span>
                    </div>
                    <div class="summary-row">
                        <span>GST (5%)</span>
                        <span id="cartGST">₹0</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span id="cartTotal">₹0</span>
                    </div>
                    <button class="btn btn-primary btn-full" onclick="openCheckout()">
                        <i class="fas fa-lock"></i> Proceed to Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== TESTIMONIALS ===== -->
<section class="testimonials">
    <div class="container">
        <div class="section-header">
            <div class="section-label">Testimonials</div>
            <h2 class="section-title">What Our <span>Guests Say</span></h2>
        </div>
        <div class="testimonials-slider" id="testimonialsSlider">
            <div class="testimonial-card active">
                <div class="testimonial-stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i>
                    <i class="fas fa-star"></i><i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <p class="testimonial-text">
                    "Sarovar is hands down the best restaurant in Rourkela! The Chicken Biryani
                    is absolutely divine and the service is impeccable. A must-visit!"
                </p>
                <div class="testimonial-author">
                    <div class="author-avatar">RK</div>
                    <div class="author-info">
                        <strong>Rajesh Kumar</strong>
                        <span>Regular Customer</span>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i>
                    <i class="fas fa-star"></i><i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <p class="testimonial-text">
                    "We celebrated our anniversary here and it was magical. The ambiance,
                    the food, the staff — everything was perfect. Highly recommended!"
                </p>
                <div class="testimonial-author">
                    <div class="author-avatar">PM</div>
                    <div class="author-info">
                        <strong>Priya Mishra</strong>
                        <span>Food Blogger</span>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i>
                    <i class="fas fa-star"></i><i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                </div>
                <p class="testimonial-text">
                    "The Dal Makhani and Butter Naan combo is out of this world! Fast delivery
                    and food arrives hot. Sarovar never disappoints!"
                </p>
                <div class="testimonial-author">
                    <div class="author-avatar">AS</div>
                    <div class="author-info">
                        <strong>Amit Sharma</strong>
                        <span>Corporate Client</span>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i>
                    <i class="fas fa-star"></i><i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                </div>
                <p class="testimonial-text">
                    "Best vegetarian options in Rourkela! The Paneer Tikka and Veg Biryani
                    are absolutely delicious. Great value for money!"
                </p>
                <div class="testimonial-author">
                    <div class="author-avatar">SP</div>
                    <div class="author-info">
                        <strong>Sunita Patel</strong>
                        <span>Family Customer</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="testimonial-controls">
            <button class="testimonial-prev" onclick="prevTestimonial()">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="testimonial-dots" id="testimonialDots">
                <span class="dot active"></span>
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
            <button class="testimonial-next" onclick="nextTestimonial()">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</section>

<!-- ===== CONTACT ===== -->
<section class="contact-section" id="contact">
    <div class="container">
        <div class="section-header">
            <div class="section-label">Contact Us</div>
            <h2 class="section-title">Get In <span>Touch</span></h2>
            <p class="section-subtitle">We'd love to hear from you</p>
        </div>
        <div class="contact-grid">
            <div class="contact-info">
                <div class="contact-card">
                    <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="contact-details">
                        <h4>Our Location</h4>
                        <p>Civil Township, Rourkela<br/>Odisha – 769004, India</p>
                    </div>
                </div>
                <div class="contact-card">
                    <div class="contact-icon"><i class="fas fa-phone-alt"></i></div>
                    <div class="contact-details">
                        <h4>Phone Numbers</h4>
                        <p><?= setting('phone_primary') ?><br/><?= setting('phone_secondary') ?></p>
                    </div>
                </div>
                <div class="contact-card">
                    <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                    <div class="contact-details">
                        <h4>Email Address</h4>
                        <p><?= setting('email_primary') ?><br/><?= setting('email_reservations') ?></p>
                    </div>
                </div>
                <div class="contact-card">
                    <div class="contact-icon"><i class="fas fa-clock"></i></div>
                    <div class="contact-details">
                        <h4>Working Hours</h4>
                        <p><?= setting('hours_weekday') ?><br/><?= setting('hours_open') ?> – <?= setting('hours_close') ?></p>
                    </div>
                </div>
                <div class="social-links">
                    <a href="<?= setting('social_facebook') ?>" class="social-link facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="<?= setting('social_instagram') ?>" class="social-link instagram"><i class="fab fa-instagram"></i></a>
                    <a href="<?= setting('social_twitter') ?>" class="social-link twitter"><i class="fab fa-twitter"></i></a>
                    <a href="<?= setting('social_whatsapp') ?>" class="social-link whatsapp"><i class="fab fa-whatsapp"></i></a>
                    <a href="<?= setting('social_youtube') ?>" class="social-link youtube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="contact-form-wrap">
                <form id="contactForm" class="contact-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Your Name *</label>
                            <input type="text" name="name" placeholder="Full name" required />
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" placeholder="your@email.com" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" placeholder="How can we help?" />
                    </div>
                    <div class="form-group">
                        <label>Message *</label>
                        <textarea name="message" rows="5"
                            placeholder="Write your message here..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
            <div class="contact-map">
                <iframe
                    src="<?= settingRaw('google_maps_embed') ?>"
                    width="100%" height="300" style="border:0;" allowfullscreen=""
                    loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </div>
</section>

<!-- ===== EVENTS SECTION ===== -->
<section class="events-section" id="events">
    <div class="container">
        <div class="section-header">
            <div class="section-label">Private Events</div>
            <h2 class="section-title">Host Your <span>Dream Event</span></h2>
            <p class="section-subtitle">Weddings, birthdays, corporate events — we make every occasion extraordinary</p>
        </div>
        <div class="events-preview-grid">
            <div class="events-preview-card" onclick="window.location.href='events.php'">
                <div class="events-preview-icon"><i class="fas fa-ring"></i></div>
                <h3>Weddings & Receptions</h3>
                <p>Luxurious setups for up to 500 guests with floral décor, live counters, and dedicated coordination.</p>
            </div>
            <div class="events-preview-card" onclick="window.location.href='events.php'">
                <div class="events-preview-icon"><i class="fas fa-birthday-cake"></i></div>
                <h3>Birthdays & Anniversaries</h3>
                <p>Celebrate life's milestones with themed décor, custom menus, and an unforgettable atmosphere.</p>
            </div>
            <div class="events-preview-card" onclick="window.location.href='events.php'">
                <div class="events-preview-icon"><i class="fas fa-briefcase"></i></div>
                <h3>Corporate Events</h3>
                <p>Professional venue setup for conferences, team dinners, product launches, and award ceremonies.</p>
            </div>
        </div>
        <div class="events-cta">
            <div style="display:flex;align-items:center;justify-content:center;gap:16px;flex-wrap:wrap">
                <div style="text-align:center;color:rgba(255,255,255,0.4);font-size:13px">
                    <strong style="color:#fff;display:block;font-size:18px">Packages from ₹75,000</strong>
                    Silver · Gold · Platinum — or fully custom
                </div>
                <a href="events.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-calendar-check"></i> Explore & Book Events
                </a>
            </div>
        </div>
    </div>
</section>
<!-- ===== END EVENTS SECTION ===== -->

<!-- ===== FOOTER ===== -->
<footer class="footer">
    <div class="footer-top">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <span>🍽️</span>
                        <div>
                            <span class="logo-main">The Sarovar Court</span>
                            <span class="logo-sub">Multicuisine Restaurant · Rourkela</span>
                        </div>
                    </div>
                    <p>Experience the finest flavors of India in the heart of Rourkela.
                       Authentic cuisine, warm hospitality, unforgettable memories.</p>
                    <div class="footer-social">
                        <a href="<?= setting('social_facebook') ?>"><i class="fab fa-facebook-f"></i></a>
                        <a href="<?= setting('social_instagram') ?>"><i class="fab fa-instagram"></i></a>
                        <a href="<?= setting('social_twitter') ?>"><i class="fab fa-twitter"></i></a>
                        <a href="<?= setting('social_whatsapp') ?>"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="#home"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="#about"><i class="fas fa-chevron-right"></i> About Us</a></li>
                        <li><a href="#menu"><i class="fas fa-chevron-right"></i> Our Menu</a></li>
                        <li><a href="#gallery"><i class="fas fa-chevron-right"></i> Gallery</a></li>
                        <li><a href="#reservation"><i class="fas fa-chevron-right"></i> Reservations</a></li>
                        <li><a href="#contact"><i class="fas fa-chevron-right"></i> Contact</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>Our Menu</h4>
                    <ul>
                        <li><a href="#menu"><i class="fas fa-chevron-right"></i> Starters</a></li>
                        <li><a href="#menu"><i class="fas fa-chevron-right"></i> Main Course</a></li>
                        <li><a href="#menu"><i class="fas fa-chevron-right"></i> Breads</a></li>
                        <li><a href="#menu"><i class="fas fa-chevron-right"></i> Rice & Biryani</a></li>
                        <li><a href="#menu"><i class="fas fa-chevron-right"></i> Desserts</a></li>
                        <li><a href="#menu"><i class="fas fa-chevron-right"></i> Beverages</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h4>Contact Info</h4>
                    <div class="footer-contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?= setting('address_line1') ?>, <?= setting('address_line2') ?>, <?= setting('address_city') ?> – <?= setting('address_pincode') ?></span>
                    </div>
                    <div class="footer-contact-item">
                        <i class="fas fa-phone"></i>
                        <span><?= setting('phone_primary') ?></span>
                    </div>
                    <div class="footer-contact-item">
                        <i class="fas fa-envelope"></i>
                        <span><?= setting('email_primary') ?></span>
                    </div>
                    <div class="footer-contact-item">
                        <i class="fas fa-clock"></i>
                        <span><?= setting('hours_weekday') ?>: <?= setting('hours_open') ?> – <?= setting('hours_close') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <p>&copy; 2026 The Sarovar Court – Multicuisine Restaurant, Rourkela. All Rights Reserved.</p>
            <p>Designed with <i class="fas fa-heart" style="color:#e74c3c"></i> for food lovers</p>
        </div>
    </div>
</footer>

<!-- ===== MODALS ===== -->

<!-- Login Modal -->
<div class="modal-overlay" id="loginModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('loginModal')">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-header">
            <div class="modal-logo">🍽️</div>
            <h2>Welcome Back!</h2>
            <p>Login to your Sarovar account</p>
        </div>
        <form id="loginForm" class="modal-form">
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" placeholder="your@email.com" required />
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <div class="password-wrap">
                    <input type="password " name="password" id="loginPassword" placeholder="Your password" required />
                    <button type="button" class="toggle-password" onclick="togglePassword('loginPassword')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div id="loginError" class="form-error" style="display:none"></div>
            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        <div class="modal-footer">
            <p>Don't have an account?
                <a href="#" onclick="switchModal('loginModal','registerModal')">Sign Up</a>
            </p>
        </div>
    </div>
</div>

<!-- Register Modal -->
<div class="modal-overlay" id="registerModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('registerModal')">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-header">
            <div class="modal-logo">🍽️</div>
            <h2>Create Account</h2>
            <p>Join the Sarovar family today</p>
        </div>
        <form id="registerForm" class="modal-form">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="name" placeholder="Your full name" required />
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone</label>
                    <input type="tel" name="phone" placeholder="+91 XXXXX XXXXX" />
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" placeholder="your@email.com" required />
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <div class="password-wrap">
                    <input type="password" name="password" id="registerPassword"
                           placeholder="Min. 6 characters" required />
                    <button type="button" class="toggle-password"
                            onclick="togglePassword('registerPassword')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div id="registerError" class="form-error" style="display:none"></div>
            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>
        <div class="modal-footer">
            <p>Already have an account?
                <a href="#" onclick="switchModal('registerModal','loginModal')">Login</a>
            </p>
        </div>
    </div>
</div>

<!-- Full Menu Modal -->
<div class="modal-overlay modal-fullscreen" id="fullMenuModal">
    <div class="modal modal-large">
        <button class="modal-close" onclick="closeModal('fullMenuModal')">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-header">
            <h2>🍽️ Complete Menu</h2>
            <p>The Sarovar Court – Multicuisine Restaurant, Rourkela</p>
        </div>
        <div class="full-menu-filter" id="fullMenuFilter"></div>
        <div class="full-menu-grid" id="fullMenuGrid">
            <div class="menu-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div class="modal-overlay" id="checkoutModal">
    <div class="modal modal-medium">
        <button class="modal-close" onclick="closeModal('checkoutModal')">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-header">
            <h2><i class="fas fa-shopping-bag"></i> Checkout</h2>
            <p>Complete your order details</p>
        </div>
        <form id="checkoutForm" class="modal-form">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name *</label>
                    <input type="text" name="name" placeholder="Your full name" required />
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone *</label>
                    <input type="tel" name="phone" placeholder="+91 XXXXX XXXXX" required />
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email *</label>
                <input type="email" name="email" placeholder="your@email.com" required />
            </div>
            <div class="form-group">
                <label><i class="fas fa-map-marker-alt"></i> Delivery Address *</label>
                <textarea name="address" rows="3"
                    placeholder="Full delivery address with landmark" required></textarea>
            </div>
            <div class="form-group">
                <label><i class="fas fa-credit-card"></i> Payment Method</label>
                <div class="payment-options">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="cod" checked />
                        <span><i class="fas fa-money-bill-wave"></i> Cash on Delivery</span>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="online" />
                        <span><i class="fas fa-mobile-alt"></i> Online Payment (UPI)</span>
                    </label>
                </div>
            </div>
            <div class="checkout-summary">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span id="checkoutSubtotal">₹0</span>
                </div>
                <div class="summary-row">
                    <span>Delivery</span>
                    <span>₹40</span>
                </div>
                <div class="summary-row">
                    <span>GST (5%)</span>
                    <span id="checkoutGST">₹0</span>
                </div>
                <div class="summary-row total">
                    <span>Grand Total</span>
                    <span id="checkoutTotal">₹0</span>
                </div>
            </div>
            <div id="checkoutError" class="form-error" style="display:none"></div>
            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-check-circle"></i> Place Order
            </button>
        </form>
    </div>
</div>

<!-- Order Success Modal -->
<div class="modal-overlay" id="orderSuccessModal">
    <div class="modal modal-small">
        <div class="success-animation">
            <div class="success-circle">
                <i class="fas fa-check"></i>
            </div>
        </div>
        <div class="modal-header">
            <h2>Order Placed!</h2>
            <p>Your order has been successfully placed.</p>
        </div>
        <div class="order-success-details" id="orderSuccessDetails"></div>
        <button class="btn btn-primary btn-full" onclick="closeModal('orderSuccessModal')">
            Continue Browsing
        </button>
    </div>
</div>

<!-- My Orders Modal -->
<div class="modal-overlay modal-fullscreen" id="myOrdersModal">
    <div class="modal modal-large">
        <button class="modal-close" onclick="closeModal('myOrdersModal')">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-header">
            <h2><i class="fas fa-box"></i> My Orders</h2>
        </div>
        <div class="orders-list" id="ordersList">
            <div class="menu-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
        </div>
    </div>
</div>

<!-- My Reservations Modal -->
<div class="modal-overlay modal-fullscreen" id="myReservationsModal">
    <div class="modal modal-large">
        <button class="modal-close" onclick="closeModal('myReservationsModal')">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-header">
            <h2><i class="fas fa-calendar-alt"></i> My Reservations</h2>
        </div>
        <div class="reservations-list" id="reservationsList">
            <div class="menu-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
        </div>
    </div>
</div>

<!-- Image Lightbox -->
<div class="lightbox" id="lightbox">
    <button class="lightbox-close" onclick="closeLightbox()">
        <i class="fas fa-times"></i>
    </button>
    <button class="lightbox-prev" onclick="lightboxPrev()">
        <i class="fas fa-chevron-left"></i>
    </button>
    <div class="lightbox-content">
        <img src="" alt="" id="lightboxImg" />
        <p id="lightboxCaption"></p>
    </div>
    <button class="lightbox-next" onclick="lightboxNext()">
        <i class="fas fa-chevron-right"></i>
    </button>
</div>

<!-- Toast Notification -->
<div class="toast-container" id="toastContainer"></div>

<!-- Back to Top -->
<button class="back-to-top" id="backToTop" onclick="scrollToTop()">
    <i class="fas fa-chevron-up"></i>
</button>

<script src="js/main.js"></script>
</body>
</html>