// js/main.js
'use strict';

/* ============================================================
   BASE PATH — change to '/' when deploying to a live server root
============================================================ */
const BASE_PATH = '/sarovar-restaurant/';

/* ============================================================
   GLOBAL STATE
============================================================ */
let cart          = JSON.parse(localStorage.getItem('sarovar_cart')) || [];
let allMenuItems  = [];
let currentUser   = null;
let currentSlide  = 0;
let testimonialIdx= 0;
let galleryImages = [];
let lightboxIdx   = 0;
let orderCategory = 'all';
let appliedCoupon = null; // { code, discount, description }

/* ============================================================
   INIT
============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    initPreloader();
    initNavbar();
    initHeroSlider();
    initScrollReveal();
    initStats();
    initMenu();
    initGallery();
    initOrderSection();
    initForms();
    initTestimonials();
    checkAuthStatus();
    updateCartUI();
    setMinDate();
});

/* ============================================================
   PRELOADER
============================================================ */
function initPreloader() {
    window.addEventListener('load', () => {
        setTimeout(() => {
            document.getElementById('preloader').classList.add('hidden');
        }, 800);
    });
}

/* ============================================================
   NAVBAR
============================================================ */
function initNavbar() {
    const navbar    = document.getElementById('navbar');
    const hamburger = document.getElementById('hamburger');
    const navLinks  = document.getElementById('navLinks');
    const userBtn   = document.getElementById('userBtn');
    const userDropdown = document.getElementById('userDropdown');

    // Scroll effect
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
        // Back to top
        const backToTop = document.getElementById('backToTop');
        if (window.scrollY > 400) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
        // Active nav link
        updateActiveNavLink();
    });

    // Hamburger
    hamburger.addEventListener('click', () => {
        hamburger.classList.toggle('open');
        navLinks.classList.toggle('open');
    });

    // Close nav on link click
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            hamburger.classList.remove('open');
            navLinks.classList.remove('open');
        });
    });

    // User dropdown
    if (userBtn) {
        userBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
        });
    }

    // Close dropdown on outside click
    document.addEventListener('click', () => {
        if (userDropdown) userDropdown.classList.remove('open');
    });

    // Cart button
    document.getElementById('cartBtn').addEventListener('click', () => {
        document.getElementById('order').scrollIntoView({ behavior: 'smooth' });
    });
}

function updateActiveNavLink() {
    const sections = document.querySelectorAll('section[id]');
    const scrollPos = window.scrollY + 100;
    sections.forEach(section => {
        const top    = section.offsetTop;
        const height = section.offsetHeight;
        const id     = section.getAttribute('id');
        const link   = document.querySelector(`.nav-link[href="#${id}"]`);
        if (link) {
            if (scrollPos >= top && scrollPos < top + height) {
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            }
        }
    });
}

/* ============================================================
   HERO SLIDER
============================================================ */
function initHeroSlider() {
    const slides = document.querySelectorAll('.hero-slide');
    const dots   = document.querySelectorAll('#sliderDots .dot');
    if (!slides.length) return;

    setInterval(() => {
        slides[currentSlide].classList.remove('active');
        dots[currentSlide].classList.remove('active');
        currentSlide = (currentSlide + 1) % slides.length;
        slides[currentSlide].classList.add('active');
        dots[currentSlide].classList.add('active');
    }, 5000);
}

function goToSlide(index) {
    const slides = document.querySelectorAll('.hero-slide');
    const dots   = document.querySelectorAll('#sliderDots .dot');
    slides[currentSlide].classList.remove('active');
    dots[currentSlide].classList.remove('active');
    currentSlide = index;
    slides[currentSlide].classList.add('active');
    dots[currentSlide].classList.add('active');
}

/* ============================================================
   SCROLL REVEAL
============================================================ */
function initScrollReveal() {
    const elements = document.querySelectorAll(
        '.stat-item, .menu-card, .gallery-item, .contact-card, ' +
        '.about-content, .about-images, .testimonial-card, ' +
        '.reservation-form-card, .reservation-info, .order-card, ' +
        '.footer-brand, .footer-links, .footer-contact'
    );
    elements.forEach(el => el.classList.add('reveal'));

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.classList.add('visible');
                }, i * 80);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
}

/* ============================================================
   STATS COUNTER
============================================================ */
function initStats() {
    const counters = document.querySelectorAll('.stat-number');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    counters.forEach(counter => observer.observe(counter));
}

function animateCounter(el) {
    const target   = parseInt(el.dataset.target);
    const duration = 2000;
    const step     = target / (duration / 16);
    let current    = 0;
        const timer = setInterval(() => {
        current += step;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        el.textContent = Math.floor(current);
    }, 16);
}

/* ============================================================
   MENU
============================================================ */
function initMenu() {
    fetchCategories();
    fetchFeaturedMenu();
}

function fetchCategories() {
    fetch(BASE_PATH + 'api/menu.php?action=categories')
        .then(res => res.json())
        .then(categories => {
            buildMenuFilter(categories);
            buildOrderCategories(categories);
            buildFullMenuFilter(categories);
        })
        .catch(err => console.error('Error fetching categories:', err));
}

