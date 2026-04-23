// js/events.js
'use strict';

const EVENTS_BASE = '/sarovar-restaurant/';
let evPackages = [];

document.addEventListener('DOMContentLoaded', () => {
    setEventMinDate();
    loadPackages();
    initEventBookingForm();
    checkAuthStatus();

    // Navbar scroll
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 50);
        const btn = document.getElementById('backToTop');
        if (btn) btn.classList.toggle('visible', window.scrollY > 400);
    });

    // Hamburger
    const ham = document.getElementById('hamburger');
    const nav = document.getElementById('navLinks');
    if (ham) ham.addEventListener('click', () => {
        ham.classList.toggle('open');
        nav.classList.toggle('open');
    });

    // User dropdown
    const userBtn = document.getElementById('userBtn');
    const userDd  = document.getElementById('userDropdown');
    if (userBtn) userBtn.addEventListener('click', e => { e.stopPropagation(); userDd.classList.toggle('open'); });
    document.addEventListener('click', () => { if (userDd) userDd.classList.remove('open'); });
});

/* ===== SET MIN DATE ===== */
function setEventMinDate() {
    const input = document.getElementById('eventDate');
    if (input) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        input.min = tomorrow.toISOString().split('T')[0];
    }
}

/* ===== LOAD PACKAGES ===== */
function loadPackages() {
    fetch(EVENTS_BASE + 'api/event_booking.php?action=packages', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
            // Handle error responses
            if (!Array.isArray(data)) {
                const grid = document.getElementById('packagesGrid');
                if (!grid) return;
                if (data.error === 'table_missing' || data.error === 'no_packages') {
                    // Fallback to hardcoded packages if DB not set up yet
                    renderPackageCards(getDefaultPackages());
                    populatePackageSelect(getDefaultPackages());
                } else {
                    grid.innerHTML = '<div class="ev-loading"><i class="fas fa-exclamation-circle"></i> Failed to load packages. Please try again.</div>';
                }
                return;
            }
            evPackages = data;
            renderPackageCards(data);
            populatePackageSelect(data);
        })
        .catch(() => {
            // Network error — use fallback packages
            const fallback = getDefaultPackages();
            renderPackageCards(fallback);
            populatePackageSelect(fallback);
        });
}

// Fallback packages used when DB table doesn't exist yet
function getDefaultPackages() {
    return [
        {
            id: 1, slug: 'silver', name: 'Silver',
            price: 75000, max_guests: 100, duration: '6 hours',
            description: 'Perfect for intimate gatherings and small celebrations.',
            features: 'Basic hall décor & lighting|Veg buffet (5 items)|Welcome drinks|Dedicated event staff|Basic sound system|6 hours slot',
            features_list: ['Basic hall décor & lighting','Veg buffet (5 items)','Welcome drinks','Dedicated event staff','Basic sound system','6 hours slot']
        },
        {
            id: 2, slug: 'gold', name: 'Gold',
            price: 150000, max_guests: 250, duration: '8 hours',
            description: 'Our most popular package for weddings and large celebrations.',
            features: 'Premium floral décor & lighting|Veg + Non-veg buffet (10 items)|Live counters & dessert bar|Professional sound system|Dedicated event coordinator|Bridal room access|8 hours slot',
            features_list: ['Premium floral décor & lighting','Veg + Non-veg buffet (10 items)','Live counters & dessert bar','Professional sound system','Dedicated event coordinator','Bridal room access','8 hours slot']
        },
        {
            id: 3, slug: 'platinum', name: 'Platinum',
            price: 300000, max_guests: 500, duration: '12 hours',
            description: 'The ultimate luxury experience for grand weddings and elite events.',
            features: 'Luxury mandap / stage setup|Full veg + non-veg menu (15+ items)|DJ + professional lighting|Photography & videography|Bridal suite with green room|Valet parking|Dedicated wedding planner|Full day 12 hours slot',
            features_list: ['Luxury mandap / stage setup','Full veg + non-veg menu (15+ items)','DJ + professional lighting','Photography & videography','Bridal suite with green room','Valet parking','Dedicated wedding planner','Full day 12 hours slot']
        }
    ];
}

