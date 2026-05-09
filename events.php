<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Events & Celebrations – The Sarovar Court, Rourkela</title>
    <link rel="stylesheet" href="css/style.css"/>
    <link rel="stylesheet" href="css/events.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet"/>
</head>
<body>

<!-- NAVBAR (reuse from main) -->
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
            <li><a href="index.php#home"        class="nav-link">Home</a></li>
            <li><a href="index.php#menu"        class="nav-link">Menu</a></li>
            <li><a href="index.php#reservation" class="nav-link">Reserve a Table</a></li>
            <li><a href="events.php"             class="nav-link active">Events</a></li>
            <li><a href="index.php#contact"     class="nav-link">Contact</a></li>
        </ul>
        <div class="nav-actions">
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
                    <a href="#" onclick="showMyEventBookings()"><i class="fas fa-calendar-star"></i> My Event Bookings</a>
                    <a href="index.php" onclick=""><i class="fas fa-home"></i> Main Site</a>
                    <a href="#" onclick="logoutUser()"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
            <button class="hamburger" id="hamburger">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="ev-hero">
    <div class="ev-hero-overlay"></div>
    <div class="ev-hero-content">
        <span class="ev-hero-tag"><i class="fas fa-star"></i> Private Events & Celebrations</span>
        <h1>Make Your <span>Special Day</span><br/>Truly Unforgettable</h1>
        <p>From intimate celebrations to grand weddings — The Sarovar Court offers exclusive venue packages with world-class catering and personalised service in the heart of Rourkela.</p>
        <div class="ev-hero-stats">
            <div class="ev-stat"><span class="ev-stat-num">500+</span><span class="ev-stat-label">Events Hosted</span></div>
            <div class="ev-stat-divider"></div>
            <div class="ev-stat"><span class="ev-stat-num">500</span><span class="ev-stat-label">Max Capacity</span></div>
            <div class="ev-stat-divider"></div>
            <div class="ev-stat"><span class="ev-stat-num">15+</span><span class="ev-stat-label">Years Experience</span></div>
        </div>
        <div class="ev-hero-btns">
            <a href="#packages"  class="btn btn-primary btn-lg"><i class="fas fa-box-open"></i> View Packages</a>
            <a href="#book-event" class="btn btn-outline btn-lg"><i class="fas fa-calendar-check"></i> Book Now</a>
        </div>
    </div>
</section>

<!-- EVENT TYPES -->
<section class="ev-section" id="event-types">
    <div class="container">
        <div class="section-header">
            <div class="section-label">What are you celebrating?</div>
            <h2 class="section-title">Every <span>Occasion</span> Deserves the Best</h2>
            <p class="section-subtitle">We specialise in making all kinds of events truly memorable</p>
        </div>
        <div class="ev-types-grid">
            <div class="ev-type-card">
                <div class="ev-type-icon"><i class="fas fa-ring"></i></div>
                <h3>Wedding & Reception</h3>
                <p>Your dream wedding brought to life with luxurious décor, exquisite cuisine, and flawless execution.</p>
                <div class="ev-type-tag">Most booked</div>
            </div>
            <div class="ev-type-card">
                <div class="ev-type-icon"><i class="fas fa-birthday-cake"></i></div>
                <h3>Birthday Party</h3>
                <p>Celebrate milestone birthdays in style with themed décor, live counters, and a customised cake.</p>
            </div>
            <div class="ev-type-card">
                <div class="ev-type-icon"><i class="fas fa-briefcase"></i></div>
                <h3>Corporate Event</h3>
                <p>Professional setup for conferences, team dinners, product launches, and award ceremonies.</p>
            </div>
            <div class="ev-type-card">
                <div class="ev-type-icon"><i class="fas fa-heart"></i></div>
                <h3>Engagement & Anniversary</h3>
                <p>Intimate and romantic setups for engagements, anniversaries, and other love celebrations.</p>
            </div>
        </div>
    </div>
</section>

<!-- PACKAGES -->
<section class="ev-section ev-section-dark" id="packages">
    <div class="container">
        <div class="section-header">
            <div class="section-label light">Choose Your Package</div>
            <h2 class="section-title light">Venue <span>Packages</span></h2>
            <p class="section-subtitle light">All packages include a dedicated event coordinator, décor setup & takedown</p>
        </div>
        <div class="ev-packages-grid" id="packagesGrid">
            <div class="ev-loading"><i class="fas fa-spinner fa-spin"></i> Loading packages...</div>
        </div>
    </div>