function fetchFeaturedMenu() {
    fetch(BASE_PATH + 'api/menu.php?action=all')
        .then(res => res.json())
        .then(items => {
            allMenuItems = items;
            renderMenuGrid(items.filter(i => i.is_featured == 1), 'menuGrid');
            renderOrderItems(items);
        })
        .catch(err => {
            document.getElementById('menuGrid').innerHTML =
                '<div class="menu-loading"><i class="fas fa-exclamation-circle"></i> Failed to load menu.</div>';
        });
}

function buildMenuFilter(categories) {
    const filter = document.getElementById('menuFilter');
    categories.forEach(cat => {
        const btn = document.createElement('button');
        btn.className    = 'filter-btn';
        btn.dataset.category = cat.id;
        btn.textContent  = cat.name;
        btn.addEventListener('click', () => {
            document.querySelectorAll('#menuFilter .filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if (btn.dataset.category === 'all') {
                renderMenuGrid(allMenuItems.filter(i => i.is_featured == 1), 'menuGrid');
            } else {
                renderMenuGrid(allMenuItems.filter(i => i.category_id == btn.dataset.category), 'menuGrid');
            }
        });
        filter.appendChild(btn);
    });

    // All button click
    filter.querySelector('[data-category="all"]').addEventListener('click', () => {
        document.querySelectorAll('#menuFilter .filter-btn').forEach(b => b.classList.remove('active'));
        filter.querySelector('[data-category="all"]').classList.add('active');
        renderMenuGrid(allMenuItems.filter(i => i.is_featured == 1), 'menuGrid');
    });
}

function buildFullMenuFilter(categories) {
    const filter = document.getElementById('fullMenuFilter');
    if (!filter) return;

    const allBtn = document.createElement('button');
    allBtn.className = 'filter-btn active';
    allBtn.textContent = 'All';
    allBtn.addEventListener('click', () => {
        document.querySelectorAll('#fullMenuFilter .filter-btn').forEach(b => b.classList.remove('active'));
        allBtn.classList.add('active');
        renderMenuGrid(allMenuItems, 'fullMenuGrid');
    });
    filter.appendChild(allBtn);

    categories.forEach(cat => {
        const btn = document.createElement('button');
        btn.className    = 'filter-btn';
        btn.textContent  = cat.name;
        btn.addEventListener('click', () => {
            document.querySelectorAll('#fullMenuFilter .filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderMenuGrid(allMenuItems.filter(i => i.category_id == cat.id), 'fullMenuGrid');
        });
        filter.appendChild(btn);
    });
}

function renderMenuGrid(items, containerId) {
    const grid = document.getElementById(containerId);
    if (!grid) return;

    if (!items || items.length === 0) {
        grid.innerHTML = '<div class="menu-loading"><i class="fas fa-info-circle"></i> No items found.</div>';
        return;
    }

    grid.innerHTML = items.map(item => `
        <div class="menu-card reveal">
            <div class="menu-card-img">
                ${item.image
                    ? `<img src="images/menu/${item.image}" alt="${escapeHtml(item.name)}" loading="lazy" />`
                    : `<div class="no-img">${getCategoryEmoji(item.category_name)}</div>`
                }
                <div class="veg-badge ${item.is_veg == 1 ? 'veg' : 'nonveg'}"></div>
                ${item.is_featured == 1 ? '<span class="featured-tag">⭐ Chef\'s Pick</span>' : ''}
            </div>
            <div class="menu-card-body">
                <div class="menu-card-category">${escapeHtml(item.category_name)}</div>
                <div class="menu-card-name">${escapeHtml(item.name)}</div>
                <div class="menu-card-desc">${escapeHtml(item.description || '')}</div>
                <div class="menu-card-footer">
                    <div class="menu-card-price">
                        ₹${parseFloat(item.price).toFixed(0)}
                        <span>per plate</span>
                    </div>
                    <button class="add-to-cart-btn" onclick="addToCart(${item.id},'${escapeHtml(item.name)}',${item.price},${item.is_veg})"
                        title="Add to cart">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>
    `).join('');

    // Re-observe new cards
    document.querySelectorAll(`#${containerId} .reveal`).forEach(el => {
        el.classList.add('visible');
    });
}

function openFullMenu() {
    openModal('fullMenuModal');
    renderMenuGrid(allMenuItems, 'fullMenuGrid');
}

function getCategoryEmoji(categoryName) {
    const map = {
        'Starters'      : '🥗',
        'Main Course'   : '🍛',
        'Breads'        : '🫓',
        'Rice & Biryani': '🍚',
        'Desserts'      : '🍮',
        'Beverages'     : '🥤',
    };
    return map[categoryName] || '🍽️';
}

/* ============================================================
   ORDER SECTION
============================================================ */
function initOrderSection() {
    const searchInput = document.getElementById('orderSearch');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const query    = searchInput.value.toLowerCase().trim();
            const filtered = allMenuItems.filter(item =>
                item.name.toLowerCase().includes(query) ||
                (item.description && item.description.toLowerCase().includes(query))
            );
            renderOrderItems(filtered);
        });
    }
}