function renderPackageCards(packages) {
    const grid = document.getElementById('packagesGrid');
    if (!grid) return;
    const icons = { silver: '🥈', gold: '🥇', platinum: '💎' };
    grid.innerHTML = packages.map((pkg, i) => {
        const featured = pkg.slug === 'gold';
        const feats    = (pkg.features || '').split('|');
        return `
        <div class="ev-pkg ${featured ? 'featured' : ''}">
            ${featured ? '<div class="ev-pkg-badge">Most Popular</div>' : ''}
            <div class="ev-pkg-header">
                <span class="ev-pkg-icon">${icons[pkg.slug] || '🎉'}</span>
                <div class="ev-pkg-name">${escHtml(pkg.name)}</div>
                <div class="ev-pkg-guests">Up to ${pkg.max_guests} guests · ${escHtml(pkg.duration)}</div>
            </div>
            <div class="ev-pkg-body">
                <div class="ev-pkg-price">₹${fmtPrice(pkg.price)} <span>/ event</span></div>
                ${feats.map(f => `<div class="ev-pkg-feature"><i class="fas fa-check-circle"></i>${escHtml(f.trim())}</div>`).join('')}
                <button class="ev-pkg-btn" onclick="selectPackageAndScroll(${pkg.id})">
                    <i class="fas fa-calendar-check"></i> Book This Package
                </button>
            </div>
        </div>`;
    }).join('');
}

function populatePackageSelect(packages) {
    const sel = document.getElementById('packageSelect');
    if (!sel) return;
    packages.forEach(p => {
        const opt    = document.createElement('option');
        opt.value    = p.id;
        opt.textContent = `${p.name} – ₹${fmtPrice(p.price)} (up to ${p.max_guests} guests)`;
        opt.dataset.pkg  = JSON.stringify(p);
        sel.appendChild(opt);
    });
}

function selectPackageAndScroll(pkgId) {
    const sel = document.getElementById('packageSelect');
    if (sel) {
        sel.value = pkgId;
        updateBookingSummary();
    }
    document.getElementById('book-event').scrollIntoView({ behavior: 'smooth' });
}

function updateBookingSummary() {
    const sel = document.getElementById('packageSelect');
    const box = document.getElementById('bookingSummary');
    if (!sel || !box) return;
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.dataset.pkg) { box.style.display = 'none'; return; }
    const pkg = JSON.parse(opt.dataset.pkg);
    box.style.display = 'block';
    document.getElementById('sumPkg').textContent      = pkg.name;
    document.getElementById('sumGuests').textContent   = `Up to ${pkg.max_guests}`;
    document.getElementById('sumDuration').textContent = pkg.duration;
    document.getElementById('sumTotal').textContent    = `₹${fmtPrice(pkg.price)}`;
    document.getElementById('sumAdvance').textContent  = `₹${fmtPrice(pkg.price * 0.25)}`;
}

/* ===== BOOKING FORM ===== */
function initEventBookingForm() {
    const form = document.getElementById('eventBookingForm');
    if (!form) return;
    form.addEventListener('submit', handleEventBooking);
}

function handleEventBooking(e) {
    e.preventDefault();
    const form    = e.target;
    const btn     = form.querySelector('button[type="submit"]');
    const errorEl = document.getElementById('eventBookingError');
    const data    = new FormData(form);
    data.append('action', 'book');

    errorEl.style.display = 'none';
    setButtonLoading(btn, true);

    fetch(EVENTS_BASE + 'api/event_booking.php', { method: 'POST', body: data, credentials: 'include' })
        .then(r => r.json())
        .then(result => {
            setButtonLoading(btn, false);
            if (result.success) {
                form.reset();
                document.getElementById('bookingSummary').style.display = 'none';

                document.getElementById('eventSuccessDetails').innerHTML = `
                    <p><strong>Booking Ref:</strong> ${result.booking_ref}</p>
                    <p><strong>Package:</strong> ${result.package_name || 'Custom'}</p>
                    ${result.advance_amount ? `<p><strong>Advance (25%):</strong> ₹${fmtPrice(result.advance_amount)}</p>` : ''}
                    <p><strong>Next step:</strong> Our team will call you within 24 hours to confirm.</p>
                `;
                openModal('eventSuccessModal');
            } else {
                errorEl.textContent   = result.error || 'Something went wrong.';
                errorEl.style.display = 'block';
            }
        })
        .catch(() => {
            setButtonLoading(btn, false);
            errorEl.textContent   = 'Network error. Please try again.';
            errorEl.style.display = 'block';
        });
}