</section>

<!-- WHY SAROVAR -->
<section class="ev-section" id="why">
    <div class="container">
        <div class="section-header">
            <div class="section-label">Why Choose Us</div>
            <h2 class="section-title">The <span>Sarovar Court</span> Difference</h2>
            <p class="section-subtitle">Everything you need for a perfect event, under one roof</p>
        </div>
        <div class="ev-why-grid">
            <div class="ev-why-card"><div class="ev-why-icon"><i class="fas fa-award"></i></div><h4>15+ years of expertise</h4><p>Over 500 events successfully hosted across Rourkela and Odisha with 5-star reviews.</p></div>
            <div class="ev-why-card"><div class="ev-why-icon"><i class="fas fa-user-tie"></i></div><h4>Dedicated coordinator</h4><p>A personal event coordinator assigned from the moment you book till the event is over.</p></div>
            <div class="ev-why-card"><div class="ev-why-icon"><i class="fas fa-utensils"></i></div><h4>Premium catering</h4><p>Our master chefs craft custom menus tailored to your event theme, guests, and preferences.</p></div>
            <div class="ev-why-card"><div class="ev-why-icon"><i class="fas fa-box-open"></i></div><h4>Flexible packages</h4><p>From 50 to 500+ guests — Silver, Gold, Platinum, or fully custom packages available.</p></div>
            <div class="ev-why-card"><div class="ev-why-icon"><i class="fas fa-clock"></i></div><h4>On-time guaranteed</h4><p>We ensure setup, service, and takedown run strictly on schedule — every single time.</p></div>
            <div class="ev-why-card"><div class="ev-why-icon"><i class="fas fa-plus-circle"></i></div><h4>Customisable add-ons</h4><p>Photography, DJ, floral arrangements, mehendi, and more — pick exactly what you need.</p></div>
        </div>
    </div>
</section>

<!-- BOOKING FORM -->
<section class="ev-section ev-section-booking" id="book-event">
    <div class="container">
        <div class="ev-booking-grid">
            <div class="ev-booking-info">
                <div class="section-label">Start Planning</div>
                <h2 class="section-title">Book Your <span>Event</span></h2>
                <p>Fill in the form and our events team will get back to you within 24 hours with a personalised quote and availability confirmation.</p>
                <div class="ev-contact-items">
                    <div class="ev-contact-item"><i class="fas fa-phone"></i><div><strong>Call Us</strong><span>+91 98765 43210</span></div></div>
                    <div class="ev-contact-item"><i class="fas fa-envelope"></i><div><strong>Email Us</strong><span>events@thesarovarcourt.com</span></div></div>
                    <div class="ev-contact-item"><i class="fas fa-map-marker-alt"></i><div><strong>Visit Us</strong><span>Ispat Market, Ambagan Circle, Bank Street, Sector 19, Rourkela – 769005</span></div></div>
                </div>
                <div class="ev-advance-note">
                    <i class="fas fa-info-circle"></i>
                    <span>A 25% advance is required to confirm your booking. The balance is due 7 days before the event.</span>
                </div>
            </div>
            <div class="ev-booking-form-card">
                <h3><i class="fas fa-calendar-check"></i> Event Booking Enquiry</h3>
                <form id="eventBookingForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Full Name *</label>
                            <input type="text" name="name" placeholder="Your full name" required/>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone *</label>
                            <input type="tel" name="phone" placeholder="+91 XXXXX XXXXX" required/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Address *</label>
                        <input type="email" name="email" placeholder="your@email.com" required/>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-star"></i> Event Type *</label>
                            <select name="event_type" required>
                                <option value="">Select event type</option>
                                <option value="wedding">Wedding / Reception</option>
                                <option value="birthday">Birthday Party</option>
                                <option value="corporate">Corporate Event</option>
                                <option value="engagement">Engagement</option>
                                <option value="anniversary">Anniversary</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Event Date *</label>
                            <input type="date" name="event_date" id="eventDate" required/>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-users"></i> Expected Guests *</label>
                            <select name="guest_count" required>
                                <option value="">Select guest count</option>
                                <option value="upto-50">Up to 50</option>
                                <option value="50-100">50 – 100</option>
                                <option value="100-250">100 – 250</option>
                                <option value="250-500">250 – 500</option>
                                <option value="500+">500+</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Time Slot *</label>
                            <select name="time_slot" required>
                                <option value="">Select time slot</option>
                                <option value="morning">Morning (8 AM – 2 PM)</option>
                                <option value="evening">Evening (4 PM – 10 PM)</option>
                                <option value="fullday">Full Day (8 AM – 8 PM)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-box-open"></i> Package Preference</label>
                        <select name="package_id" id="packageSelect" onchange="updateBookingSummary()">
                            <option value="">Select a package</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-comment"></i> Special Requirements</label>
                        <textarea name="special_requests" rows="3" placeholder="Décor theme, catering preferences, special requests..."></textarea>
                    </div>

                    <!-- Booking Summary -->
                    <div class="ev-booking-summary" id="bookingSummary" style="display:none">
                        <div class="ev-summary-title">Booking Summary</div>
                        <div class="ev-summary-row"><span>Package</span><span id="sumPkg">—</span></div>
                        <div class="ev-summary-row"><span>Max guests</span><span id="sumGuests">—</span></div>
                        <div class="ev-summary-row"><span>Duration</span><span id="sumDuration">—</span></div>
                        <div class="ev-summary-row"><span>Package total</span><span id="sumTotal">—</span></div>
                        <div class="ev-summary-row ev-summary-total"><span>25% Advance</span><span id="sumAdvance">—</span></div>
                    </div>

                    <div id="eventBookingError" class="form-error" style="display:none"></div>
                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-calendar-check"></i> Submit Booking Enquiry
                    </button>
                    <p class="ev-form-note">Our team will confirm availability and contact you within 24 hours.</p>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-bottom">
        <div class="container">
            <p>© 2026 The Sarovar Court – Multicuisine Restaurant, Rourkela. All Rights Reserved.</p>
            <p><a href="index.php" style="color:inherit">← Back to main site</a></p>
        </div>
    </div>