function buildOrderCategories(categories) {
    const container = document.getElementById('orderCategories');
    if (!container) return;

    const allBtn = document.createElement('button');
    allBtn.className = 'filter-btn active';
    allBtn.textContent = 'All';
    allBtn.addEventListener('click', () => {
        document.querySelectorAll('#orderCategories .filter-btn').forEach(b => b.classList.remove('active'));
        allBtn.classList.add('active');
        orderCategory = 'all';
        renderOrderItems(allMenuItems);
    });
    container.appendChild(allBtn);

    categories.forEach(cat => {
        const btn = document.createElement('button');
        btn.className   = 'filter-btn';
        btn.textContent = cat.name;
        btn.addEventListener('click', () => {
            document.querySelectorAll('#orderCategories .filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            orderCategory = cat.id;
            renderOrderItems(allMenuItems.filter(i => i.category_id == cat.id));
        });
        container.appendChild(btn);
    });
}

function renderOrderItems(items) {
    const grid = document.getElementById('orderItemsGrid');
    if (!grid) return;

    if (!items || items.length === 0) {
        grid.innerHTML = '<div class="menu-loading"><i class="fas fa-search"></i> No items found.</div>';
        return;
    }

    grid.innerHTML = items.map(item => {
        const cartItem = cart.find(c => c.id == item.id);
        const qty      = cartItem ? cartItem.quantity : 0;
        return `
        <div class="order-item-card">
            <div class="order-item-img">
                ${item.image
                    ? `<img src="images/menu/${item.image}" alt="${escapeHtml(item.name)}" loading="lazy" />`
                    : `<div class="no-img">${getCategoryEmoji(item.category_name)}</div>`
                }
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
                <div class="veg-badge ${item.is_veg == 1 ? 'veg' : 'nonveg'}" style="position:static;"></div>
                <div class="order-item-name">${escapeHtml(item.name)}</div>
            </div>
            <div class="order-item-footer">
                <div class="order-item-price">₹${parseFloat(item.price).toFixed(0)}</div>
                <div class="qty-control">
                    <button class="qty-btn" onclick="changeOrderQty(${item.id},'${escapeHtml(item.name)}',${item.price},${item.is_veg},-1)">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span class="qty-display" id="qty-${item.id}">${qty}</span>
                    <button class="qty-btn" onclick="changeOrderQty(${item.id},'${escapeHtml(item.name)}',${item.price},${item.is_veg},1)">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        </div>
        `;
    }).join('');
}

function changeOrderQty(id, name, price, isVeg, delta) {
    const existing = cart.find(c => c.id == id);
    if (existing) {
        existing.quantity += delta;
        if (existing.quantity <= 0) {
            cart = cart.filter(c => c.id != id);
        }
    } else if (delta > 0) {
        cart.push({ id, name, price: parseFloat(price), isVeg, quantity: 1 });
    }
    saveCart();
    updateCartUI();
    const qtyEl = document.getElementById(`qty-${id}`);
    if (qtyEl) {
        const item = cart.find(c => c.id == id);
        qtyEl.textContent = item ? item.quantity : 0;
    }
}

/* ============================================================
   CART
============================================================ */
function addToCart(id, name, price, isVeg) {
    const existing = cart.find(c => c.id == id);
    if (existing) {
        existing.quantity++;
    } else {
        cart.push({ id, name, price: parseFloat(price), isVeg, quantity: 1 });
    }
    saveCart();
    updateCartUI();
    showToast(`${name} added to cart!`, 'success');
}

function saveCart() {
    localStorage.setItem('sarovar_cart', JSON.stringify(cart));
}

function updateCartUI() {
    const count    = cart.reduce((sum, item) => sum + item.quantity, 0);
    const subtotal = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    const gst      = subtotal * 0.05;
    const total    = subtotal + gst + (cart.length > 0 ? 40 : 0);

    // Cart count badge
    document.getElementById('cartCount').textContent = count;

    // Cart items panel
    const cartItemsEl  = document.getElementById('cartItems');
    const cartSummary  = document.getElementById('cartSummary');

    if (cart.length === 0) {
        cartItemsEl.innerHTML = `
            <div class="cart-empty">
                <i class="fas fa-shopping-basket"></i>
                <p>Your cart is empty</p>
                <span>Add items from the menu</span>
            </div>`;
        if (cartSummary) cartSummary.style.display = 'none';
        appliedCoupon = null; // reset coupon when cart empties
    } else {
        cartItemsEl.innerHTML = cart.map(item => `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${escapeHtml(item.name)}</div>
                    <div class="cart-item-price">₹${item.price.toFixed(0)} each</div>
                </div>
                <div class="cart-item-qty">
                    <button class="cart-qty-btn" onclick="changeOrderQty(${item.id},'${escapeHtml(item.name)}',${item.price},${item.isVeg},-1)">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span>${item.quantity}</span>
                    <button class="cart-qty-btn" onclick="changeOrderQty(${item.id},'${escapeHtml(item.name)}',${item.price},${item.isVeg},1)">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="cart-item-total">₹${(item.price * item.quantity).toFixed(0)}</div>
            </div>
        `).join('');

        if (cartSummary) {
            cartSummary.style.display = 'block';
            document.getElementById('cartSubtotal').textContent = `₹${subtotal.toFixed(0)}`;
            document.getElementById('cartGST').textContent      = `₹${gst.toFixed(0)}`;
            document.getElementById('cartTotal').textContent    = `₹${total.toFixed(0)}`;
        }
    }

    // Update checkout summary
    const discount = appliedCoupon ? appliedCoupon.discount : 0;
    const finalTotal = Math.max(0, total - discount);

    document.getElementById('checkoutSubtotal') &&
        (document.getElementById('checkoutSubtotal').textContent = `₹${subtotal.toFixed(0)}`);
    document.getElementById('checkoutGST') &&
        (document.getElementById('checkoutGST').textContent = `₹${gst.toFixed(0)}`);
    document.getElementById('checkoutTotal') &&
        (document.getElementById('checkoutTotal').textContent = `₹${finalTotal.toFixed(0)}`);
    if (document.getElementById('checkoutDiscount')) {
        document.getElementById('checkoutDiscount').textContent = `-₹${discount.toFixed(0)}`;
    }
}

function clearCart() {
    if (cart.length === 0) return;
    if (confirm('Are you sure you want to clear the cart?')) {
        cart = [];
        saveCart();
        updateCartUI();
        renderOrderItems(allMenuItems);
        showToast('Cart cleared', 'info');
    }
}

function openCheckout() {
    if (cart.length === 0) {
        showToast('Your cart is empty!', 'error');
        return;
    }
    updateCartUI();

    // Pre-fill if logged in
    if (currentUser) {
        const form = document.getElementById('checkoutForm');
        if (form) {
            form.querySelector('[name="name"]').value  = currentUser.name  || '';
            form.querySelector('[name="email"]').value = currentUser.email || '';
        }
    }
    openModal('checkoutModal');
}
/* ============================================================
   GALLERY
============================================================ */
function initGallery() {
    const items      = document.querySelectorAll('.gallery-item');
    const filterBtns = document.querySelectorAll('.gallery-filter .filter-btn');

    // Build lightbox images array
    galleryImages = Array.from(items).map(item => ({
        src     : item.querySelector('img') ? item.querySelector('img').src : '',
        caption : item.querySelector('.gallery-overlay span')
                    ? item.querySelector('.gallery-overlay span').textContent : ''
    }));

    // Filter
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const filter = btn.dataset.filter;
            items.forEach(item => {
                if (filter === 'all' || item.dataset.category === filter) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        });
    });

    // Lightbox open
    items.forEach((item, index) => {
        item.addEventListener('click', () => {
            openLightbox(index);
        });
    });
}