/* ===== MY EVENT BOOKINGS ===== */
function showMyEventBookings() {
    if (!currentUser) { openModal('loginModal'); return; }
    openModal('myEventBookingsModal');
    const list = document.getElementById('myEventBookingsList');
    list.innerHTML = '<div class="menu-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';

    fetch(EVENTS_BASE + 'api/event_booking.php?action=my_bookings', { credentials: 'include' })
        .then(r => r.json())
        .then(bookings => {
            if (!bookings || bookings.length === 0) {
                list.innerHTML = `<div class="empty-state"><i class="fas fa-calendar-times"></i><p>No event bookings found</p></div>`;
                return;
            }
            const statusClass = { pending:'s-pending', confirmed:'s-confirmed', cancelled:'s-cancelled', completed:'s-delivered' };
            list.innerHTML = bookings.map(b => `
            <div class="order-card">
                <div class="order-card-header">
                    <div>
                        <span class="order-id">${escHtml(b.booking_ref)}</span>
                        <div style="font-size:12px;color:var(--text-light);margin-top:3px">
                            ${escHtml(b.event_type.charAt(0).toUpperCase() + b.event_type.slice(1))}
                            ${b.package_name ? '· ' + escHtml(b.package_name) + ' Package' : ''}
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px">
                        <span class="order-status ${statusClass[b.status] || 's-pending'}">
                            ${b.status.charAt(0).toUpperCase() + b.status.slice(1)}
                        </span>
                        ${b.status === 'pending' ? `<button class="cancel-reservation-btn" onclick="cancelEventBooking(${b.id})"><i class="fas fa-times"></i> Cancel</button>` : ''}
                    </div>
                </div>
                <div class="order-meta" style="margin-top:10px">
                    <span><i class="fas fa-calendar"></i> ${formatEventDate(b.event_date)}</span>
                    <span><i class="fas fa-clock"></i> ${fmtTimeSlot(b.time_slot)}</span>
                    <span><i class="fas fa-users"></i> ${escHtml(b.guest_count)} guests</span>
                    ${b.package_price ? `<span><i class="fas fa-rupee-sign"></i> ₹${fmtPrice(b.package_price)} (25% adv: ₹${fmtPrice(b.advance_amount)})</span>` : ''}
                </div>
                ${b.admin_notes ? `<div style="margin-top:8px;font-size:12px;color:var(--text-light)"><i class="fas fa-comment" style="color:var(--primary);margin-right:5px"></i>${escHtml(b.admin_notes)}</div>` : ''}
            </div>`).join('');
        })
        .catch(() => {
            list.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Failed to load bookings.</p></div>';
        });
}

function cancelEventBooking(id) {
    if (!confirm('Are you sure you want to cancel this event booking?')) return;
    const data = new FormData();
    data.append('action', 'cancel');
    data.append('booking_id', id);
    fetch(EVENTS_BASE + 'api/event_booking.php', { method: 'POST', body: data, credentials: 'include' })
        .then(r => r.json())
        .then(result => {
            if (result.success) { showToast('Booking cancelled', 'info'); showMyEventBookings(); }
            else showToast(result.error || 'Failed to cancel', 'error');
        })
        .catch(() => showToast('Network error', 'error'));
}

/* ===== HELPERS ===== */
function fmtPrice(n) {
    return Math.round(parseFloat(n)).toLocaleString('en-IN');
}
function fmtTimeSlot(slot) {
    const m = { morning: 'Morning (8AM–2PM)', evening: 'Evening (4PM–10PM)', fullday: 'Full Day (8AM–8PM)' };
    return m[slot] || slot;
}
function formatEventDate(d) {
    if (!d) return '';
    return new Date(d).toLocaleDateString('en-IN', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
}
function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