</footer>

<!-- SUCCESS MODAL -->
<div class="modal-overlay" id="eventSuccessModal">
    <div class="modal modal-small">
        <div class="success-animation">
            <div class="success-circle"><i class="fas fa-check"></i></div>
        </div>
        <div class="modal-header">
            <h2>Booking Submitted!</h2>
            <p>Your event enquiry has been received.</p>
        </div>
        <div id="eventSuccessDetails" class="order-success-details"></div>
        <button class="btn btn-primary btn-full" onclick="closeModal('eventSuccessModal')">Done</button>
    </div>
</div>

<!-- MY EVENT BOOKINGS MODAL -->
<div class="modal-overlay modal-fullscreen" id="myEventBookingsModal">
    <div class="modal modal-large">
        <button class="modal-close" onclick="closeModal('myEventBookingsModal')"><i class="fas fa-times"></i></button>
        <div class="modal-header">
            <h2><i class="fas fa-calendar-star"></i> My Event Bookings</h2>
        </div>
        <div id="myEventBookingsList">
            <div class="menu-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
        </div>
    </div>
</div>

<!-- LOGIN / REGISTER MODALS (reused) -->
<div class="modal-overlay" id="loginModal">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('loginModal')"><i class="fas fa-times"></i></button>
        <div class="modal-header">
            <div class="modal-logo">🍽️</div>
            <h2>Welcome Back!</h2>
            <p>Login to your Sarovar Court account</p>
        </div>
        <form id="loginForm" class="modal-form">
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" name="email" placeholder="your@email.com" required/>
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <div class="password-wrap">
                    <input type="password" name="password" id="loginPassword" placeholder="Your password" required/>
                    <button type="button" class="toggle-password" onclick="togglePassword('loginPassword')"><i class="fas fa-eye"></i></button>
                </div>
            </div>
            <div id="loginError" class="form-error" style="display:none"></div>
            <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-sign-in-alt"></i> Login</button>
        </form>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<div id="backToTop" class="back-to-top" onclick="scrollToTop()"><i class="fas fa-chevron-up"></i></div>

<script src="js/main.js"></script>
<script src="js/events.js"></script>
</body>
</html>