function openLightbox(index) {
    lightboxIdx = index;
    const lightbox = document.getElementById('lightbox');
    const img      = document.getElementById('lightboxImg');
    const caption  = document.getElementById('lightboxCaption');

    img.src          = galleryImages[index].src;
    caption.textContent = galleryImages[index].caption;
    lightbox.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
    document.body.style.overflow = '';
}

function lightboxPrev() {
    lightboxIdx = (lightboxIdx - 1 + galleryImages.length) % galleryImages.length;
    document.getElementById('lightboxImg').src = galleryImages[lightboxIdx].src;
    document.getElementById('lightboxCaption').textContent = galleryImages[lightboxIdx].caption;
}

function lightboxNext() {
    lightboxIdx = (lightboxIdx + 1) % galleryImages.length;
    document.getElementById('lightboxImg').src = galleryImages[lightboxIdx].src;
    document.getElementById('lightboxCaption').textContent = galleryImages[lightboxIdx].caption;
}

// Keyboard navigation for lightbox
document.addEventListener('keydown', (e) => {
    const lightbox = document.getElementById('lightbox');
    if (lightbox.classList.contains('open')) {
        if (e.key === 'ArrowLeft')  lightboxPrev();
        if (e.key === 'ArrowRight') lightboxNext();
        if (e.key === 'Escape')     closeLightbox();
    }
    // Close modals on Escape
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(modal => {
            modal.classList.remove('open');
            document.body.style.overflow = '';
        });
    }
});

/* ============================================================
   TESTIMONIALS
============================================================ */
function initTestimonials() {
    const cards = document.querySelectorAll('.testimonial-card');
    const dots  = document.querySelectorAll('#testimonialDots .dot');

    // Auto-rotate
    setInterval(() => {
        nextTestimonial();
    }, 5000);

    // Dot clicks
    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => goToTestimonial(i));
    });
}

function goToTestimonial(index) {
    const cards = document.querySelectorAll('.testimonial-card');
    const dots  = document.querySelectorAll('#testimonialDots .dot');
    cards[testimonialIdx].classList.remove('active');
    dots[testimonialIdx].classList.remove('active');
    testimonialIdx = index;
    cards[testimonialIdx].classList.add('active');
    dots[testimonialIdx].classList.add('active');
}

function prevTestimonial() {
    const cards = document.querySelectorAll('.testimonial-card');
    goToTestimonial((testimonialIdx - 1 + cards.length) % cards.length);
}

function nextTestimonial() {
    const cards = document.querySelectorAll('.testimonial-card');
    goToTestimonial((testimonialIdx + 1) % cards.length);
}

/* ============================================================
   FORMS
============================================================ */
function initForms() {
    // Reservation form
    const reservationForm = document.getElementById('reservationForm');
    if (reservationForm) {
        reservationForm.addEventListener('submit', handleReservation);
    }

    // Contact form
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', handleContact);
    }

    // Login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    // Register form
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }

    // Checkout form
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', handleCheckout);
    }
}

function setMinDate() {
    const dateInput = document.querySelector('input[name="date"]');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
    }
}

/* ---- Reservation ---- */
function handleReservation(e) {
    e.preventDefault();
    const form   = e.target;
    const btn    = form.querySelector('button[type="submit"]');
    const data   = new FormData(form);
    data.append('action', 'create');

    setButtonLoading(btn, true);

    fetch(BASE_PATH + 'api/reservation.php', { method: 'POST', body: data, credentials: 'include' })
        .then(res => res.json())
        .then(result => {
            setButtonLoading(btn, false);
            if (result.success) {
                showToast(result.message, 'success');
                form.reset();
                setMinDate();
            } else {
                showToast(result.error || 'Something went wrong', 'error');
            }
        })
        .catch(() => {
            setButtonLoading(btn, false);
            showToast('Network error. Please try again.', 'error');
        });
}

/* ---- Contact ---- */
function handleContact(e) {
    e.preventDefault();
    const form = e.target;
    const btn  = form.querySelector('button[type="submit"]');
    const data = new FormData(form);

    setButtonLoading(btn, true);

    fetch(BASE_PATH + 'api/contact.php', { method: 'POST', body: data })
        .then(res => res.json())
        .then(result => {
            setButtonLoading(btn, false);
            if (result.success) {
                showToast(result.message, 'success');
                form.reset();
            } else {
                showToast(result.error || 'Something went wrong', 'error');
            }
        })
        .catch(() => {
            setButtonLoading(btn, false);
            showToast('Network error. Please try again.', 'error');
        });
}

/* ---- Login ---- */
function handleLogin(e) {
    e.preventDefault();
    const form     = e.target;
    const btn      = form.querySelector('button[type="submit"]');
    const errorEl  = document.getElementById('loginError');
    const data     = new FormData(form);
    data.append('action', 'login');

    errorEl.style.display = 'none';
    setButtonLoading(btn, true);

    fetch(BASE_PATH + 'api/auth.php', { method: 'POST', body: data, credentials: 'include' })
        .then(res => res.json())
        .then(result => {
            setButtonLoading(btn, false);
            if (result.success) {
                currentUser = result.user;
                updateAuthUI(true, result.user);
                closeModal('loginModal');
                form.reset();
                showToast(`Welcome back, ${result.user.name}!`, 'success');
            } else {
                errorEl.textContent    = result.error;
                errorEl.style.display  = 'block';
            }
        })
        .catch(() => {
            setButtonLoading(btn, false);
            errorEl.textContent   = 'Network error. Please try again.';
            errorEl.style.display = 'block';
        });
}

/* ---- Register ---- */
function handleRegister(e) {
    e.preventDefault();
    const form    = e.target;
    const btn     = form.querySelector('button[type="submit"]');
    const errorEl = document.getElementById('registerError');
    const data    = new FormData(form);
    data.append('action', 'register');

    errorEl.style.display = 'none';
    setButtonLoading(btn, true);

    fetch(BASE_PATH + 'api/auth.php', { method: 'POST', body: data, credentials: 'include' })
        .then(res => res.json())
        .then(result => {
            setButtonLoading(btn, false);
            if (result.success) {
                currentUser = result.user;
                updateAuthUI(true, result.user);
                closeModal('registerModal');
                form.reset();
                showToast(`Welcome to Sarovar, ${result.user.name}!`, 'success');
            } else {
                errorEl.textContent   = result.error;
                errorEl.style.display = 'block';
            }
        })
        .catch(() => {
            setButtonLoading(btn, false);
            errorEl.textContent   = 'Network error. Please try again.';
            errorEl.style.display = 'block';
        });
}

/* ---- Checkout ---- */
function handleCheckout(e) {
    e.preventDefault();
    const form    = e.target;
    const btn     = form.querySelector('button[type="submit"]');
    const errorEl = document.getElementById('checkoutError');
    const data    = new FormData(form);

    if (cart.length === 0) {
        showToast('Your cart is empty!', 'error');
        return;
    }

    const subtotal = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    const gst      = subtotal * 0.05;
    const baseTotal= subtotal + gst + 40;
    const discount = appliedCoupon ? appliedCoupon.discount : 0;
    const total    = Math.max(0, baseTotal - discount);

    data.append('action',          'place');
    data.append('items',           JSON.stringify(cart));
    data.append('total_amount',    total.toFixed(2));
    data.append('coupon_code',     appliedCoupon ? appliedCoupon.code : '');
    data.append('discount_amount', discount.toFixed(2));

    errorEl.style.display = 'none';
    setButtonLoading(btn, true);

    fetch(BASE_PATH + 'api/order.php', { method: 'POST', body: data, credentials: 'include' })
        .then(res => res.json())
        .then(result => {
            setButtonLoading(btn, false);
            if (result.success) {
                closeModal('checkoutModal');
                form.reset();

                // Show success modal
                document.getElementById('orderSuccessDetails').innerHTML = `
                    <p><strong>Order ID:</strong> #${result.order_id}</p>
                    ${appliedCoupon ? `<p><strong>Coupon Applied:</strong> ${appliedCoupon.code} (-₹${appliedCoupon.discount.toFixed(0)})</p>` : ''}
                    <p><strong>Total:</strong> ₹${total.toFixed(0)}</p>
                    <p><strong>Estimated Delivery:</strong> 45–60 minutes</p>
                    <p><strong>Status:</strong> <span class="order-status status-pending">Pending</span></p>
                `;
                openModal('orderSuccessModal');

                // Clear cart and coupon
                cart = [];
                appliedCoupon = null;
                saveCart();
                updateCartUI();
                renderOrderItems(allMenuItems);
            } else {
                errorEl.textContent   = result.error || 'Failed to place order.';
                errorEl.style.display = 'block';
            }
        })
        .catch(() => {
            setButtonLoading(btn, false);
            errorEl.textContent   = 'Network error. Please try again.';
            errorEl.style.display = 'block';
        });
}

/* ============================================================
   AUTH
============================================================ */
function checkAuthStatus() {
    const data = new FormData();
    data.append('action', 'check');

    fetch(BASE_PATH + 'api/auth.php', { method: 'POST', body: data, credentials: 'include' })
        .then(res => res.json())
        .then(result => {
            if (result.loggedIn) {
                currentUser = result.user;
                updateAuthUI(true, result.user);
            } else {
                updateAuthUI(false, null);
            }
        })
        .catch(() => updateAuthUI(false, null));
}

function updateAuthUI(isLoggedIn, user) {
    const authButtons = document.getElementById('authButtons');
    const userMenu    = document.getElementById('userMenu');
    const userNameEl  = document.getElementById('userName');

    if (isLoggedIn && user) {
        authButtons.style.display = 'none';
        userMenu.style.display    = 'block';
        userNameEl.textContent    = user.name.split(' ')[0];
        currentUser               = user;
    } else {
        authButtons.style.display = 'flex';
        userMenu.style.display    = 'none';
        currentUser               = null;
    }
}

function logoutUser() {
    const data = new FormData();
    data.append('action', 'logout');

    fetch(BASE_PATH + 'api/auth.php', { method: 'POST', body: data, credentials: 'include' })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                updateAuthUI(false, null);
                showToast('Logged out successfully', 'info');
                document.getElementById('userDropdown').classList.remove('open');
            }
        })
        .catch(() => showToast('Logout failed. Try again.', 'error'));
}

/* ============================================================
   MY ORDERS
============================================================ */
function showMyOrders() {
    if (!currentUser) {
        openModal('loginModal');
        return;
    }
    openModal('myOrdersModal');
    document.getElementById('ordersList').innerHTML =
        '<div class="menu-loading"><i class="fas fa-spinner fa-spin"></i> Loading orders...</div>';

    fetch(BASE_PATH + 'api/order.php?action=get', { credentials: 'include' })
        .then(res => res.json())
        .then(orders => {
            const container = document.getElementById('ordersList');
            if (!orders || orders.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <p>No orders found</p>
                    </div>`;
                return;
            }
            container.innerHTML = orders.map(order => `
                <div class="order-card">
                    <div class="order-card-header">
                        <div>
                            <span class="order-id">Order #${order.id}</span>
                        </div>
                        <span class="order-status status-${order.status}">${formatStatus(order.status)}</span>
                    </div>
                    <div class="order-items-summary">
                        <i class="fas fa-utensils" style="color:var(--primary);margin-right:5px;"></i>
                        ${order.items_summary || 'No items'}
                    </div>
                    <div class="order-meta">
                        <span><i class="fas fa-calendar"></i> ${formatDate(order.created_at)}</span>
                        <span><i class="fas fa-map-marker-alt"></i> ${escapeHtml(order.address.substring(0, 40))}...</span>
                        <span><i class="fas fa-credit-card"></i> ${order.payment_method.toUpperCase()}</span>
                        <span class="order-total"><i class="fas fa-rupee-sign"></i> ${parseFloat(order.total_amount).toFixed(0)}</span>
                    </div>
                </div>
            `).join('');
        })
        .catch(() => {
            document.getElementById('ordersList').innerHTML =
                '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Failed to load orders.</p></div>';
        });
}

/* ============================================================
   MY RESERVATIONS
============================================================ */
function showMyReservations() {
    if (!currentUser) {
        openModal('loginModal');
        return;
    }
    openModal('myReservationsModal');
    document.getElementById('reservationsList').innerHTML =
        '<div class="menu-loading"><i class="fas fa-spinner fa-spin"></i> Loading reservations...</div>';

    fetch(BASE_PATH + 'api/reservation.php?action=get', { credentials: 'include' })
        .then(res => res.json())
        .then(reservations => {
            const container = document.getElementById('reservationsList');
            if (!reservations || reservations.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>No reservations found</p>
                    </div>`;
                return;
            }
            container.innerHTML = reservations.map(res => `
                <div class="reservation-card">
                    <div class="reservation-card-header">
                        <div>
                            <span class="order-id">Reservation #${res.id}</span>
                            <div style="font-size:0.82rem;color:var(--text-light);margin-top:3px;">
                                ${escapeHtml(res.name)} &bull; ${escapeHtml(res.phone)}
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span class="reservation-status status-${res.status}">
                                ${formatStatus(res.status)}
                            </span>
                            ${res.status !== 'cancelled'
                                ? `<button class="cancel-reservation-btn"
                                        onclick="cancelReservation(${res.id})">
                                        <i class="fas fa-times"></i> Cancel
                                   </button>`
                                : ''
                            }
                        </div>
                    </div>
                    <div class="order-meta" style="margin-top:12px;">
                        <span>
                            <i class="fas fa-calendar"></i>
                            ${formatDateOnly(res.date)}
                        </span>
                        <span>
                            <i class="fas fa-clock"></i>
                            ${formatTime(res.time)}
                        </span>
                        <span>
                            <i class="fas fa-users"></i>
                            ${res.guests} Guest${res.guests > 1 ? 's' : ''}
                        </span>
                        <span>
                            <i class="fas fa-calendar-plus"></i>
                            Booked: ${formatDate(res.created_at)}
                        </span>
                    </div>
                    ${res.special_requests
                        ? `<div style="margin-top:10px;font-size:0.82rem;color:var(--text-light);">
                               <i class="fas fa-comment" style="color:var(--primary);margin-right:5px;"></i>
                               ${escapeHtml(res.special_requests)}
                           </div>`
                        : ''
                    }
                </div>
            `).join('');
        })
        .catch(() => {
            document.getElementById('reservationsList').innerHTML =
                '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Failed to load reservations.</p></div>';
        });
}

function cancelReservation(reservationId) {
    if (!confirm('Are you sure you want to cancel this reservation?')) return;

    const data = new FormData();
    data.append('action',         'cancel');
    data.append('reservation_id', reservationId);

    fetch(BASE_PATH + 'api/reservation.php', { method: 'POST', body: data, credentials: 'include' })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                showToast(result.message, 'success');
                showMyReservations(); // Refresh list
            } else {
                showToast(result.error || 'Failed to cancel reservation', 'error');
            }
        })
        .catch(() => showToast('Network error. Please try again.', 'error'));
}

/* ============================================================
   MODAL HELPERS
============================================================ */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.remove('open');
    // Only restore scroll if no other modals are open
    const openModals = document.querySelectorAll('.modal-overlay.open');
    if (openModals.length === 0) {
        document.body.style.overflow = '';
    }
}

function switchModal(fromId, toId) {
    closeModal(fromId);
    setTimeout(() => openModal(toId), 200);
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            overlay.classList.remove('open');
            const openModals = document.querySelectorAll('.modal-overlay.open');
            if (openModals.length === 0) {
                document.body.style.overflow = '';
            }
        }
    });
});

/* ============================================================
   TOAST NOTIFICATIONS
============================================================ */
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    const icons = {
        success : 'fas fa-check-circle',
        error   : 'fas fa-exclamation-circle',
        info    : 'fas fa-info-circle',
    };

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="toast-icon ${icons[type] || icons.info}"></i>
        <span class="toast-message">${escapeHtml(message)}</span>
        <button class="toast-close" onclick="removeToast(this.parentElement)">
            <i class="fas fa-times"></i>
        </button>
    `;

    container.appendChild(toast);

    // Auto remove after 4 seconds
    setTimeout(() => removeToast(toast), 4000);
}

function removeToast(toast) {
    if (!toast || !toast.parentElement) return;
    toast.classList.add('removing');
    setTimeout(() => {
        if (toast.parentElement) toast.parentElement.removeChild(toast);
    }, 300);
}

/* ============================================================
   UTILITY HELPERS
============================================================ */
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g,  '&amp;')
        .replace(/</g,  '&lt;')
        .replace(/>/g,  '&gt;')
        .replace(/"/g,  '&quot;')
        .replace(/'/g,  '&#039;');
}

function setButtonLoading(btn, isLoading) {
    if (!btn) return;
    if (isLoading) {
        btn.dataset.originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Please wait...';
        btn.disabled  = true;
    } else {
        btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
        btn.disabled  = false;
    }
}

function formatStatus(status) {
    const map = {
        'pending'          : 'Pending',
        'confirmed'        : 'Confirmed',
        'preparing'        : 'Preparing',
        'out_for_delivery' : 'Out for Delivery',
        'delivered'        : 'Delivered',
        'cancelled'        : 'Cancelled',
    };
    return map[status] || status;
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-IN', {
        day   : '2-digit',
        month : 'short',
        year  : 'numeric',
        hour  : '2-digit',
        minute: '2-digit',
    });
}

function formatDateOnly(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-IN', {
        weekday : 'long',
        day     : '2-digit',
        month   : 'long',
        year    : 'numeric',
    });
}

function formatTime(timeStr) {
    if (!timeStr) return '';
    const [hours, minutes] = timeStr.split(':');
    const h   = parseInt(hours);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12  = h % 12 || 12;
    return `${h12}:${minutes} ${ampm}`;
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const btn   = input.nextElementSibling;
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type      = 'text';
        icon.className  = 'fas fa-eye-slash';
    } else {
        input.type      = 'password';
        icon.className  = 'fas fa-eye';
    }
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ============================================================
   SMOOTH SCROLL FOR NAV LINKS
============================================================ */
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href === '#') return;
        const target = document.querySelector(href);
        if (target) {
            e.preventDefault();
            const navHeight = document.getElementById('navbar').offsetHeight;
            const targetPos = target.offsetTop - navHeight;
            window.scrollTo({ top: targetPos, behavior: 'smooth' });
        }
    });
});

/* ============================================================
   LAZY LOAD IMAGES
============================================================ */
if ('IntersectionObserver' in window) {
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src   = img.dataset.src || img.src;
                imageObserver.unobserve(img);
            }
        });
    });
    lazyImages.forEach(img => imageObserver.observe(img));
}

/* ============================================================
   HANDLE IMAGE ERRORS
============================================================ */
document.addEventListener('error', (e) => {
    if (e.target.tagName === 'IMG') {
        e.target.style.display = 'none';
        const parent = e.target.parentElement;
        if (parent && !parent.querySelector('.no-img')) {
            const placeholder = document.createElement('div');
            placeholder.className   = 'no-img';
            placeholder.textContent = '🍽️';
            parent.appendChild(placeholder);
        }
    }
}, true);

/* ============================================================
   RESERVATION FORM AUTO-FILL IF LOGGED IN
============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    const resForm = document.getElementById('reservationForm');
    if (resForm && currentUser) {
        const nameInput  = resForm.querySelector('[name="name"]');
        const emailInput = resForm.querySelector('[name="email"]');
        if (nameInput  && currentUser.name)  nameInput.value  = currentUser.name;
        if (emailInput && currentUser.email) emailInput.value = currentUser.email;
    }
});

/* ============================================================
   WINDOW RESIZE HANDLER
============================================================ */
window.addEventListener('resize', () => {
    // Close mobile nav on resize to desktop
    if (window.innerWidth > 768) {
        document.getElementById('navLinks').classList.remove('open');
        document.getElementById('hamburger').classList.remove('open');
    }
});

/* ============================================================
   PRINT FRIENDLY - Disable animations on print
============================================================ */
window.addEventListener('beforeprint', () => {
    document.querySelectorAll('.reveal').forEach(el => el.classList.add('visible'));
});
/* ============================================================
   COUPON SYSTEM
============================================================ */
function applyCoupon() {
    const input  = document.getElementById('couponInput');
    const btn    = document.getElementById('applyCouponBtn');
    const msgEl  = document.getElementById('couponMsg');
    const code   = input.value.trim().toUpperCase();

    if (!code) {
        showCouponMsg('Please enter a coupon code', 'error');
        return;
    }

    const subtotal = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    const gst      = subtotal * 0.05;
    const total    = subtotal + gst + 40;

    btn.disabled   = true;
    btn.textContent = '...';

    const data = new FormData();
    data.append('action',       'validate');
    data.append('code',         code);
    data.append('order_amount', total.toFixed(2));

    fetch(BASE_PATH + 'api/coupon.php', { method: 'POST', body: data, credentials: 'include' })
        .then(r => r.json())
        .then(result => {
            btn.disabled    = false;
            btn.textContent = 'Apply';
            if (result.success) {
                appliedCoupon = {
                    code:        result.code,
                    discount:    result.discount,
                    description: result.description,
                };
                // Show discount row
                const row = document.getElementById('couponDiscountRow');
                if (row) row.style.display = 'flex';
                const codeDisplay = document.getElementById('couponCodeDisplay');
                if (codeDisplay) codeDisplay.textContent = result.code;
                showCouponMsg('🎉 ' + result.message, 'success');
                input.disabled = true;
                btn.textContent = 'Applied';
                btn.disabled    = true;
                updateCartUI();
            } else {
                appliedCoupon = null;
                showCouponMsg(result.error, 'error');
            }
        })
        .catch(() => {
            btn.disabled    = false;
            btn.textContent = 'Apply';
            showCouponMsg('Network error. Please try again.', 'error');
        });
}

function removeCoupon() {
    appliedCoupon = null;
    const input = document.getElementById('couponInput');
    const btn   = document.getElementById('applyCouponBtn');
    const row   = document.getElementById('couponDiscountRow');
    const msgEl = document.getElementById('couponMsg');

    if (input)  { input.value = ''; input.disabled = false; }
    if (btn)    { btn.textContent = 'Apply'; btn.disabled = false; }
    if (row)    row.style.display = 'none';
    if (msgEl)  msgEl.style.display = 'none';

    updateCartUI();
    showToast('Coupon removed', 'info');
}

function showCouponMsg(msg, type) {
    const el = document.getElementById('couponMsg');
    if (!el) return;
    el.textContent  = msg;
    el.className    = `coupon-msg ${type}`;
    el.style.display = 'block';
}

// Allow pressing Enter in coupon input
document.addEventListener('DOMContentLoaded', () => {
    const inp = document.getElementById('couponInput');
    if (inp) inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); applyCoupon(); } });
});
